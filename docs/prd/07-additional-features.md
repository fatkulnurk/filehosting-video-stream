# Additional Features

## 1. Storage Packages (Admin Configurable)

### Concept

Admin bisa bikin package storage di database, user pilih package yang cocok.

### Database Schema

```php
Schema::create('storage_packages', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name'); // Free, Basic, Pro, Enterprise
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->unsignedBigInteger('storage_limit'); // bytes
    $table->unsignedBigInteger('bandwidth_limit')->nullable(); // bytes per month
    $table->unsignedInteger('file_limit')->nullable(); // max files
    $table->unsignedInteger('max_file_size')->nullable(); // max single file size
    $table->json('features'); // ['custom_thumbnail', 'password_protect', 'api_access', etc]
    $table->decimal('price_monthly', 10, 2)->default(0);
    $table->decimal('price_yearly', 10, 2)->default(0);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_default')->default(false); // default for new users
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});

Schema::create('user_subscriptions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('storage_package_id')->constrained();
    $table->string('status')->default('active'); // active, cancelled, expired
    $table->timestamp('start_date');
    $table->timestamp('end_date')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->string('payment_method')->nullable();
    $table->string('transaction_id')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'status']);
});
```

### Contoh Data

**storage_packages:**

| id | name | slug | storage_limit | max_file_size | price_monthly | features |
|----|------|------|---------------|---------------|---------------|----------|
| uuid-1 | Free | free | 1073741824 | 104857600 | 0 | [] |
| uuid-2 | Basic | basic | 10737418240 | 524288000 | 9.99 | ["custom_thumbnail"] |
| uuid-3 | Pro | pro | 53687091200 | 1073741824 | 29.99 | ["custom_thumbnail", "password_protect", "api_access", "download_limit"] |
| uuid-4 | Enterprise | enterprise | 107374182400 | 2147483648 | 99.99 | ["custom_thumbnail", "password_protect", "api_access", "download_limit", "webhook", "priority_support"] |

**user_subscriptions:**

| id | user_id | storage_package_id | status | start_date | end_date |
|----|---------|-------------------|--------|------------|----------|
| uuid-10 | user-1 | uuid-2 | active | 2026-08-01 | 2026-09-01 |
| uuid-11 | user-2 | uuid-1 | active | 2026-08-01 | NULL |

### Package Features Enum

```php
<?php

namespace App\Enums;

enum PackageFeature: string
{
    case CustomThumbnail = 'custom_thumbnail';
    case PasswordProtect = 'password_protect';
    case ApiAccess = 'api_access';
    case DownloadLimit = 'download_limit';
    case Webhook = 'webhook';
    case PrioritySupport = 'priority_support';
    case FileVersioning = 'file_versioning';
    case BatchUpload = 'batch_upload';
    case CustomEmbed = 'custom_embed';
    case Analytics = 'analytics';

    public function label(): string
    {
        return match ($this) {
            self::CustomThumbnail => 'Custom Thumbnail',
            self::PasswordProtect => 'Password Protected Links',
            self::ApiAccess => 'API Access',
            self::DownloadLimit => 'Download Limit',
            self::Webhook => 'Webhook Notifications',
            self::PrioritySupport => 'Priority Support',
            self::FileVersioning => 'File Versioning',
            self::BatchUpload => 'Batch Upload',
            self::CustomEmbed => 'Custom Embed Player',
            self::Analytics => 'Advanced Analytics',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CustomThumbnail => 'Upload custom thumbnail untuk video',
            self::PasswordProtect => 'Proteksi file dengan password',
            self::ApiAccess => 'Akses API untuk integrasi',
            self::DownloadLimit => 'Batas jumlah download per file',
            self::Webhook => 'Notifikasi via webhook',
            self::PrioritySupport => 'Prioritas support',
            self::FileVersioning => 'Simpan versi file sebelumnya',
            self::BatchUpload => 'Upload banyak file sekaligus',
            self::CustomEmbed => 'Customize embed player',
            self::Analytics => 'Analitik detail',
        };
    }
}
```

### Service Layer

```php
<?php

namespace App\Services;

use App\Models\StoragePackage;
use App\Models\UserSubscription;
use App\Repositories\Interfaces\StoragePackageRepositoryInterface;
use App\Repositories\Interfaces\UserSubscriptionRepositoryInterface;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private StoragePackageRepositoryInterface $packageRepo,
        private UserSubscriptionRepositoryInterface $subscriptionRepo
    ) {}

    /**
     * Get user's current package
     */
    public function getUserPackage(string $userId): ?StoragePackage
    {
        $subscription = $this->subscriptionRepo->getActiveSubscription($userId);

        return $subscription?->storagePackage;
    }

    /**
     * Check if user has specific feature
     */
    public function userHasFeature(string $userId, string $feature): bool
    {
        $package = $this->getUserPackage($userId);

        if (!$package) {
            return false;
        }

        return in_array($feature, $package->features);
    }

    /**
     * Get user's storage limit from package
     */
    public function getUserStorageLimit(string $userId): ?int
    {
        $package = $this->getUserPackage($userId);

        return $package?->storage_limit;
    }

    /**
     * Get user's max file size from package
     */
    public function getUserMaxFileSize(string $userId): ?int
    {
        $package = $this->getUserPackage($userId);

        return $package?->max_file_size;
    }

    /**
     * Subscribe user to package
     */
    public function subscribe(string $userId, string $packageId, ?string $paymentMethod = null): UserSubscription
    {
        // Cancel existing subscription
        $this->cancelActiveSubscription($userId);

        // Create new subscription
        return $this->subscriptionRepo->create([
            'user_id' => $userId,
            'storage_package_id' => $packageId,
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'payment_method' => $paymentMethod,
        ]);
    }

    /**
     * Cancel active subscription
     */
    public function cancelActiveSubscription(string $userId): void
    {
        $this->subscriptionRepo->cancelActive($userId);
    }

    /**
     * Check if user can upload file
     */
    public function canUpload(string $userId, int $fileSize): bool
    {
        $maxFileSize = $this->getUserMaxFileSize($userId);

        if ($maxFileSize && $fileSize > $maxFileSize) {
            return false;
        }

        $storageLimit = $this->getUserStorageLimit($userId);

        if ($storageLimit) {
            $currentUsage = $this->getUserStorageUsage($userId);

            if (($currentUsage + $fileSize) > $storageLimit) {
                return false;
            }
        }

        return true;
    }
}
```

### Admin Panel: Manage Packages

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoragePackageRequest;
use App\Services\Interfaces\StoragePackageServiceInterface;
use Illuminate\Http\Request;

class AdminStoragePackageController extends Controller
{
    public function __construct(
        private StoragePackageServiceInterface $packageService
    ) {}

    public function index()
    {
        $packages = $this->packageService->getAll();

        return inertia('Admin/Packages/Index', [
            'packages' => $packages,
        ]);
    }

    public function store(StoragePackageRequest $request)
    {
        $package = $this->packageService->create($request->validated());

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package created');
    }

    public function update(StoragePackageRequest $request, string $id)
    {
        $package = $this->packageService->update($id, $request->validated());

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package updated');
    }

    public function destroy(string $id)
    {
        $this->packageService->delete($id);

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package deleted');
    }
}
```

---

## 2. Password Protected Links

### Database

```php
Schema::table('files', function (Blueprint $table) {
    $table->string('password')->nullable(); // hashed password
    $table->unsignedInteger('download_limit')->nullable(); // max downloads
    $table->unsignedInteger('download_count')->default(0);
});
```

### Service

```php
public function accessProtectedFile(string $code, ?string $password): File
{
    $file = $this->fileRepository->findByCode($code);

    if (!$file) {
        abort(404);
    }

    // Check password
    if ($file->password && !Hash::check($password, $file->password)) {
        abort(403, 'Invalid password');
    }

    // Check download limit
    if ($file->download_limit && $file->download_count >= $file->download_limit) {
        abort(410, 'Download limit reached');
    }

    return $file;
}
```

---

## 3. File Versioning

### Database

```php
Schema::create('file_versions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('version'); // 1, 2, 3...
    $table->string('name');
    $table->text('path');
    $table->string('disk');
    $table->unsignedBigInteger('size');
    $table->string('checksum')->nullable();
    $table->text('changelog')->nullable();
    $table->foreignUuid('uploaded_by')->constrained('users');
    $table->timestamps();
    $table->unique(['file_id', 'version']);
});
```

### Flow

1. User upload new version
2. System create file_version record (version + 1)
3. System copy old media to version collection
4. System update file's main media
5. User can restore old version

---

## 4. Trash / Recycle Bin

### Database

```php
Schema::table('files', function (Blueprint $table) {
    $table->softDeletes();
    $table->timestamp('deleted_at')->nullable();
});

Schema::table('directories', function (Blueprint $table) {
    $table->softDeletes();
    $table->timestamp('deleted_at')->nullable();
});
```

### Flow

1. User delete file
2. File soft deleted (deleted_at set)
3. File hidden from normal view
4. User can view trash
5. User can restore or permanent delete
6. After 30 days, auto permanent delete (schedule)

---

## 5. Activity Logs

### Database

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('action'); // login, upload, delete, download, etc
    $table->string('subject_type')->nullable();
    $table->uuid('subject_id')->nullable();
    $table->json('properties')->nullable(); // additional data
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'action']);
    $table->index(['subject_type', 'subject_id']);
});
```

### Contoh Data

| id | user_id | action | subject_type | subject_id | properties |
|----|---------|--------|--------------|------------|------------|
| uuid | user-1 | login | - | - | {"method": "email"} |
| uuid | user-1 | file.upload | File | file-1 | {"name": "video.mp4", "size": 104857600} |
| uuid | user-1 | file.download | File | file-1 | {"code": "abc123"} |
| uuid | user-1 | directory.create | Directory | dir-1 | {"name": "Videos"} |

---

## 6. Webhook Notifications

### Database

```php
Schema::create('webhooks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('url');
    $table->string('secret')->nullable();
    $table->json('events'); // ['file.uploaded', 'file.downloaded', 'storage.exceeded']
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_triggered_at')->nullable();
    $table->unsignedInteger('failure_count')->default(0);
    $table->timestamps();
});

Schema::create('webhook_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('webhook_id')->constrained()->cascadeOnDelete();
    $table->string('event');
    $table->json('payload');
    $table->integer('response_code')->nullable();
    $table->text('response_body')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->boolean('success')->default(false);
    $table->timestamps();
});
```

### Events

| Event | Description | Payload |
|-------|-------------|---------|
| file.uploaded | File berhasil diupload | file_id, name, size |
| file.downloaded | File didownload | file_id, code, ip |
| file.deleted | File dihapus | file_id, name |
| directory.created | Folder dibuat | directory_id, name |
| directory.deleted | Folder dihapus | directory_id, name |
| storage.exceeded | Quota hampir habis | user_id, usage, limit |
| subscription.expired | Subscription expired | user_id, package |
| file.expired | File expired | file_id, code |

---

## 7. API Keys

### Database

```php
Schema::create('api_keys', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('name'); // "My App", "CI/CD Pipeline"
    $table->string('key', 64)->unique();
    $table->text('permissions')->nullable(); // json array
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index('key');
});
```

### Service

```php
public function createApiKey(string $userId, string $name, ?array $permissions = null): ApiKey
{
    return ApiKey::create([
        'user_id' => $userId,
        'name' => $name,
        'key' => 'sk_' . bin2hex(random_bytes(32)),
        'permissions' => $permissions ?? ['file.read', 'file.write'],
        'expires_at' => now()->addYear(),
    ]);
}

public function validateApiKey(string $key): ?User
{
    $apiKey = ApiKey::where('key', $key)
        ->where('is_active', true)
        ->where('expires_at', '>', now())
        ->first();

    if (!$apiKey) {
        return null;
    }

    $apiKey->update(['last_used_at' => now()]);

    return $apiKey->user;
}
```

---

## 8. Batch Operations

### Upload

```php
// Multiple file upload
public function storeBatch(Request $request, FileServiceInterface $fileService)
{
    $files = $request->file('files');
    $results = [];

    foreach ($files as $file) {
        try {
            $fileModel = $fileService->store(
                $file,
                $request->directory_id,
                auth()->id()
            );
            $results[] = ['success' => true, 'file' => $fileModel];
        } catch (\Exception $e) {
            $results[] = ['success' => false, 'error' => $e->getMessage()];
        }
    }

    return response()->json($results);
}
```

### Download (Zip)

```php
public function downloadBatch(Request $request)
{
    $fileIds = $request->input('file_ids');
    $files = File::whereIn('id', $fileIds)->get();

    $zip = new ZipArchive();
    $zip->open(storage_path('app/batch-' . time() . '.zip'), ZipArchive::CREATE);

    foreach ($files as $file) {
        $media = $file->getFirstMedia();
        if ($media) {
            $zip->addFile($media->getPath(), $file->name . '.' . $file->extension);
        }
    }

    $zip->close();

    return response()->download(storage_path('app/batch-' . time() . '.zip'))
        ->deleteFileAfterSend();
}
```

---

## 9. Summary: Additional Features

| Feature | Priority | Complexity | Package Required |
|---------|----------|------------|------------------|
| Storage Packages | High | Medium | - |
| Password Protected Links | High | Low | Pro |
| Download Limit | Medium | Low | Pro |
| File Versioning | Medium | High | Enterprise |
| Trash / Recycle Bin | High | Medium | All |
| Activity Logs | Medium | Medium | Pro |
| Webhook Notifications | Medium | High | Enterprise |
| API Keys | High | Medium | Pro |
| Batch Upload | Medium | Medium | Enterprise |
| Batch Download | Low | Medium | Enterprise |

