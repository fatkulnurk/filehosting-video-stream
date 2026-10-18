# 03 Architecture

| 7 | Profile | Edit Profile | Update nama, email, password |
| 8 | Profile | Upload Avatar | Upload foto profil |
| 9 | Dashboard | View Statistics | Lihat total files, views, downloads |
| 10 | Dashboard | Recent Files | Lihat 5 file terakhir |
| 11 | Dashboard | Storage Info | Lihat info storage yang dimiliki |
| 12 | Directory | Create Folder | Buat folder baru |
| 13 | Directory | List Folder | Lihat list folder |
| 14 | Directory | Navigate Folder | Navigasi ke dalam folder |
| 15 | Directory | Rename Folder | Rename folder |
| 16 | Directory | Delete Folder | Hapus folder + isi |
| 17 | Directory | Move Folder | Pindahkan folder |
| 18 | File | Upload File | Upload file (max 100MB) |
| 19 | File | List Files | Lihat list file di folder |
| 20 | File | Search File | Cari file berdasarkan nama |
| 21 | File | View File Detail | Lihat detail file |
| 22 | File | Rename File | Rename file |
| 23 | File | Move File | Pindahkan file ke folder lain |
| 24 | File | Delete File | Hapus file |
| 25 | File | Copy Link | Copy public link |
| 26 | File | Copy Embed | Copy embed code |
| 27 | File | Download File | Download file sendiri |
| 28 | Storage | View Storage | Lihat list storage server |
| 29 | Storage | Add Storage | Tambah storage server baru |
| 30 | Storage | Edit Storage | Edit config storage |
| 31 | Storage | Delete Storage | Hapus storage server |
| 32 | Public | View Public File | Akses file public via link |
| 33 | Public | Download Public File | Download file public |
| 34 | Public | Embed Video | Embed video di website lain |
| 35 | Public | Stream Video | Stream video langsung |

#### Admin Features

Semua fitur Member, plus:

| No | Module | Feature | Description |
|----|--------|---------|-------------|
| 1 | User Management | View All Users | Lihat semua user |
| 2 | User Management | Edit User | Edit profil user lain |
| 3 | User Management | Delete User | Hapus akun user |
| 4 | User Management | Change Role | Ubah role user (member/admin) |
| 5 | User Management | Suspend User | Nonaktifkan akun user |
| 6 | File Management | View All Files | Lihat semua file semua user |
| 7 | File Management | Delete Any File | Hapus file user lain |
| 8 | File Management | File Statistics | Statistik semua file |
| 9 | Directory Management | View All Directories | Lihat semua folder semua user |
| 10 | Directory Management | Delete Any Directory | Hapus folder user lain |
| 11 | Storage Management | View All Storage | Lihat semua storage semua user |
| 12 | Storage Management | System Storage Stats | Statistik storage sistem |
| 13 | System | System Settings | Pengaturan sistem |
| 14 | System | System Statistics | Statistik sistem lengkap |
| 15 | System | Manage Admins | Tambah/hapus admin |
| 16 | System | View Logs | Lihat logs sistem |
| 17 | System | System Health | Cek kesehatan sistem |
| 18 | Dashboard | Global Statistics | Statistik semua user |
| 19 | Dashboard | User Statistics | Statistik per user |
| 20 | Dashboard | Revenue Stats | Statistik penggunaan (jika ada) |

#### Authorization Matrix

| Action | Member | Admin |
|--------|--------|-------|
| Upload file | Ya | Ya |
| View own files | Ya | Ya |
| View all files | Tidak | Ya |
| Delete own file | Ya | Ya |
| Delete any file | Tidak | Ya |
| Create directory | Ya | Ya |
| Delete own directory | Ya | Ya |
| Delete any directory | Tidak | Ya |
| Add storage | Ya | Ya |
| View own storage | Ya | Ya |
| View all storage | Tidak | Ya |
| Delete storage | Ya | Ya |
| Edit user profile | Sendiri | Semua |
| Delete user | Tidak | Ya |
| Change role | Tidak | Ya |
| System settings | Tidak | Ya |
| View statistics | Own | Global |

### 17.3 Strategi Migrasi Data Antar Provider

#### Kenapa Spatie Media Library Sudah Cukup?

Spatie Media Library sudah handle:
- **Penyimpanan file** di berbagai disk (local, s3, dropbox, dll)
- **Metadata file** (name, file_name, mime_type, size, disk, path)
- **Custom properties** (bisa tambah checksum, dll)
- **File conversions** (thumbnail, preview, dll)
- **Moving files** antar disk via `move()` method

```php
// Spatie sudah support move file antar disk
$media->move($newDisk, $newPath);

// Atau copy
$media->copy($newDisk, $newPath);
```

#### Yang Perlu Kita Tambah

| Fitur | Spatie | Kita Tambah |
|-------|--------|-------------|
| File storage | Ya | - |
| File metadata | Ya | - |
| Multiple disks | Ya | - |
| Move between disks | Ya | - |
| Checksum tracking | Custom props | Ya |
| Migration history | Tidak | Ya (audit trail) |
| Backup strategy | Tidak | Ya |
| Link expiry | Tidak | Ya (di files table) |
| View/download counter | Tidak | Ya (di files table) |

#### Design yang Direkomendasikan

**1. Hapus storage_server_id dari files table**

Tidak perlu FK ke storage_servers di files table. Biarkan Spatie handle via media.disk.

**2. Hapus tabel file_migrations**

Tidak perlu tabel terpisah. Gunakan custom_properties di media untuk audit trail.

**3. Gunakan Spatie Media Library sepenuhnya**

```php
// Upload file
$file->addMedia($uploadedFile)
    ->withCustomProperties([
        'checksum' => hash_file('sha256', $tempPath),
        'uploaded_by' => auth()->id(),
    ])
    ->toMediaCollection('default');

// Move file ke disk lain
$media->move($newDisk, $newPath);

// Update custom properties
$media->updateCustomProperties([
    'checksum_verified' => true,
    'migrated_at' => now(),
    'migrated_from' => $oldDisk,
]);
```

**4. Backup Strategy via Spatie**

```php
// Config: filesystems.php
'disks' => [
    's3' => [...],
    'wasabi' => [
        'driver' => 's3',
        'key' => env('WASABI_KEY'),
        'secret' => env('WASABI_SECRET'),
        'region' => env('WASABI_REGION'),
        'bucket' => env('WASABI_BUCKET'),
        'endpoint' => 'https://s3.wasabisys.com',
    ],
],

// Backup: copy media ke backup disk
$media->copy('wasabi', $media->path);
```

**5. Files Table (Final)**

Hanya field yang Spatie TIDAK handle:

| Field | Type | Fungsi |
|-------|------|--------|
| id | uuid | PK |
| user_id | uuid | FK users |
| directory_id | uuid | FK directories |
| name | varchar | Display name |
| slug | varchar | URL-friendly |
| status | varchar | active/archived/deleted |
| views | bigint | Counter |
| downloads | bigint | Counter |
| expires_at | timestamp | Link expiry |
| is_public | boolean | Public/private |
| created_at | timestamp | - |
| updated_at | timestamp | - |

**6. Storage Servers Table (Final)**

| Field | Type | Fungsi |
|-------|------|--------|
| id | uuid | PK |
| user_id | uuid | FK users |
| name | varchar | Disk name |
| driver | varchar | Driver type |
| config | json | Config |
| is_active | boolean | Status |
| is_primary | boolean | Primary storage |
| backup_storage_server_id | uuid | Backup server |
| auto_backup | boolean | Auto backup |
| storage_limit | bigint | Limit |
| storage_used | bigint | Used |
| deleted_at | timestamp | Soft delete |
| created_at | timestamp | - |
| updated_at | timestamp | - |

#### Kesimpulan

**Tidak perlu:**
- `storage_server_id` di files table
- Tabel `file_migrations`
- Migration flow yang kompleks

**Cukup:**
- Spatie Media Library untuk file management
- Custom properties untuk checksum/audit
- Storage servers untuk config
- Backup via copy antar disk

### 17.4 StorageDriverEnum

```php
<?php

namespace App\Enums;

enum StorageDriver: string
{
    case Local = 'local';
    case S3 = 's3';
    case Dropbox = 'dropbox';
    case GCS = 'gcs';
    case Azure = 'azure';
    case FTP = 'ftp';
    case SFTP = 'sftp';

---
[← Back to Main PRD](../PRD.md)


---

## 13. Interface Requirements

### 13.1 Mandatory Interface Pattern

Semua service DAN repository WAJIB menggunakan interface. Ini memastikan:
- Dependency Injection bisa dilakukan
- Mocking untuk testing lebih mudah
- Swapping implementation tanpa ubah kode pemanggil
- Enforce contract antar layer

### 13.2 Repository Interfaces

```php
<?php

namespace App\Repositories\Interfaces;

use App\Models\File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface FileRepositoryInterface
{
    public function create(array $data): File;
    public function findById(string $id): ?File;

    public function getUserFiles(string $userId, ?string $directoryId = null): LengthAwarePaginator;
    public function update(string $id, array $data): File;
    public function delete(string $id): bool;
    public function incrementViews(string $id): void;
    public function incrementDownloads(string $id): void;
    public function search(string $userId, string $query): LengthAwarePaginator;
}
```

```php
<?php

namespace App\Repositories\Interfaces;

use App\Models\Directory;
use Illuminate\Database\Eloquent\Collection;

interface DirectoryRepositoryInterface
{
    public function create(array $data): Directory;
    public function findById(string $id): ?Directory;
    public function getUserDirectories(string $userId, ?string $parentId = null): Collection;
    public function getTree(string $userId): Collection;
    public function getPath(string $id): Collection;
    public function update(string $id, array $data): Directory;
    public function delete(string $id): bool;
    public function getBreadcrumbs(string $id): Collection;
}
```

```php
<?php

namespace App\Repositories\Interfaces;

use App\Models\StorageServer;
use Illuminate\Database\Eloquent\Collection;

interface StorageServerRepositoryInterface
{
    public function create(array $data): StorageServer;
    public function findById(string $id): ?StorageServer;
    public function getUserStorageServers(string $userId): Collection;
    public function getActiveServers(): Collection;
    public function update(string $id, array $data): StorageServer;
    public function delete(string $id): bool;
    public function getStats(string $userId): array;
}
```

### 13.3 Service Interfaces

```php
<?php

namespace App\Services\Interfaces;

use App\Models\File;
use Illuminate\Http\UploadedFile;

interface FileServiceInterface
{
    public function store(UploadedFile $file, ?string $directoryId, string $userId, ?string $diskName = null): File;
    public function delete(File $file): bool;
    public function move(File $file, ?string $newDirectoryId): File;
    public function rename(File $file, string $name): File;
    public function getById(string $id): ?File;
    public function getByCode(string $code): ?File;
    public function getPublicFile(string $code): ?File;
    public function incrementViews(File $file): void;
    public function incrementDownloads(File $file): void;
}
```

```php
<?php

namespace App\Services\Interfaces;

use App\Models\Directory;
use Illuminate\Support\Collection;

interface DirectoryServiceInterface
{
    public function create(string $name, ?string $parentId, string $userId): Directory;
    public function delete(Directory $directory): bool;
    public function update(Directory $directory, string $name): Directory;
    public function move(Directory $directory, ?string $newParentId): Directory;
    public function getTree(string $userId): Collection;
    public function getPath(string $id, string $userId): Collection;
    public function getBreadcrumbs(string $id): Collection;
}
```

```php
<?php

namespace App\Services\Interfaces;

use App\Models\StorageServer;
use Illuminate\Support\Collection;

interface StorageServiceInterface
{
    public function registerDisk(StorageServer $server): void;
    public function registerAllDisks(): void;
    public function getStats(string $userId): array;
    public function syncUsedSize(StorageServer $server): void;
    public function addStorage(array $data): StorageServer;
    public function updateStorage(string $id, array $data): StorageServer;
    public function deleteStorage(string $id): bool;
}
```

### 13.4 Binding di ServiceProvider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\FileRepositoryInterface;
use App\Repositories\FileRepository;
use App\Repositories\Interfaces\DirectoryRepositoryInterface;
use App\Repositories\DirectoryRepository;
use App\Repositories\Interfaces\StorageServerRepositoryInterface;
use App\Repositories\StorageServerRepository;
use App\Services\Interfaces\FileServiceInterface;
use App\Services\FileService;
use App\Services\Interfaces\DirectoryServiceInterface;
use App\Services\DirectoryService;
use App\Services\Interfaces\StorageServiceInterface;
use App\Services\StorageService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(FileRepositoryInterface::class, FileRepository::class);
        $this->app->bind(DirectoryRepositoryInterface::class, DirectoryRepository::class);
        $this->app->bind(StorageServerRepositoryInterface::class, StorageServerRepository::class);

        // Services
        $this->app->bind(FileServiceInterface::class, FileService::class);
        $this->app->bind(DirectoryServiceInterface::class, DirectoryService::class);
        $this->app->bind(StorageServiceInterface::class, StorageService::class);
    }
}
```

### 13.5 Aturan Wajib

| Layer | Wajib | Tidak Boleh |
|-------|-------|-------------|
| Controller | Inject Service Interface | Inject Repository/Model |
| Service | Inject Repository Interface | Direct DB query |
| Repository | Implement Repository Interface | Business logic |
| Job/Notification | Inject Service Interface | Direct DB query |
| Schedule | Inject Service Interface | Direct DB query |

### 13.6 Contoh Penggunaan di Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\FileServiceInterface;
use App\Http\Requests\FileRequest;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function __construct(
        private FileServiceInterface $fileService
    ) {}

    public function store(FileRequest $request)
    {
        $file = $this->fileService->store(
            $request->file('file'),
            $request->directory_id,
            auth()->id()
        );

        return redirect()->back()->with('success', 'File uploaded');
    }

    public function show(string $id)
    {
        $file = $this->fileService->getById($id);

        if (!$file) {
            abort(404);
        }

        return inertia('Files/Show', [
            'file' => $file,
        ]);
    }
}
```

### 13.7 Contoh Penggunaan di Repository

```php
<?php

namespace App\Repositories;

use App\Models\File;
use App\Repositories\Interfaces\FileRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class FileRepository implements FileRepositoryInterface
{
    public function create(array $data): File
    {
        return File::create($data);
    }

    public function findById(string $id): ?File
    {
        return File::find($id);
    }



    public function getUserFiles(string $userId, ?string $directoryId = null): LengthAwarePaginator
    {
        return File::where('user_id', $userId)
            ->when($directoryId, fn ($q) => $q->where('directory_id', $directoryId))
            ->latest()
            ->paginate(20);
    }

    public function update(string $id, array $data): File
    {
        $file = File::findOrFail($id);
        $file->update($data);
        return $file->fresh();
    }

    public function delete(string $id): bool
    {
        return File::where('id', $id)->delete() > 0;
    }

    public function incrementViews(string $id): void
    {
        File::where('id', $id)->increment('views');
    }

    public function incrementDownloads(string $id): void
    {
        File::where('id', $id)->increment('downloads');
    }

    public function search(string $userId, string $query): LengthAwarePaginator
    {
        return File::where('user_id', $userId)
            ->where('name', 'LIKE', "%{$query}%")
            ->latest()
            ->paginate(20);
    }
}
```



---

## 14. Storage Quota Enforcement

### 14.1 Field di profiles table

| Field | Type | Default | Fungsi |
|-------|------|---------|--------|
| storage_limit | bigint | NULL | Batas storage personal (bytes). NULL = unlimited |

**Contoh nilai:**

| Nilai | Artinya |
|-------|---------|
| NULL | Unlimited |
| 1073741824 | 1 GB |
| 5368709120 | 5 GB |
| 10737418240 | 10 GB |
| 53687091200 | 50 GB |
| 107374182400 | 100 GB |

### 14.2 Logic Pengecekan Quota

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\FileRepositoryInterface;
use Illuminate\Http\UploadedFile;

class FileService implements FileServiceInterface
{
    public function __construct(
        private FileRepositoryInterface $fileRepository
    ) {}

    public function store(UploadedFile $file, ?string $directoryId, string $userId, ?string $diskName = null): File
    {
        // 1. Cek quota user
        $this->checkQuota($userId, $file->getSize());

        // 2. Upload file
        $fileModel = $this->fileRepository->create([
            'user_id' => $userId,
            'directory_id' => $directoryId,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),

        ]);

        $fileModel->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->toMediaCollection('default');

        return $fileModel;
    }

    /**
     * Cek apakah user masih punya quota
     */
    private function checkQuota(string $userId, int $fileSize): void
    {
        $user = User::find($userId);

        // Jika unlimited (NULL), skip check
        if (is_null($user->profile?->storage_limit)) {
            return;
        }

        $currentUsage = $this->getUserStorageUsage($userId);
        $limit = $user->profile->storage_limit;

        if (($currentUsage + $fileSize) > $limit) {
            $remaining = $limit - $currentUsage;
            $remainingMB = round($remaining / 1024 / 1024, 2);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            throw new \App\Exceptions\StorageQuotaExceededException(
                "Storage quota exceeded. Remaining: {$remainingMB} MB, File size: {$fileSizeMB} MB"
            );
        }
    }

    /**
     * Hitung total storage yang digunakan user
     */
    private function getUserStorageUsage(string $userId): int
    {
        return \App\Models\File::where('user_id', $userId)
            ->join('media', 'files.id', '=', 'mediable_id')
            ->where('mediable_type', '\App\Models\File')
            ->sum('media.size');
    }
}
```

### 14.3 Custom Exception

```php
<?php

namespace App\Exceptions;

use Exception;

class StorageQuotaExceededException extends Exception
{
    protected $message = 'Storage quota exceeded';
    protected $code = 413;

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Storage quota exceeded',
                'message' => $this->getMessage(),
            ], 413);
        }

        return redirect()->back()->withErrors([
            'storage' => $this->getMessage(),
        ]);
    }
}
```

### 14.4 Quota Check di Upload Component

```vue
<!-- resources/js/components/UploadModal.vue -->
<template>
  <div>
    <!-- Quota Progress Bar -->
    <div v-if="user.storage_limit" class="mb-4">
      <div class="flex justify-between text-sm text-gray-600 mb-1">
        <span>Storage Used</span>
        <span>{{ formatBytes(user.storage_used) }} / {{ formatBytes(user.storage_limit) }}</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="h-2 rounded-full"
          :class="quotaPercentage > 90 ? 'bg-red-500' : quotaPercentage > 70 ? 'bg-yellow-500' : 'bg-green-500'"
          :style="{ width: quotaPercentage + '%' }"
        ></div>
      </div>
      <p v-if="quotaPercentage > 90" class="text-red-500 text-xs mt-1">
        Warning: Storage almost full!
      </p>
    </div>

    <!-- Upload Zone -->
    <div class="border-2 border-dashed rounded-lg p-8 text-center">
      <!-- ... upload zone ... -->
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  user: Object,
})

const quotaPercentage = computed(() => {
  if (!props.user.storage_limit) return 0
  return (props.user.storage_used / props.user.storage_limit) * 100
})

const formatBytes = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>
```

### 14.5 Admin: Set User Quota

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Interfaces\AdminServiceInterface;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(
        private AdminServiceInterface $adminService
    ) {}

    public function updateQuota(Request $request, string $userId)
    {
        $request->validate([
            'storage_limit' => 'nullable|integer|min:0',
        ]);

        $user = User::findOrFail($userId);

        // Update atau buat profile
        $user->profile()->updateOrCreate(
            ['user_id' => $userId],
            ['storage_limit' => $request->storage_limit]
        );

        return redirect()->back()->with('success', 'Quota updated');
    }
}
```

### 14.6 Migration: Add storage_limit to profiles

```php
Schema::table('profiles', function (Blueprint $table) {
    $table->unsignedBigInteger('storage_limit')->nullable()->after('bio');
});
```

### 14.7 Default Quota Config

```php
// config/filehosting.php
return [
    'storage' => [
        'default_limit' => env('FILEHOSTING_DEFAULT_QUOTA', 10737418240), // 10 GB
        'admin_limit' => null, // Unlimited for admin
        'warn_percentage' => 90, // Warn when 90% full
    ],
];
```

