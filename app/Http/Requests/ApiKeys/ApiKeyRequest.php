<?php

declare(strict_types=1);

namespace App\Http\Requests\ApiKeys;

use Illuminate\Foundation\Http\FormRequest;

class ApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:file.read,file.write,file.delete,directory.manage,storage.view,webhook.manage'],
            'rate_limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
