<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Models\Users\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            app(ActivityLogServiceInterface::class)->log('login', $user->id, properties: ['method' => 'email']);
        }

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $home = $user instanceof User && $user->isAdmin()
            ? route('admin.dashboard')
            : config('fortify.home');

        return redirect()->intended($home);
    }
}
