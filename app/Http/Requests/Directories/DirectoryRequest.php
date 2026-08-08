<?php

declare(strict_types=1);

namespace App\Http\Requests\Directories;

use Illuminate\Foundation\Http\FormRequest;

class DirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid', 'exists:directories,id'],
        ];
    }
}
