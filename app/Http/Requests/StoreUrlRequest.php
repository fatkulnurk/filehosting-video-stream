<?php

namespace App\Http\Requests;

use App\Rules\SafeDownloadUrl;
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