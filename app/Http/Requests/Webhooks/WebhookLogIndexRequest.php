<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

class WebhookLogIndexRequest extends FormRequest
{
    private const SORTABLE_FIELDS = ['event', 'status', 'response', 'duration', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'array'],
            'sort.*' => ['required_with:sort', 'string', 'in:asc,desc'],
        ];
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
