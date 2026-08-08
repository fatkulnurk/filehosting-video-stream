<?php

declare(strict_types=1);

namespace App\Http\Requests\Storage;

use App\Enums\PackageFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoragePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('storage_packages', 'slug')->ignore($this->route('package'))],
            'description' => ['nullable', 'string'],
            'storage_limit' => ['required', 'integer', 'min:0'],
            'bandwidth_limit' => ['nullable', 'integer', 'min:0'],
            'file_limit' => ['nullable', 'integer', 'min:1'],
            'max_file_size' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::enum(PackageFeature::class)],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ];
    }
}
