<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        $auth = null;

        if ($user) {
            $auth = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_admin' => $user->isAdmin(),
                'storage_limit' => $user->storageLimit(),
                'storage_used' => $user->storageUsed(),
                'usage_percentage' => $user->profile?->usage_percentage,
                'profile' => $user->profile,
            ];
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $auth,
            ],
            'player' => [
                'driver' => config('filehosting.player.driver', 'plyr'),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'ziggy' => fn () => (new Ziggy)->toArray(),
        ]);
    }
}
