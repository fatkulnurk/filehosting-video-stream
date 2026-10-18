# 17. File Deduplication (Checksum-Based)

## Concept

Jika user upload file dengan checksum yang sama dengan file yang sudah ada, tidak perlu simpan file lagi. Cukup reference ke file yang sudah ada. Storage jadi lebih efisien.

## Checksum Algorithms (Mandatory)

Semua file WAJIB hitung **5 checksum** sekaligus untuk akurasi maksimal:

| Algorithm | Size | Kecepatan | Fungsi |
|-----------|------|-----------|--------|
| CRC32 | 32-bit | Sangat cepat | Quick pre-check (indexed) |
| MD5 | 128-bit | Cepat | Legacy compatibility |
| SHA1 | 160-bit | Cepat | Git compatibility |
| SHA256 | 256-bit | Sedang | Primary verification |
| SHA512 | 512-bit | Lambat | Maximum security |

**Alasan pakai semua:**
- **CRC32**: Quick lookup via index (O(1))
- **MD5**: Kompatibilitas dengan sistem lama yang pakai MD5
- **SHA1**: Kompatibilitas dengan Git dan sistem versi control
- **SHA256**: Primary verification, industry standard
- **SHA512**: Maximum security untuk file sensitif

**Lookup Strategy:**
1. CRC32 → Quick filter (indexed)
2. MD5 → Secondary filter
3. SHA256 → Primary verification
4. SHA1 + SHA512 → Audit trail

## Flow

```
User Upload File
       │
       ▼
Hitung CRC32 + SHA256
       │
       ▼
┌────────────────────────────┐
│ CRC32 ada di database?     │
└────────────────────────────┘
       │
   ┌───┴───┐
   │       │
  NO      YES
   │       │
   ▼       ▼
New      ┌────────────────────────────┐
File     │ SHA256 cocok dengan yang   │
         │ ada di CRC32 candidates?   │
         └────────────────────────────┘
                  │
              ┌───┴───┐
              │       │
             NO      YES
              │       │
              ▼       ▼
          New      Reference
          File     Existing
              │       │
              ▼       ▼
          Create   Create
          checksum files
          record   (ref)
```

## Database

### file_checksums

```php
Schema::create('file_checksums', function (Blueprint $table) {
    $table->uuid('id')->primary();

    // All 5 checksums (mandatory)
    $table->unsignedInteger('crc32');       // 32-bit, quick lookup
    $table->string('md5', 32);              // 128-bit, legacy compat
    $table->string('sha1', 40);             // 160-bit, git compat
    $table->string('sha256', 64);           // 256-bit, primary
    $table->string('sha512', 128);          // 512-bit, maximum security

    // File metadata
    $table->foreignUuid('storage_server_id')->constrained('storage_servers');
    $table->string('disk');
    $table->string('path');
    $table->unsignedBigInteger('size');
    $table->string('mime_type');
    $table->string('file_name');
    $table->unsignedInteger('reference_count')->default(1);
    $table->timestamps();

    // Indexes
    $table->index('crc32');        // Quick lookup
    $table->index('md5');          // Secondary lookup
    $table->unique('sha256');      // Primary verification
    $table->unique('sha1');        // Git compatibility
    $table->index(['size', 'crc32']); // Composite index
});
```

**Contoh Data:**

| id | crc32 | md5 | sha1 | sha256 | sha512 | reference_count |
|----|-------|-----|------|--------|--------|-----------------|
| uuid-1 | 3842756123 | d41d8cd9... | da39a3ee... | a1b2c3d4... | cf83e135... | 3 |
| uuid-2 | 1234567890 | 098f6bcd... | a94a8fe5... | e5f6g7h8... | 9e6a2e5a... | 1 |

**Lookup Strategy:**
1. CRC32 → Quick filter (indexed, O(1))
2. MD5 → Secondary filter
3. SHA256 → Primary verification (unique)
4. SHA1 + SHA512 → Audit trail

### files (updated)

```php
Schema::table('files', function (Blueprint $table) {
    $table->foreignUuid('file_checksum_id')->nullable()
        ->after('directory_id')
        ->constrained('file_checksums')->nullOnDelete();
});
```

## Checksum Service

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ChecksumService implements ChecksumServiceInterface
{
    /**
     * Calculate all 5 checksums
     */
    public function calculate(UploadedFile $file): array
    {
        $tempPath = $file->getRealPath();
        $content = file_get_contents($tempPath);

        return [
            'crc32' => crc32($content),
            'md5' => md5($content),
            'sha1' => sha1($content),
            'sha256' => hash('sha256', $content),
            'sha512' => hash('sha512', $content),
        ];
    }

    /**
     * Calculate individual checksums
     */
    public function crc32(UploadedFile $file): int
    {
        return crc32(file_get_contents($file->getRealPath()));
    }

    public function md5(UploadedFile $file): string
    {
        return md5_file($file->getRealPath());
    }

    public function sha1(UploadedFile $file): string
    {
        return sha1_file($file->getRealPath());
    }

    public function sha256(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    public function sha512(UploadedFile $file): string
    {
        return hash_file('sha512', $file->getRealPath());
    }

    /**
     * Verify file integrity
     */
    public function verify(string $path, string $expectedSha256): bool
    {
        return hash_equals($expectedSha256, hash_file('sha256', $path));
    }
}
```

## File Deduplication Service

```php
<?php

namespace App\Services;

use App\Models\FileChecksum;
use App\Repositories\Interfaces\FileChecksumRepositoryInterface;
use Illuminate\Http\UploadedFile;

class FileDeduplicationService implements FileDeduplicationServiceInterface
{
    public function __construct(
        private FileChecksumRepositoryInterface $checksumRepo,
        private ChecksumServiceInterface $checksumService
    ) {}

    /**
     * Find duplicate file
     * Strategy: CRC32 first (fast), then SHA256 (verify)
     */
    public function findDuplicate(UploadedFile $file): ?FileChecksum
    {
        // Step 1: Calculate both checksums
        $checksums = $this->checksumService->calculate($file);

        // Step 2: CRC32 quick lookup (indexed, O(1))
        $candidates = FileChecksum::where('crc32', $checksums['crc32'])
            ->where('size', $file->getSize()) // Extra safety check
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Step 3: SHA256 verification
        $match = $candidates->firstWhere('sha256', $checksums['sha256']);

        return $match;
    }

    /**
     * Store new checksum (all 5 algorithms)
     */
    public function storeChecksum(UploadedFile $file, string $disk, string $path): FileChecksum
    {
        $checksums = $this->checksumService->calculate($file);

        return $this->checksumRepo->create([
            'crc32' => $checksums['crc32'],
            'md5' => $checksums['md5'],
            'sha1' => $checksums['sha1'],
            'sha256' => $checksums['sha256'],
            'sha512' => $checksums['sha512'],
            'storage_server_id' => $this->getStorageServerId($disk),
            'disk' => $disk,
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_name' => $file->getClientOriginalName(),
            'reference_count' => 1,
        ]);
    }

    /**
     * Increment reference count
     */
    public function incrementReference(string $checksumId): void
    {
        FileChecksum::where('id', $checksumId)->increment('reference_count');
    }

    /**
     * Decrement reference count
     */
    public function decrementReference(string $checksumId): void
    {
        FileChecksum::where('id', $checksumId)->decrement('reference_count');
    }

    /**
     * Check if file can be deleted
     */
    public function canDelete(string $checksumId): bool
    {
        $checksum = FileChecksum::find($checksumId);

        return $checksum && $checksum->reference_count <= 1;
    }

    /**
     * Delete if unreferenced
     */
    public function deleteIfUnreferenced(string $checksumId): bool
    {
        $checksum = FileChecksum::find($checksumId);

        if (!$checksum || $checksum->reference_count > 1) {
            return false;
        }

        // Delete from storage
        \Storage::disk($checksum->disk)->delete($checksum->path);

        // Delete record
        $checksum->delete();

        return true;
    }
}
```

## FileService (Updated)

```php
public function store(UploadedFile $file, ?string $directoryId, string $userId, ?string $diskName = null): File
{
    $disk = $diskName ?? config('filesystems.default');

    // 1. Check for duplicate
    $existingChecksum = $this->dedupService->findDuplicate($file);

    if ($existingChecksum) {
        // 2a. Duplicate found - reference existing
        $this->dedupService->incrementReference($existingChecksum->id);

        $fileModel = $this->fileRepo->create([
            'user_id' => $userId,
            'directory_id' => $directoryId,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_checksum_id' => $existingChecksum->id,
        ]);

        // Create media reference from shared storage
        // Use storage path directly, NOT asset() which is server-local
        $fileModel->addMediaFromDisk($existingChecksum->path, $existingChecksum->disk)
            ->usingName($file->getClientOriginalName())
            ->toMediaCollection('default');

    } else {
        // 2b. New file - store normally
        $fileModel = $this->fileRepo->create([
            'user_id' => $userId,
            'directory_id' => $directoryId,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_checksum_id' => null,
        ]);

        // Store file
        $fileModel->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->toMediaCollection('default');

        // Store checksum
        $media = $fileModel->getFirstMedia();
        $this->dedupService->storeChecksum($file, $disk, $media->path);
    }

    return $fileModel;
}

public function delete(File $file): bool
{
    $checksumId = $file->file_checksum_id;

    $this->fileRepo->delete($file->id);

    if ($checksumId) {
        $this->dedupService->decrementReference($checksumId);
        $this->dedupService->deleteIfUnreferenced($checksumId);
    }

    return true;
}
```

## Performance

**File: 1GB video.mp4**

| Step | Operation | Time |
|------|-----------|------|
| 1 | Calculate CRC32 | 0.8s |
| 2 | Calculate SHA256 | 4.2s |
| 3 | DB lookup (CRC32 index) | 0.001s |
| 4 | DB lookup (SHA256 unique) | 0.001s |
| **Total** | | **~5s** |

**Vs tanpa dedup:**
- Upload 1GB = 5-10 detik (tergantung bandwidth)
- Dedup = 5 detik (checksum only)
- **Result:** Lebih cepat upload file yang sudah ada

## Benefits

| Benefit | Description |
|---------|-------------|
| Storage Savings | File sama tidak disimpan berulang |
| Faster Upload | File sudah ada = skip storage |
| Bandwidth Savings | Tidak perlu transfer ulang |
| Integrity Check | SHA256 verify file tidak corrupt |
| Audit Trail | Semua checksum tercatat |

## Delete Flow

```
User Delete File
       │
       ▼
Decrement reference_count
       │
       ▼
┌─────────────────────┐
│ reference_count > 0?│
└─────────────────────┘
       │
   ┌───┴───┐
   │       │
  YES      NO
   │       │
   ▼       ▼
Keep      Delete
File      File
```

## Migration

```php
Schema::create('file_checksums', function (Blueprint $table) {
    $table->uuid('id')->primary();

    // All 5 checksums (mandatory)
    $table->unsignedInteger('crc32');       // 32-bit, quick lookup
    $table->string('md5', 32);              // 128-bit, legacy compat
    $table->string('sha1', 40);             // 160-bit, git compat
    $table->string('sha256', 64);           // 256-bit, primary
    $table->string('sha512', 128);          // 512-bit, maximum security

    $table->foreignUuid('storage_server_id')->constrained('storage_servers');
    $table->string('disk');
    $table->string('path');
    $table->unsignedBigInteger('size');
    $table->string('mime_type');
    $table->string('file_name');
    $table->unsignedInteger('reference_count')->default(1);
    $table->timestamps();

    // Indexes
    $table->index('crc32');        // Quick lookup
    $table->index('md5');          // Secondary lookup
    $table->unique('sha256');      // Primary verification
    $table->unique('sha1');        // Git compatibility
    $table->index(['size', 'crc32']); // Composite index
});

Schema::table('files', function (Blueprint $table) {
    $table->foreignUuid('file_checksum_id')->nullable()
        ->after('directory_id')
        ->constrained('file_checksums')->nullOnDelete();
});
```

---

[← Back to Main PRD](../PRD.md)


---

## Multi-Server Deployment

### Problem

Jika deploy di multi-server dengan Docker (stateless):
- Setiap server punya storage lokal sendiri
- File di Server A tidak bisa diakses dari Server B
- `asset('storage/...')` hanya work di server yang sama

### Solution: Shared Storage

**WAJIB pakai shared storage** untuk file deduplication:

| Storage Type | Multi-Server Ready | Contoh |
|--------------|-------------------|--------|
| Local | ❌ Tidak | `/var/www/storage` |
| S3 | ✅ Ya | AWS S3, MinIO, DigitalOcean Spaces |
| GCS | ✅ Ya | Google Cloud Storage |
| Azure Blob | ✅ Ya | Azure Blob Storage |
| Dropbox | ✅ Ya | Dropbox API |
| NFS | ✅ Ya | Network File System |

### Config

```php
// config/filesystems.php
return [
    'disks' => [
        // ❌ TIDAK BOLEH pakai local untuk dedup
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        // ✅ WAJIB pakai shared storage
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],
    ],

    // Default disk untuk file upload
    'default' => env('FILESYSTEM_DISK', 's3'),
];
```

### Service (Updated for Multi-Server)

```php
<?php

namespace App\Services;

class FileService implements FileServiceInterface
{
    /**
     * Store file - multi-server compatible
     */
    public function store(UploadedFile $file, ?string $directoryId, string $userId, ?string $diskName = null): File
    {
        // Use shared storage (S3, GCS, etc.)
        $disk = $diskName ?? config('filesystems.default');

        // Verify disk is shared storage
        $this->validateDisk($disk);

        // ... rest of the logic
    }

    /**
     * Validate disk is shared storage
     */
    private function validateDisk(string $disk): void
    {
        $diskConfig = config("filesystems.disks.{$disk}");

        if ($diskConfig['driver'] === 'local') {
            throw new \InvalidArgumentException(
                "Local storage cannot be used for file deduplication in multi-server setup. " .
                "Use shared storage (S3, GCS, Azure, etc.)"
            );
        }
    }
}
```

### Media Library Reference

```php
// ✅ BENAR - Use disk directly (works across servers)
$fileModel->addMediaFromDisk($existingChecksum->path, $existingChecksum->disk)
    ->usingName($file->getClientOriginalName())
    ->toMediaCollection('default');

// ❌ SALAH - asset() is server-local
$fileModel->addMediaFromUrl(asset('storage/' . $existingChecksum->path))
    ->usingName($file->getClientOriginalName())
    ->toMediaCollection('default');
```

### URL Generation

```php
// Get public URL (works across servers)
$url = $file->getFirstMedia()->getTemporaryUrl(now()->addMinutes(5));

// Or permanent URL for public files
$url = $file->getFirstMedia()->getUrl();
```

### Docker Compose (Multi-Server)

```yaml
version: '3.8'

services:
  app:
    build: .
    environment:
      - FILESYSTEM_DISK=s3
      - AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
      - AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
      - AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION}
      - AWS_BUCKET=${AWS_BUCKET}
    # No local storage volumes needed!
```

### Deployment Architecture

```
                    ┌─────────────────┐
                    │   Load Balancer  │
                    └────────┬────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
            ▼                ▼                ▼
     ┌────────────┐  ┌────────────┐  ┌────────────┐
     │  Server 1  │  │  Server 2  │  │  Server 3  │
     │  (Laravel) │  │  (Laravel) │  │  (Laravel) │
     └──────┬─────┘  └──────┬─────┘  └──────┬─────┘
            │                │                │
            └────────────────┼────────────────┘
                             │
                    ┌────────┴────────┐
                    │   Shared Storage │
                    │   (S3 / GCS)    │
                    └─────────────────┘
```

### File Deduplication Flow (Multi-Server)

```
Server 1: User upload file
     │
     ▼
Calculate checksums (CRC32 + MD5 + SHA1 + SHA256 + SHA512)
     │
     ▼
Check database for checksum
     │
     ├── Found: Reference existing (no storage needed)
     │
     └── Not found: Store to S3 + create checksum record
```

### Key Points

| Point | Description |
|-------|-------------|
| **Shared Storage** | WAJIB pakai S3/GCS/Azure, bukan local |
| **No Local Storage** | Docker container stateless, tidak ada local file |
| **Reference by Path** | Gunakan `$media->path` bukan `asset()` |
| **Temporary URLs** | Gunakan `getTemporaryUrl()` untuk akses file |
| **CDN** | Optional: pakai CloudFront/Cloudflare untuk caching |

