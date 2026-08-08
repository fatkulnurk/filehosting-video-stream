<?php

declare(strict_types=1);

namespace App\Http\Requests\Directories;

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
            'permission' => ['required', 'string', Rule::in(['view', 'upload', 'manage'])],
            'recursive' => ['sometimes', 'boolean'],
        ];
    }
}
