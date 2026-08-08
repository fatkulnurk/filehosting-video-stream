<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class BatchDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_ids' => ['required', 'array', 'min:1'],
            'file_ids.*' => ['uuid', 'exists:files,id'],
        ];
    }
}
