<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Models\Files\Directory;
use App\Models\Storage\StorageServer;
use App\Models\Subscriptions\StoragePackage;
use App\Models\Subscriptions\UserSubscription;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Nested folder structure + file specs to seed for each user.
     * Each folder key maps to a nested-array under it; files are listed
     * as ['count' => n, 'label' => 'sample-name', 'ext' => fn:pathinfo ext, 'mime' => ..., 'max_kb' => ...].
     */
    private array $demoTree = [
        'Videos' => [
            'files' => ['count' => 8, 'label' => 'video-', 'ext' => 'mp4', 'mime' => 'video/mp4', 'max_kb' => 96, 'video' => true],
            'children' => [
                'Events' => [
                    'files' => ['count' => 6, 'label' => 'event-', 'ext' => 'mp4', 'mime' => 'video/mp4', 'max_kb' => 48, 'video' => true],
                    'children' => [
                        'Wedding' => ['files' => ['count' => 5, 'label' => 'wedding-', 'ext' => 'mp4', 'mime' => 'video/mp4', 'max_kb' => 40, 'video' => true]],
                        'Concert' => ['files' => ['count' => 5, 'label' => 'concert-', 'ext' => 'mp4', 'mime' => 'video/mp4', 'max_kb' => 40, 'video' => true]],
                    ],
                ],
                'Tutorials' => [
                    'files' => ['count' => 7, 'label' => 'tutorial-', 'ext' => 'mp4', 'mime' => 'video/mp4', 'max_kb' => 56, 'video' => true],
                    'children' => [
                        'PHP' => ['files' => ['count' => 6, 'label' => 'php-', 'ext' => 'mp4', 'mime' => 'video/mp4', 'max_kb' => 32, 'video' => true]],
                        'Vue' => ['files' => ['count' => 6, 'label' => 'vue-', 'ext' => 'mp4', 'mime' => 'video/mp4', 'max_kb' => 32, 'video' => true]],
                    ],
                ],
            ],
        ],
        'Images' => [
            'files' => ['count' => 10, 'label' => 'image-', 'ext' => 'jpg', 'mime' => 'image/jpeg', 'max_kb' => 32],
            'children' => [
                'Wallpapers' => ['files' => ['count' => 8, 'label' => 'wallpaper-', 'ext' => 'jpg', 'mime' => 'image/jpeg', 'max_kb' => 48]],
                'Thumbnails' => [
                    'files' => ['count' => 5, 'label' => 'thumb-', 'ext' => 'png', 'mime' => 'image/png', 'max_kb' => 16],
                    'children' => [
                        'Brand' => ['files' => ['count' => 4, 'label' => 'brand-', 'ext' => 'png', 'mime' => 'image/png', 'max_kb' => 12]],
                    ],
                ],
            ],
        ],
        'Documents' => [
            'files' => ['count' => 10, 'label' => 'doc-', 'ext' => 'pdf', 'mime' => 'application/pdf', 'max_kb' => 24],
            'children' => [
                'Contracts' => ['files' => ['count' => 6, 'label' => 'contract-', 'ext' => 'pdf', 'mime' => 'application/pdf', 'max_kb' => 20]],
                'Reports' => [
                    'files' => ['count' => 6, 'label' => 'report-', 'ext' => 'pdf', 'mime' => 'application/pdf', 'max_kb' => 28],
                    'children' => [
                        '2025' => ['files' => ['count' => 6, 'label' => 'report-2025-', 'ext' => 'pdf', 'mime' => 'application/pdf', 'max_kb' => 18]],
                        '2024' => ['files' => ['count' => 6, 'label' => 'report-2024-', 'ext' => 'pdf', 'mime' => 'application/pdf', 'max_kb' => 18]],
                    ],
                ],
            ],
        ],
        'Music' => [
            'files' => ['count' => 8, 'label' => 'track-', 'ext' => 'mp3', 'mime' => 'audio/mpeg', 'max_kb' => 32],
            'children' => [
                'Playlists' => ['files' => ['count' => 8, 'label' => 'playlist-', 'ext' => 'mp3', 'mime' => 'audio/mpeg', 'max_kb' => 28]],
            ],
        ],
    ];

    private FileServiceInterface $fileService;

    public function run(): void
    {
        $this->fileService = app(FileServiceInterface::class);

        $this->call(StoragePackageSeeder::class);

        $admin = $this->seedUser('admin@dibumi.com', 'Admin FHV', 'admin');
        $member = $this->seedUser('member@dibumi.com', 'Member FHV', 'member');

        foreach ([$admin, $member] as $user) {
            $this->seedUserProfile($user);
            $this->seedUserSubscription($user);
            $this->seedDemoTree($user);
        }

        // Single global storage server used by the whole system
        StorageServer::whereIn('name', ['admin-local', 'budi-local'])->delete();

        StorageServer::updateOrCreate(
            ['name' => 'global-local'],
            [
                'user_id' => null,
                'driver' => 'local',
                'config' => ['root' => storage_path('app/global')],
                'is_active' => true,
                'is_primary' => true,
                'storage_limit' => null,
            ]
        );
    }

    private function seedUser(string $email, string $name, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }

    private function seedUserProfile(User $user): void
    {
        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $user->isAdmin() ? null : '+628123456789',
                'bio' => $user->isAdmin() ? 'Platform administrator' : 'Content Creator',
                'storage_used' => 0,
            ]
        );
    }

    private function seedUserSubscription(User $user): void
    {
        $defaultPackage = StoragePackage::query()->where('is_default', true)->first();

        if (! $defaultPackage) {
            return;
        }

        UserSubscription::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'active'],
            [
                'storage_package_id' => $defaultPackage->id,
                'start_date' => now(),
            ]
        );

        $user->profile()->update(['storage_limit' => $defaultPackage->storage_limit]);
    }

    private function seedDemoTree(User $user): void
    {
        // Root-level files
        $this->seedFiles($user, null, [
            'count' => 10,
            'label' => 'root-file-',
            'ext' => 'txt',
            'mime' => 'text/plain',
            'max_kb' => 4,
        ]);

        foreach ($this->demoTree as $name => $spec) {
            $this->buildSubtree($user, null, $name, $spec);
        }
    }

    private function buildSubtree(User $user, ?string $parentId, string $name, array $spec): void
    {
        $parent = $parentId ? Directory::find($parentId) : null;
        $slug = Str::slug($name);
        $path = $parent ? "{$parent->path}/{$slug}" : "/{$slug}";

        $dir = Directory::create([
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'path' => $path,
        ]);

        if (isset($spec['files'])) {
            $this->seedFiles($user, $dir->id, $spec['files']);
        }

        foreach ($spec['children'] ?? [] as $childName => $childSpec) {
            // Array keys that look like integers (e.g. "2025") become int — cast back to string.
            $this->buildSubtree($user, $dir->id, (string) $childName, $childSpec);
        }
    }

    private function seedFiles(User $user, ?string $directoryId, array $spec): void
    {
        $count = $spec['count'];
        $label = $spec['label'];
        $ext = $spec['ext'];
        $mime = $spec['mime'];
        $maxKb = $spec['max_kb'];
        $isVideo = $spec['video'] ?? false;

        for ($i = 1; $i <= $count; $i++) {
            $tmp = tempnam(sys_get_temp_dir(), 'fhv_seed_');
            $kb = random_int(1, $maxKb);
            $content = $this->randomContent($kb, $user->id, $label.$i);

            if ($isVideo) {
                // Write a tiny valid-ish MP4 container header so the player has bytes.
                $content = $this->fakeMp4($content);
            }

            file_put_contents($tmp, $content);

            $name = sprintf('%s%s.%s', $label, $i, $ext);

            try {
                $uploaded = new UploadedFile($tmp, $name, $mime, null, true);

                $file = $this->fileService->store($uploaded, $directoryId, $user->id);

                $file->updateQuietly([
                    'name' => pathinfo($name, PATHINFO_FILENAME),
                ]);
            } catch (\Throwable $e) {
                $this->command?->warn("Seed file '{$name}' skipped: {$e->getMessage()}");
            } finally {
                @unlink($tmp);
            }
        }
    }

    private function randomContent(int $kb, string $salt, int|string $seed): string
    {
        $base = 'FHV-SEED:'.$salt.':'.$seed.':'.random_bytes(8);

        $data = str_repeat($base, 8);
        $targetLen = $kb * 1024;

        while (strlen($data) < $targetLen) {
            $data .= hash('sha256', $data);
        }

        return substr($data, 0, $targetLen);
    }

    private function fakeMp4(string $payload): string
    {
        // Minimal ISO Base Media (ftyp) preamble so video players detect an MP4.
        $ftyp = pack('N', 24).'ftyp'.'isom'.pack('N', 0x200).'isomiso2avc1mp41';
        $mdat = pack('N', strlen($payload) + 8).'mdat'.$payload;

        return $ftyp.$mdat;
    }
}
