<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Enums\SharePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shared_with' => ['required', 'email', 'exists:users,email'],
            'permission' => ['required', 'string', Rule::enum(SharePermission::class), Rule::in(SharePermission::fileOnly())],
            'password' => ['nullable', 'string', 'min:4'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'download_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
