<?php

declare(strict_types=1);

namespace App\Http\Requests\Storage;

use App\Enums\StorageDriver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorageServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $driver = $this->input('driver');
        $driverEnum = StorageDriver::tryFrom($driver);

        $configRules = [];

        if ($driverEnum) {
            foreach ($driverEnum->requiredFields() as $field) {
                $configRules['config.'.$field] = ['nullable', 'string'];
            }
        }

        return array_merge([
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/i', Rule::unique('storage_servers')->ignore($this->route('server'))],
            'driver' => ['required', 'string', Rule::enum(StorageDriver::class)],
            'is_primary' => ['sometimes', 'boolean'],
            'backup_storage_server_id' => ['nullable', 'uuid', 'exists:storage_servers,id'],
            'auto_backup' => ['sometimes', 'boolean'],
            'storage_limit' => ['nullable', 'integer', 'min:0'],
        ], $configRules);
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Disk name hanya boleh berisi huruf, angka, strip, dan underscore.',
        ];
    }
}
