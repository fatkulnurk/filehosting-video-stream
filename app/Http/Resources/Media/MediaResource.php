<?php

declare(strict_types=1);

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'file_name' => $this->file_name,
            'name' => $this->name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'disk' => $this->disk,
            'collection_name' => $this->collection_name,
            'url' => $this->getUrl(),
            'custom_properties' => $this->custom_properties,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
