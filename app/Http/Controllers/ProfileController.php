<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->load('profile');

        return Inertia::render('Profile/Edit', [
            'user' => $user,
            'twoFactorEnabled' => (bool) $user->two_factor_secret,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        auth()->user()->profile()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'phone' => $request->phone,
                'bio' => $request->bio,
            ]
        );

        return redirect()->back()->with('success', 'Profile updated');
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');

        auth()->user()->profile()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['avatar' => $path]
        );

        return redirect()->back()->with('success', 'Avatar updated');
    }
}