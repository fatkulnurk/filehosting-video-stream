# Storage Packages

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


---
[← Back to Main PRD](../PRD.md)
