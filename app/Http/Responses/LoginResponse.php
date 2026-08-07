<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\Interfaces\ActivityLogServiceInterface;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): \Symfony\Component\HttpFoundation\Response
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
