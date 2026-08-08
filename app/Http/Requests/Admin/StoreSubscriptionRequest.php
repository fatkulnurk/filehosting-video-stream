<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'package_id' => ['required', 'uuid', 'exists:storage_packages,id'],
            'status' => ['sometimes', 'string', 'in:active,cancelled,expired'],
        ];
    }
}
