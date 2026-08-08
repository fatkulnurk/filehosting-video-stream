<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'status' => ['sometimes', 'string', Rule::enum(SubscriptionStatus::class)],
            'end_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
