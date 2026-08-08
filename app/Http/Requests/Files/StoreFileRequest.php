<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

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
            'file' => ['required_without:files', 'file', 'max:'.config('filehosting.upload.max_size', 104857600)],
            'files' => ['sometimes', 'array'],
            'files.*' => ['file', 'max:'.config('filehosting.upload.max_size', 104857600)],
            'directory_id' => ['nullable', 'uuid', 'exists:directories,id'],
            'disk' => ['nullable', 'string', 'max:255'],
        ];
    }
}
