<?php

declare(strict_types=1);

namespace App\Http\Requests\Directories;

use App\Enums\SharePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shared_with' => ['required', 'email', 'exists:users,email'],
            'permission' => ['required', 'string', Rule::enum(SharePermission::class), Rule::in(SharePermission::directoryOnly())],
            'recursive' => ['sometimes', 'boolean'],
        ];
    }
}
