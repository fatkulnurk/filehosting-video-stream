# Improvements & Bug Fixes

## 1. API Key Rate Limiting (#14)

### Problem
API Key tidak ada rate limiting per-key, user bisa spam API calls.

### Solution
Tambah rate limiting per API key di middleware.

### Database

```php
Schema::create('api_keys', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('name'); // "My App", "CI/CD Pipeline"
    $table->string('key', 64)->unique();
    $table->json('permissions')->nullable();
    $table->unsignedInteger('rate_limit')->default(60); // requests per minute
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->index('key');
});
```

### Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use Illuminate\Support\Facades\RateLimiter;

class ApiKeyRateLimiter
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return $next($request);
        }

        $key = ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (!$key) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Rate limit per API key
        $rateLimitKey = 'api_key:' . $key->id;
        $maxAttempts = $key->rate_limit; // requests per minute

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60); // 60 seconds window

        // Update last used
        $key->update(['last_used_at' => now()]);

        // Add rate limit headers
        $request->attributes->set('api_key', $key);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($rateLimitKey, $maxAttempts),
        ]);
    }
}
```

### Register Middleware

```php
// bootstrap/app.php or Kernel.php
\$routeMiddleware->pipeThrough([
    // ...
    \App\Http\Middleware\ApiKeyRateLimiter::class,
]);
```

### Contoh Response Headers

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
Retry-After: 30
```

---

## 2. Webhook Secret Hash (#15)

### Problem
Webhook secret disimpan plaintext di database, tidak aman.

### Solution
Hash webhook secret seperti password, verifikasi pakai hash.

### Database

```php
Schema::create('webhooks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->string('url');
    $table->string('secret_hash')->nullable(); // hashed secret
    $table->json('events');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_triggered_at')->nullable();
    $table->unsignedInteger('failure_count')->default(0);
    $table->timestamps();
});
```

### Service

```php
<?php

namespace App\Services;

use App\Models\Webhook;
use App\Repositories\Interfaces\WebhookRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class WebhookService implements WebhookServiceInterface
{
    public function __construct(
        private WebhookRepositoryInterface $webhookRepo
    ) {}

    /**
     * Create webhook with plain text secret, store hash
     */
    public function create(array $data): Webhook
    {
        $plainSecret = $data['secret'] ?? Str::random(32);

        $webhook = $this->webhookRepo->create([
            'user_id' => $data['user_id'],
            'url' => $data['url'],
            'secret_hash' => Hash::make($plainSecret),
            'events' => $data['events'],
        ]);

        // Return plain secret only on creation (one time only)
        $webhook->plain_secret = $plainSecret;

        return $webhook;
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(Webhook $webhook, string $payload, string $signature): bool
    {
        if (!$webhook->secret_hash) {
            return true; // No secret, skip verification
        }

        $expected = hash_hmac('sha256', $payload, $webhook->secret_hash);

        return hash_equals($expected, $signature);
    }

    /**
     * Regenerate webhook secret
     */
    public function regenerateSecret(Webhook $webhook): string
    {
        $plainSecret = Str::random(32);

        $webhook->update([
            'secret_hash' => Hash::make($plainSecret),
        ]);

        return $plainSecret; // Return plain text once
    }
}
```

### Usage di Webhook Call

```php
// Saat kirim webhook
$payload = json_encode($data);
$signature = hash_hmac('sha256', $payload, $webhook->secret_hash);

Http::withHeaders([
    'X-Webhook-Signature' => $signature,
    'X-Webhook-Event' => $event,
])->post($webhook->url, $data);

// Saat terima webhook (verification)
$signature = $request->header('X-Webhook-Signature');
$payload = $request->getContent();

if (!$webhookService->verifySignature($webhook, $payload, $signature)) {
    abort(403, 'Invalid signature');
}
```

---

## 3. StorageService Config Isolation (#6)

### Problem
`Config::set()` mutate global config, bisa side effect saat testing.

### Solution
Register disk di ServiceProvider boot, isolate per-request.

### Before

```php
public function registerDisk(StorageServer $server): void
{
    // BAD: Mutates global config
    Config::set("filesystems.disks.{$server->name}", $config);
}
```

### After

```php
<?php

namespace App\Services;

use App\Models\StorageServer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class StorageService implements StorageServiceInterface
{
    /**
     * Register disk - cache config to avoid repeated Config::set()
     */
    public function registerDisk(StorageServer $server): void
    {
        $cacheKey = "disk_config:{$server->id}";

        // Check if already registered in this request
        if (Cache::has($cacheKey)) {
            return;
        }

        $config = $this->buildDiskConfig($server);

        Config::set("filesystems.disks.{$server->name}", $config);

        // Cache for this request only (doesn't persist)
        Cache::put($cacheKey, true, now()->addSeconds(1));
    }

    /**
     * Build disk config from StorageServer model
     */
    private function buildDiskConfig(StorageServer $server): array
    {
        $driver = $server->driver;

        return match ($driver) {
            'local' => [
                'driver' => 'local',
                'root' => $server->config['root'] ?? storage_path("app/{$server->name}"),
            ],
            's3' => [
                'driver' => 's3',
                'key' => $server->config['key'],
                'secret' => $server->config['secret'],
                'region' => $server->config['region'],
                'bucket' => $server->config['bucket'],
                'endpoint' => $server->config['endpoint'] ?? null,
            ],
            'dropbox' => [
                'driver' => 'dropbox',
                'token' => $server->config['token'],
            ],
            // ... other drivers
            default => throw new \InvalidArgumentException("Unsupported driver: {$driver}"),
        };
    }

    /**
     * Register all active disks
     */
    public function registerAllDisks(): void
    {
        StorageServer::where('is_active', true)
            ->each(fn ($server) => $this->registerDisk($server));
    }
}
```

### Register in ServiceProvider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\StorageService;

class StorageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register all active storage disks on boot
        $this->app->booted(function () {
            $storageService = app(StorageService::class);
            $storageService->registerAllDisks();
        });
    }
}
```

---

## 4. StorageDriver Config-Driven (#7)

### Problem
StorageDriver enum hardcoded, jika ada driver baru harus edit enum.

### Solution
Database-driven driver config, atau hybrid approach.

### Option A: Hybrid (Recommended)

Tetap pakai enum untuk type safety, tapi tambah driver config di database:

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
    case Rackspace = 'rackspace';
    case Wasabi = 'wasabi';
    case B2 = 'b2';

    /**
     * Check if driver is supported
     */
    public static function isSupported(string $driver): bool
    {
        return in_array($driver, array_column(self::cases(), 'value'));
    }

    /**
     * Get all supported drivers
     */
    public static function supported(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Option B: Fully Config-Driven (Advanced)

```php
Schema::create('storage_drivers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name'); // "Amazon S3"
    $table->string('driver')->unique(); // "s3"
    $table->string('package'); // "league/flysystem-aws-s3-v3"
    $table->json('config_fields'); // ["key", "secret", "region", "bucket"]
    $table->json('optional_fields')->nullable(); // ["endpoint", "use_path_style"]
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

```php
// Service loads drivers from database
public function getSupportedDrivers(): Collection
{
    return StorageDriver::where('is_active', true)->get();
}

public function validateConfig(string $driver, array $config): bool
{
    $driver = StorageDriver::where('driver', $driver)->first();

    $required = $driver->config_fields;
    $optional = $driver->optional_fields ?? [];

    foreach ($required as $field) {
        if (!isset($config[$field])) {
            return false;
        }
    }

    return true;
}
```

---

## 5. Spatie move() Verify Checksum (#8)

### Problem
Spatie `move()` tidak update custom_properties, checksum tetap lama.

### Solution
Verify checksum setelah move.

### Service

```php
<?php

namespace App\Services;

use App\Models\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FileMigrationService implements FileMigrationServiceInterface
{
    /**
     * Move file to another disk with checksum verification
     */
    public function moveFile(File $file, string $targetDisk): Media
    {
        $media = $file->getFirstMedia();

        if (!$media) {
            throw new \Exception('File has no media');
        }

        // Get old checksum
        $oldChecksum = $media->getCustomProperty('checksum');

        // Move file
        $newMedia = $media->move($targetDisk, $media->path);

        // Verify checksum
        $newPath = $newMedia->getPath();
        $newChecksum = hash_file('sha256', $newPath);

        if ($oldChecksum && $oldChecksum !== $newChecksum) {
            // Rollback: move back
            $newMedia->move($media->disk, $media->path);

            throw new \Exception('Checksum mismatch after move');
        }

        // Update custom properties
        $newMedia->updateCustomProperties([
            'checksum' => $newChecksum,
            'migrated_at' => now(),
            'migrated_from' => $media->disk,
        ]);

        return $newMedia;
    }

    /**
     * Copy file to another disk with checksum verification
     */
    public function copyFile(File $file, string $targetDisk): Media
    {
        $media = $file->getFirstMedia();

        if (!$media) {
            throw new \Exception('File has no media');
        }

        // Copy file
        $newMedia = $media->copy($targetDisk, $media->path);

        // Verify checksum
        $oldPath = $media->getPath();
        $newPath = $newMedia->getPath();

        $oldChecksum = hash_file('sha256', $oldPath);
        $newChecksum = hash_file('sha256', $newPath);

        if ($oldChecksum !== $newChecksum) {
            // Delete copy
            $newMedia->delete();

            throw new \Exception('Checksum mismatch after copy');
        }

        // Update custom properties
        $newMedia->updateCustomProperties([
            'checksum' => $newChecksum,
            'copied_at' => now(),
            'copied_from' => $media->disk,
        ]);

        return $newMedia;
    }
}
```

---

## 6. Private File Sharing (#9)

### Problem
File hanya public atau private (owner only), tidak bisa share ke user tertentu.

### Solution
Tambah tabel `file_shares` untuk private sharing.

### Database

```php
Schema::create('file_shares', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('shared_by')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('shared_with')->constrained('users')->cascadeOnDelete();
    $table->string('permission')->default('view'); // view, download
    $table->string('password')->nullable(); // hashed password
    $table->timestamp('expires_at')->nullable();
    $table->unsignedInteger('download_limit')->nullable();
    $table->unsignedInteger('download_count')->default(0);
    $table->timestamps();
    $table->unique(['file_id', 'shared_with']);
    $table->index('shared_with');
});
```

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| permission | view, download | Default: view |

**Contoh Data:**

| id | file_id | shared_by | shared_with | permission | expires_at |
|----|---------|-----------|-------------|------------|------------|
| uuid-1 | file-1 | user-1 | user-2 | download | 2026-08-31 |
| uuid-2 | file-1 | user-1 | user-3 | view | NULL |

### Service

```php
<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileShare;
use App\Repositories\Interfaces\FileShareRepositoryInterface;

class FileShareService implements FileShareServiceInterface
{
    public function __construct(
        private FileShareRepositoryInterface $shareRepo
    ) {}

    /**
     * Share file to user
     */
    public function share(
        File $file,
        string $sharedBy,
        string $sharedWith,
        string $permission = 'view',
        ?string $password = null,
        ?string $expiresAt = null
    ): FileShare {
        return $this->shareRepo->create([
            'file_id' => $file->id,
            'shared_by' => $sharedBy,
            'shared_with' => $sharedWith,
            'permission' => $permission,
            'password' => $password ? \Hash::make($password) : null,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Get files shared with user
     */
    public function getSharedWithMe(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return FileShare::where('shared_with', $userId)
            ->where('expires_at', '>', now())
            ->orWhereNull('expires_at')
            ->with('file.media')
            ->get();
    }

    /**
     * Get files I shared to others
     */
    public function getSharedByMe(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return FileShare::where('shared_by', $userId)
            ->with('file.media', 'sharedWithUser')
            ->get();
    }

    /**
     * Check if user can access file
     */
    public function canAccess(File $file, string $userId): bool
    {
        // Owner can always access
        if ($file->user_id === $userId) {
            return true;
        }

        // Public file
        if ($file->is_public) {
            return true;
        }

        // Check share
        $share = FileShare::where('file_id', $file->id)
            ->where('shared_with', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        return $share !== null;
    }

    /**
     * Check if user can download file
     */
    public function canDownload(File $file, string $userId): bool
    {
        // Owner can always download
        if ($file->user_id === $userId) {
            return true;
        }

        // Check share with download permission
        $share = FileShare::where('file_id', $file->id)
            ->where('shared_with', $userId)
            ->where('permission', 'download')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('download_limit')
                  ->orWhereColumn('download_count', '<', 'download_limit');
            })
            ->first();

        return $share !== null;
    }

    /**
     * Remove share
     */
    public function removeShare(string $shareId): bool
    {
        return $this->shareRepo->delete($shareId);
    }
}
```

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareFileRequest;
use App\Models\File;
use App\Services\Interfaces\FileShareServiceInterface;

class FileShareController extends Controller
{
    public function __construct(
        private FileShareServiceInterface $shareService
    ) {}

    public function store(ShareFileRequest $request, File $file)
    {
        $share = $this->shareService->share(
            $file,
            auth()->id(),
            $request->shared_with,
            $request->permission,
            $request->password,
            $request->expires_at
        );

        return redirect()->back()->with('success', 'File shared');
    }

    public function sharedWithMe()
    {
        $shares = $this->shareService->getSharedWithMe(auth()->id());

        return inertia('Files/SharedWithMe', [
            'shares' => $shares,
        ]);
    }

    public function sharedByMe()
    {
        $shares = $this->shareService->getSharedByMe(auth()->id());

        return inertia('Files/SharedByMe', [
            'shares' => $shares,
        ]);
    }

    public function destroy(string $shareId)
    {
        $this->shareService->removeShare($shareId);

        return redirect()->back()->with('success', 'Share removed');
    }
}
```

### Routes

```php
Route::middleware('auth')->group(function () {
    // ... other routes

    Route::post('/files/{file}/share', [FileShareController::class, 'store'])
        ->name('files.share');
    Route::delete('/shares/{share}', [FileShareController::class, 'destroy'])
        ->name('shares.destroy');
    Route::get('/shared-with-me', [FileShareController::class, 'sharedWithMe'])
        ->name('files.shared-with-me');
    Route::get('/shared-by-me', [FileShareController::class, 'sharedByMe'])
        ->name('files.shared-by-me');
});
```

---

## 7. Folder Sharing / Collaboration (#10)

### Problem
Folder hanya bisa diakses owner, tidak bisa collaborate.

### Solution
Tambah tabel `directory_shares` untuk folder sharing.

### Database

```php
Schema::create('directory_shares', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('directory_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('shared_by')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('shared_with')->constrained('users')->cascadeOnDelete();
    $table->string('permission')->default('view'); // view, upload, manage
    $table->boolean('recursive')->default(true); // apply to subdirectories
    $table->timestamps();
    $table->unique(['directory_id', 'shared_with']);
    $table->index('shared_with');
});
```

**Enum Values:**

| Field | Values | Keterangan |
|-------|--------|------------|
| permission | view, upload, manage | Default: view |
| recursive | true, false | Default: true |

**Contoh Data:**

| id | directory_id | shared_by | shared_with | permission | recursive |
|----|--------------|-----------|-------------|------------|-----------|
| uuid-1 | dir-1 | user-1 | user-2 | upload | true |
| uuid-2 | dir-1 | user-1 | user-3 | view | false |

### Permission Levels

| Permission | View Files | Upload Files | Delete Files | Create Subdirs | Manage Shares |
|------------|------------|--------------|--------------|----------------|---------------|
| view | Ya | Tidak | Tidak | Tidak | Tidak |
| upload | Ya | Ya | Tidak | Tidak | Tidak |
| manage | Ya | Ya | Ya | Ya | Ya |

### Service

```php
<?php

namespace App\Services;

use App\Models\Directory;
use App\Models\DirectoryShare;
use App\Repositories\Interfaces\DirectoryShareRepositoryInterface;

class DirectoryShareService implements DirectoryShareServiceInterface
{
    public function __construct(
        private DirectoryShareRepositoryInterface $shareRepo
    ) {}

    /**
     * Share directory to user
     */
    public function share(
        Directory $directory,
        string $sharedBy,
        string $sharedWith,
        string $permission = 'view',
        bool $recursive = true
    ): DirectoryShare {
        return $this->shareRepo->create([
            'directory_id' => $directory->id,
            'shared_by' => $sharedBy,
            'shared_with' => $sharedWith,
            'permission' => $permission,
            'recursive' => $recursive,
        ]);
    }

    /**
     * Get directories shared with user
     */
    public function getSharedWithMe(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return DirectoryShare::where('shared_with', $userId)
            ->with(['directory.files', 'directory.children'])
            ->get();
    }

    /**
     * Check if user can access directory
     */
    public function canAccess(Directory $directory, string $userId): bool
    {
        // Owner can always access
        if ($directory->user_id === $userId) {
            return true;
        }

        // Check direct share
        $share = DirectoryShare::where('directory_id', $directory->id)
            ->where('shared_with', $userId)
            ->first();

        if ($share) {
            return true;
        }

        // Check parent share (recursive)
        if ($directory->parent_id) {
            return $this->canAccess($directory->parent, $userId);
        }

        return false;
    }

    /**
     * Check if user can upload to directory
     */
    public function canUpload(Directory $directory, string $userId): bool
    {
        // Owner can always upload
        if ($directory->user_id === $userId) {
            return true;
        }

        // Check share with upload/manage permission
        $share = DirectoryShare::where('directory_id', $directory->id)
            ->where('shared_with', $userId)
            ->whereIn('permission', ['upload', 'manage'])
            ->first();

        if ($share) {
            return true;
        }

        // Check parent share (recursive)
        if ($directory->parent_id) {
            return $this->canUpload($directory->parent, $userId);
        }

        return false;
    }

    /**
     * Check if user can manage directory
     */
    public function canManage(Directory $directory, string $userId): bool
    {
        // Owner can always manage
        if ($directory->user_id === $userId) {
            return true;
        }

        // Check share with manage permission
        $share = DirectoryShare::where('directory_id', $directory->id)
            ->where('shared_with', $userId)
            ->where('permission', 'manage')
            ->first();

        return $share !== null;
    }

    /**
     * Get all directories user can access
     */
    public function getAccessibleDirectories(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        // Own directories
        $own = Directory::where('user_id', $userId)
            ->whereNull('parent_id')
            ->get();

        // Shared directories
        $shared = DirectoryShare::where('shared_with', $userId)
            ->with('directory')
            ->get()
            ->pluck('directory')
            ->filter(fn ($dir) => $dir->parent_id === null);

        return $own->concat($shared)->unique('id');
    }
}
```

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShareDirectoryRequest;
use App\Models\Directory;
use App\Services\Interfaces\DirectoryShareServiceInterface;

class DirectoryShareController extends Controller
{
    public function __construct(
        private DirectoryShareServiceInterface $shareService
    ) {}

    public function store(ShareDirectoryRequest $request, Directory $directory)
    {
        $share = $this->shareService->share(
            $directory,
            auth()->id(),
            $request->shared_with,
            $request->permission,
            $request->boolean('recursive', true)
        );

        return redirect()->back()->with('success', 'Directory shared');
    }

    public function sharedWithMe()
    {
        $shares = $this->shareService->getSharedWithMe(auth()->id());

        return inertia('Directories/SharedWithMe', [
            'shares' => $shares,
        ]);
    }

    public function destroy(string $shareId)
    {
        $this->shareService->removeShare($shareId);

        return redirect()->back()->with('success', 'Share removed');
    }
}
```

### Routes

```php
Route::middleware('auth')->group(function () {
    // ... other routes

    Route::post('/directories/{directory}/share', [DirectoryShareController::class, 'store'])
        ->name('directories.share');
    Route::delete('/directory-shares/{share}', [DirectoryShareController::class, 'destroy'])
        ->name('directory-shares.destroy');
    Route::get('/directories/shared-with-me', [DirectoryShareController::class, 'sharedWithMe'])
        ->name('directories.shared-with-me');
});
```

---

## Summary

| # | Issue | Solution | Status |
|---|-------|----------|--------|
| 6 | StorageService Config::set() global | Cache per-request + register in boot | Done |
| 7 | StorageDriver hardcoded enum | Hybrid: enum + database config | Done |
| 8 | Spatie move() checksum | Verify checksum after move/copy | Done |
| 9 | Private file sharing | file_shares table + permission | Done |
| 10 | Folder sharing/collaboration | directory_shares table + permission levels | Done |
| 14 | API Key rate limiting | RateLimiter per key + headers | Done |
| 15 | Webhook secret plaintext | Hash secret, verify signature | Done |



---

## 8. Quota Check Optimized (#16)

### Problem
Query `File::where('user_id', $userId)->sum('size')` lambat jika user banyak file.

### Solution
Cache storage usage di profile table, update on upload/delete.

### Database Update

```php
Schema::table('profiles', function (Blueprint $table) {
    $table->unsignedBigInteger('storage_used')->default(0); // cached usage
    $table->timestamp('storage_used_at')->nullable(); // last calculated
});
```

### Service

```php
<?php

namespace App\Services;

use App\Models\File;
use App\Models\Profile;
use Illuminate\Support\Facades\Cache;

class StorageQuotaService implements StorageQuotaServiceInterface
{
    /**
     * Get user storage usage (cached)
     */
    public function getUsage(string $userId): int
    {
        return Cache::remember("storage_usage:{$userId}", 300, function () use ($userId) {
            return $this->calculateUsage($userId);
        });
    }

    /**
     * Calculate actual usage from files + media
     */
    private function calculateUsage(string $userId): int
    {
        $usage = File::where('user_id', $userId)
            ->join('media', function ($join) {
                $join->on('files.id', '=', 'mediable_id')
                     ->where('mediable_type', '=', 'App\\Models\\File');
            })
            ->sum('media.size');

        // Update cached value in profile
        Profile::where('user_id', $userId)->update([
            'storage_used' => $usage,
            'storage_used_at' => now(),
        ]);

        return $usage;
    }

    /**
     * Check if user can upload file
     */
    public function canUpload(string $userId, int $fileSize): bool
    {
        $profile = Profile::where('user_id', $userId)->first();

        if (!$profile || !$profile->storage_limit) {
            return true; // Unlimited
        }

        $currentUsage = $this->getUsage($userId);

        return ($currentUsage + $fileSize) <= $profile->storage_limit;
    }

    /**
     * Get remaining quota
     */
    public function getRemaining(string $userId): ?int
    {
        $profile = Profile::where('user_id', $userId)->first();

        if (!$profile || !$profile->storage_limit) {
            return null; // Unlimited
        }

        return $profile->storage_limit - $this->getUsage($userId);
    }

    /**
     * Force recalculate (after delete, etc)
     */
    public function recalculate(string $userId): int
    {
        Cache::forget("storage_usage:{$userId}");
        return $this->calculateUsage($userId);
    }
}
```

### Usage di FileService

```php
public function store(UploadedFile $file, ?string $directoryId, string $userId): File
{
    // Check quota (cached, fast)
    if (!$this->quotaService->canUpload($userId, $file->getSize())) {
        throw new StorageQuotaExceededException('Storage quota exceeded');
    }

    // Upload file...
    $fileModel = $this->fileRepository->create([...]);

    // Invalidate cache
    Cache::forget("storage_usage:{$userId}");

    return $fileModel;
}

public function delete(File $file): bool
{
    $userId = $file->user_id;

    // Delete file...
    $this->fileRepository->delete($file->id);

    // Recalculate usage
    $this->quotaService->recalculate($userId);

    return true;
}
```

---

## 9. Directory Tree N+1 Query Fix (#17)

### Problem
`with('childrenRecursive')` bisa N+1 query jika depth dalam.

### Solution 1: Eager Load with Depth Limit

```php
// Limit depth to 3 levels
Directory::where('user_id', $userId)
    ->whereNull('parent_id')
    ->with(['children' => function ($query) {
        $query->with(['children' => function ($q) {
            $q->with('children');
        }]);
    }])
    ->get();
```

### Solution 2: Adjacency List (Recommended)

Gunakan recursive query untuk ambil semua sekaligus:

```php
<?php

namespace App\Repositories;

use App\Models\Directory;
use App\Repositories\Interfaces\DirectoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DirectoryRepository implements DirectoryRepositoryInterface
{
    /**
     * Get full directory tree for user (single query)
     */
    public function getTree(string $userId): Collection
    {
        // Get all directories for user in one query
        $allDirectories = Directory::where('user_id', $userId)
            ->orderBy('path')
            ->get();

        // Build tree in memory
        return $this->buildTree($allDirectories);
    }

    /**
     * Build tree structure from flat collection
     */
    private function buildTree(Collection $directories): Collection
    {
        $grouped = $directories->groupBy('parent_id');
        $root = $grouped->get(null, collect());

        return $root->map(function ($directory) use ($grouped) {
            return $this->buildNode($directory, $grouped);
        });
    }

    /**
     * Build node with children
     */
    private function buildNode(Directory $directory, $grouped): Directory
    {
        $children = $grouped->get($directory->id, collect());

        $directory->setRelation('children', $children->map(function ($child) use ($grouped) {
            return $this->buildNode($child, $grouped);
        }));

        return $directory;
    }

    /**
     * Get breadcrumbs for directory (single query)
     */
    public function getBreadcrumbs(string $directoryId): Collection
    {
        $directory = Directory::find($directoryId);

        if (!$directory) {
            return collect();
        }

        // Parse path to get all ancestor IDs
        $pathParts = array_filter(explode('/', $directory->path));

        // Get all directories in path
        $breadcrumbs = Directory::where('user_id', $directory->user_id)
            ->whereIn('id', $pathParts)
            ->orderBy('path')
            ->get();

        return $breadcrumbs;
    }
}
```

### Solution 3: Cache Tree

```php
public function getTree(string $userId): Collection
{
    return Cache::remember("directory_tree:{$userId}", 300, function () use ($userId) {
        return $this->buildTree(
            Directory::where('user_id', $userId)
                ->orderBy('path')
                ->get()
        );
    });
}
```

---

## 10. Full-Text Search (#18)

### Problem
File search pakai `LIKE '%query%'` lambat, tidak bisa index.

### Solution: MySQL FULLTEXT Search

```php
// Migration
Schema::table('files', function (Blueprint $table) {
    $table->fullText('name'); // Add FULLTEXT index
});
```

### Repository

```php
public function search(string $userId, string $query): LengthAwarePaginator
{
    // Use FULLTEXT search
    return File::where('user_id', $userId)
        ->whereFullText('name', $query)
        ->latest()
        ->paginate(20);
}

/**
 * Advanced search with multiple criteria
 */
public function advancedSearch(string $userId, array $filters): LengthAwarePaginator
{
    return File::where('user_id', $userId)
        ->when($filters['query'] ?? null, function ($q, $query) {
            $q->whereFullText('name', $query);
        })
        ->when($filters['extension'] ?? null, function ($q, $ext) {
            // Get extension from media
            $q->whereHas('media', function ($mq) use ($ext) {
                $mq->where('file_name', 'LIKE', "%.{$ext}");
            });
        })
        ->when($filters['min_size'] ?? null, function ($q, $size) {
            $q->whereHas('media', function ($mq) use ($size) {
                $mq->where('size', '>=', $size);
            });
        })
        ->when($filters['max_size'] ?? null, function ($q, $size) {
            $q->whereHas('media', function ($mq) use ($size) {
                $mq->where('size', '<=', $size);
            });
        })
        ->when($filters['date_from'] ?? null, function ($q, $date) {
            $q->where('created_at', '>=', $date);
        })
        ->when($filters['date_to'] ?? null, function ($q, $date) {
            $q->where('created_at', '<=', $date);
        })
        ->when($filters['is_public'] ?? null, function ($q, $public) {
            $q->where('is_public', $public);
        })
        ->latest()
        ->paginate(20);
}
```

### Solution 2: Meilisearch (Advanced)

```php
// Install
composer require meilisearch/meilisearch-php

// Config
// config/meilisearch.php
return [
    'url' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    'key' => env('MEILISEARCH_KEY'),
];

// Model
use Laravel\Scout\Searchable;

class File extends Model
{
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'user_id' => $this->user_id,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at->timestamp,
        ];
    }
}

// Usage
$files = File::search($query)->where('user_id', auth()->id())->paginate(20);
```

---

## 11. Starred/Favorite Files (#19)

### Problem
User tidak bisa bookmark file favorit.

### Solution
Tambah tabel `file_favorites`.

### Database

```php
Schema::create('file_favorites', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
    $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['user_id', 'file_id']);
});
```

### Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FileFavorite extends Model
{
    use HasUuids;

    protected $table = 'file_favorites';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
```

### Service

```php
<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileFavorite;
use App\Repositories\Interfaces\FileFavoriteRepositoryInterface;

class FileFavoriteService implements FileFavoriteServiceInterface
{
    public function __construct(
        private FileFavoriteRepositoryInterface $favoriteRepo
    ) {}

    /**
     * Toggle favorite
     */
    public function toggle(string $userId, string $fileId): bool
    {
        $existing = FileFavorite::where('user_id', $userId)
            ->where('file_id', $fileId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false; // Removed from favorites
        }

        $this->favoriteRepo->create([
            'user_id' => $userId,
            'file_id' => $fileId,
        ]);

        return true; // Added to favorites
    }

    /**
     * Check if file is favorited
     */
    public function isFavorited(string $userId, string $fileId): bool
    {
        return FileFavorite::where('user_id', $userId)
            ->where('file_id', $fileId)
            ->exists();
    }

    /**
     * Get all favorites for user
     */
    public function getFavorites(string $userId): \Illuminate\Database\Eloquent\Collection
    {
        return FileFavorite::where('user_id', $userId)
            ->with('file.media')
            ->latest()
            ->get();
    }

    /**
     * Remove favorite
     */
    public function remove(string $userId, string $fileId): bool
    {
        return FileFavorite::where('user_id', $userId)
            ->where('file_id', $fileId)
            ->delete() > 0;
    }
}
```

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Interfaces\FileFavoriteServiceInterface;
use Illuminate\Http\Request;

class FileFavoriteController extends Controller
{
    public function __construct(
        private FileFavoriteServiceInterface $favoriteService
    ) {}

    public function toggle(File $file)
    {
        $isFavorited = $this->favoriteService->toggle(auth()->id(), $file->id);

        return response()->json([
            'is_favorited' => $isFavorited,
            'message' => $isFavorited ? 'Added to favorites' : 'Removed from favorites',
        ]);
    }

    public function index()
    {
        $favorites = $this->favoriteService->getFavorites(auth()->id());

        return inertia('Files/Favorites', [
            'favorites' => $favorites,
        ]);
    }
}
```

### Routes

```php
Route::middleware('auth')->group(function () {
    Route::get('/favorites', [FileFavoriteController::class, 'index'])
        ->name('files.favorites');
    Route::post('/files/{file}/favorite', [FileFavoriteController::class, 'toggle'])
        ->name('files.favorite');
});
```

### Vue Component

```vue
<template>
  <button
    @click="toggleFavorite"
    class="p-2 rounded-full hover:bg-gray-100"
    :class="{ 'text-yellow-500': isFavorited }"
  >
    <StarIcon class="w-5 h-5" :class="{ 'fill-current': isFavorited }" />
  </button>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  file: Object,
  initialIsFavorited: Boolean,
})

const isFavorited = ref(props.initialIsFavorited)

const toggleFavorite = () => {
  router.post(route('files.favorite', props.file.id), {}, {
    onSuccess: () => {
      isFavorited.value = !isFavorited.value
    },
  })
}
</script>
```

---

## 12. Recently Accessed Files (#20)

### Problem
Tidak ada tracking file yang baru diakses.

### Solution
Tambah tabel `file_access_logs`.

### Database

```php
Schema::create('file_access_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
    $table->string('action'); // view, download, embed
    $table->string('ip', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'created_at']);
    $table->index(['file_id', 'created_at']);
});
```

### Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FileAccessLog extends Model
{
    use HasUuids;

    protected $table = 'file_access_logs';

    protected $fillable = [
        'user_id',
        'file_id',
        'action',
        'ip',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
```

### Service

```php
<?php

namespace App\Services;

use App\Models\FileAccessLog;
use App\Repositories\Interfaces\FileAccessLogRepositoryInterface;

class FileAccessService implements FileAccessServiceInterface
{
    public function __construct(
        private FileAccessLogRepositoryInterface $logRepo
    ) {}

    /**
     * Log file access
     */
    public function logAccess(string $fileId, string $action, ?string $userId = null): void
    {
        $this->logRepo->create([
            'user_id' => $userId ?? auth()->id(),
            'file_id' => $fileId,
            'action' => $action,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get recently accessed files for user
     */
    public function getRecentlyAccessed(string $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return FileAccessLog::where('user_id', $userId)
            ->with('file.media')
            ->groupBy('file_id')
            ->latest()
            ->limit($limit)
            ->get()
            ->pluck('file');
    }

    /**
     * Get most accessed files (global)
     */
    public function getMostAccessed(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return FileAccessLog::select('file_id', \DB::raw('COUNT(*) as access_count'))
            ->groupBy('file_id')
            ->orderByDesc('access_count')
            ->limit($limit)
            ->with('file.media')
            ->get()
            ->pluck('file');
    }
}
```

### Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\FileAccessServiceInterface;

class FileAccessController extends Controller
{
    public function __construct(
        private FileAccessServiceInterface $accessService
    ) {}

    public function recentlyAccessed()
    {
        $files = $this->accessService->getRecentlyAccessed(auth()->id());

        return inertia('Files/RecentlyAccessed', [
            'files' => $files,
        ]);
    }
}
```

### Routes

```php
Route::middleware('auth')->group(function () {
    Route::get('/recently-accessed', [FileAccessController::class, 'recentlyAccessed'])
        ->name('files.recently-accessed');
});
```

### Usage di Controller

```php
// Saat view file
public function show(string $id)
{
    $file = $this->fileService->getById($id);

    // Log access
    $this->accessService->logAccess($id, 'view');

    return inertia('Files/Show', ['file' => $file]);
}

// Saat download
public function download(string $id)
{
    $file = $this->fileService->getById($id);

    // Log access
    $this->accessService->logAccess($id, 'download');

    return Storage::disk($file->disk)->download($file->path);
}
```

---

## 13. User Rate Limiting (#21)

### Problem
Tidak ada rate limiting per user untuk download/view.

### Solution
Rate limit per user per action.

### Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class UserActionRateLimiter
{
    /**
     * Rate limits per action
     */
    protected array $limits = [
        'download' => ['max' => 50, 'minutes' => 60],  // 50 downloads per hour
        'view' => ['max' => 200, 'minutes' => 60],      // 200 views per hour
        'upload' => ['max' => 20, 'minutes' => 60],     // 20 uploads per hour
        'share' => ['max' => 30, 'minutes' => 60],      // 30 shares per hour
    ];

    public function handle(Request $request, Closure $next, string $action = 'default')
    {
        $userId = auth()->id();

        if (!$userId) {
            return $next($request);
        }

        $limit = $this->limits[$action] ?? ['max' => 100, 'minutes' => 60];
        $rateLimitKey = "user_action:{$userId}:{$action}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit['max'])) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Rate limit exceeded',
                    'action' => $action,
                    'retry_after' => $retryAfter,
                ], 429)->withHeaders([
                    'Retry-After' => $retryAfter,
                ]);
            }

            return redirect()->back()->withErrors([
                'rate_limit' => "Too many {$action} requests. Try again in {$retryAfter} seconds.",
            ]);
        }

        RateLimiter::hit($rateLimitKey, $limit['minutes'] * 60);

        return $next($request);
    }
}
```

### Register Middleware

```php
// bootstrap/app.php
\$routeMiddleware->pipeThrough([
    // ...
]);
```

### Usage di Routes

```php
Route::middleware(['auth', 'throttle:download'])->group(function () {
    Route::post('/files/{file}/download', [FileController::class, 'download']);
});

Route::middleware(['auth', 'throttle:upload'])->group(function () {
    Route::post('/files', [FileController::class, 'store']);
});
```

### Or Manual Usage

```php
public function download(string $id)
{
    $userId = auth()->id();
    $rateLimitKey = "download:{$userId}";

    if (RateLimiter::tooManyAttempts($rateLimitKey, 50)) {
        $retryAfter = RateLimiter::availableIn($rateLimitKey);

        return redirect()->back()->withErrors([
            'download' => "Download limit reached. Try again in {$retryAfter} seconds.",
        ]);
    }

    RateLimiter::hit($rateLimitKey, 3600); // 1 hour

    // Process download...
}
```

### Response Headers

```
X-RateLimit-Limit: 50
X-RateLimit-Remaining: 45
X-RateLimit-Action: download
Retry-After: 30
```

---

## Updated Summary

| # | Issue | Solution | Status |
|---|-------|----------|--------|
| 6 | StorageService Config::set() global | Cache per-request + register in boot | Done |
| 7 | StorageDriver hardcoded enum | Hybrid: enum + database config | Done |
| 8 | Spatie move() checksum | Verify checksum after move/copy | Done |
| 9 | Private file sharing | file_shares table + permission | Done |
| 10 | Folder sharing/collaboration | directory_shares table + permission levels | Done |
| 14 | API Key rate limiting | RateLimiter per key + headers | Done |
| 15 | Webhook secret plaintext | Hash secret, verify signature | Done |
| 16 | Quota check lambat | Cache usage + profile storage_used | Done |
| 17 | N+1 query directory tree | Adjacency list + single query | Done |
| 18 | LIKE '%query%' lambat | FULLTEXT search + Meilisearch option | Done |
| 19 | Tidak ada starred/favorite | file_favorites table | Done |
| 20 | Tidak ada recently accessed | file_access_logs table | Done |
| 21 | Tidak ada rate limiting per user | UserActionRateLimiter middleware | Done |

