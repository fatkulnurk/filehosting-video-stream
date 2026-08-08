<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id' => ['sometimes', 'uuid', 'exists:storage_packages,id'],
            'status' => ['sometimes', 'string', 'in:active,cancelled,expired'],
            'end_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
