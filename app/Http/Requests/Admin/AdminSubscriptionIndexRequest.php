<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminSubscriptionIndexRequest extends FormRequest
{
    private const SORTABLE_FIELDS = ['user', 'package', 'status', 'start', 'end', 'created_at'];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'array'],
            'filter.status' => ['nullable', 'string', Rule::enum(SubscriptionStatus::class)],
            'filter.package_id' => ['nullable', 'uuid', 'exists:storage_packages,id'],
            'sort' => ['nullable', 'array'],
            'sort.*' => ['required_with:sort', 'string', 'in:asc,desc'],
        ];
    }

    public function getFilters(): array
    {
        return $this->validated()['filter'] ?? [];
    }

    public function getSorts(): array
    {
        $sorts = $this->input('sort', ['created_at' => 'desc']);

        return array_filter(
            $sorts,
            fn (string $field): bool => in_array($field, self::SORTABLE_FIELDS, true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
