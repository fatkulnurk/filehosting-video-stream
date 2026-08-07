<?php

namespace App\Http\Requests;

use App\Enums\DownloaderDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', Rule::in(DownloaderDriver::supported())],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
        ];
    }
}
