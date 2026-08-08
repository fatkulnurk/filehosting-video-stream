<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Rules\Downloads\SafeDownloadUrl;
use Illuminate\Foundation\Http\FormRequest;

class StoreUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'url', 'max:2048', new SafeDownloadUrl],
            'directory_id' => ['nullable', 'uuid', 'exists:directories,id'],
            'disk' => ['nullable', 'string', 'max:255'],
        ];
    }
}
