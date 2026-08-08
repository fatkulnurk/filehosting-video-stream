<?php

declare(strict_types=1);

namespace App\Http\Requests\Webhooks;

use App\Rules\Webhooks\SafeWebhookUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebhookRequest extends FormRequest
{
    protected array $allowedEvents = [
        'file.uploaded',
        'file.downloaded',
        'file.deleted',
        'file.restored',
        'directory.created',
        'directory.deleted',
        'storage.exceeded',
        'subscription.expired',
        'file.expired',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048', new SafeWebhookUrl],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', Rule::in($this->allowedEvents)],
            'secret' => ['nullable', 'string', 'min:8', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
