<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'directory_id' => ['nullable', 'uuid', 'exists:directories,id'],
            'files' => ['required', 'array'],
            'files.*' => ['file'],
        ];
    }
}
