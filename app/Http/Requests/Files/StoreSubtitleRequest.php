<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubtitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subtitle' => [
                'required',
                'file',
                'max:'.config('filehosting.subtitle.max_size', 2097152),
                'extensions:'.implode(',', config('filehosting.subtitle.allowed_extensions', ['vtt', 'srt'])),
            ],
            'language' => ['required', 'string', 'max:10'],
            'label' => ['nullable', 'string', 'max:100'],
            'default' => ['sometimes', 'boolean'],
        ];
    }
}
