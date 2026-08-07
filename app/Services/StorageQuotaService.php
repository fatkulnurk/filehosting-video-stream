<?php

namespace App\Services;

use App\Models\File;
use App\Models\Profile;
use App\Services\Interfaces\StorageQuotaServiceInterface;
use Illuminate\Support\Facades\Cache;

class StorageQuotaService implements StorageQuotaServiceInterface
{
    public function getUsage(string $userId): int
    {
        return Cache::remember("storage_usage:{$userId}", 300, function () use ($userId) {
            return $this->calculateUsage($userId);
        });
    }

    private function calculateUsage(string $userId): int
    {
        $usage = (int) File::where('user_id', $userId)
            ->join('media', function ($join) {
                $join->on('files.id', '=', 'media.model_id')
                    ->where('media.model_type', '=', File::class);
            })
            ->sum('media.size');

        Profile::where('user_id', $userId)->update([
            'storage_used' => $usage,
            'storage_used_at' => now(),
        ]);

        return $usage;
    }

    public function canUpload(string $userId, int $fileSize): bool
    {
        $limit = $this->getLimit($userId);

        if (is_null($limit)) {
            return true;
        }

        return ($this->getUsage($userId) + $fileSize) <= $limit;
    }

    public function getRemaining(string $userId): ?int
    {
        $limit = $this->getLimit($userId);

        if (is_null($limit)) {
            return null;
        }

        return max(0, $limit - $this->getUsage($userId));
    }

    public function recalculate(string $userId): int
    {
        Cache::forget("storage_usage:{$userId}");

        return $this->calculateUsage($userId);
    }

    public function getLimit(string $userId): ?int
    {
        $user = \App\Models\User::find($userId);

        if (! $user) {
            return null;
        }

        // Admin unlimited
        if ($user->isAdmin()) {
            return null;
        }

        return $user->profile?->storage_limit;
    }
}
