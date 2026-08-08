<?php

declare(strict_types=1);

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'is_admin' => $this->isAdmin(),
            'storage_used' => $this->storageUsed(),
            'storage_limit' => $this->storageLimit(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
