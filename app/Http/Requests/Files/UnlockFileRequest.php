<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class UnlockFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }
}
