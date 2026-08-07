<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['required', 'uuid', 'exists:storage_packages,id'],
            'payment_method' => ['nullable', 'string', 'max:255'],
        ];
    }
}
