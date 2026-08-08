<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\Subscriptions\StoragePackage;
use App\Models\Subscriptions\UserSubscription;
use App\Models\Users\User;
use App\Traits\PasswordValidationRules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'role' => 'member',
            'password' => Hash::make($input['password']),
        ]);

        $user->profile()->create([]);

        $defaultPackage = StoragePackage::query()->where('is_default', true)->first();

        if ($defaultPackage) {
            UserSubscription::create([
                'user_id' => $user->id,
                'storage_package_id' => $defaultPackage->id,
                'status' => 'active',
                'start_date' => now(),
            ]);

            $user->profile()->update(['storage_limit' => $defaultPackage->storage_limit]);
        }

        return $user;
    }
}
