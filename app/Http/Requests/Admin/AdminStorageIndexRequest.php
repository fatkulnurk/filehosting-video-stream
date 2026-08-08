<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\StorageDriver;
use App\Enums\StorageStatusFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminStorageIndexRequest extends FormRequest
{
    private const SORTABLE_FIELDS = ['name', 'driver', 'storage_used', 'created_at'];

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
            'filter.driver' => ['nullable', 'string', Rule::enum(StorageDriver::class)],
            'filter.status' => ['nullable', 'string', Rule::enum(StorageStatusFilter::class)],
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
