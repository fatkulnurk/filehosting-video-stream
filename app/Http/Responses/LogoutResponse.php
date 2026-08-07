<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Services\Interfaces\ActivityLogServiceInterface;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): \Symfony\Component\HttpFoundation\Response
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
