<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Contracts\Services\ActivityLogs\ActivityLogServiceInterface;
use App\Models\Users\User;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            app(ActivityLogServiceInterface::class)->log('logout', $user->id);
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Logged out']);
        }

        return redirect()->route('home');
    }
}
