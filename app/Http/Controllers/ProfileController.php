<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Profiles\UpdateProfileRequest;
use App\Http\Requests\Profiles\UploadAvatarRequest;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->load('profile');

        return Inertia::render('Profile/Edit', [
            'user' => $user,
            'twoFactorEnabled' => ! is_null($user->two_factor_confirmed_at),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();

        $user->update([
            'name' => $request->name,
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $request->phone,
                'bio' => $request->bio,
            ]
        );

        return redirect()->back()->with('success', 'Profile updated');
    }

    public function uploadAvatar(UploadAvatarRequest $request)
    {
        $path = $request->file('avatar')->store('avatars', 'public');

        auth()->user()->profile()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['avatar' => $path]
        );

        return redirect()->back()->with('success', 'Avatar updated');
    }
}
