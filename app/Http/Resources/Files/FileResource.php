<?php

declare(strict_types=1);

namespace App\Http\Resources\Files;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'views' => $this->views,
            'downloads' => $this->downloads,
            'is_public' => $this->is_public,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'size' => $this->size(),
            'formatted_size' => $this->formatted_size,
            'extension' => $this->extension(),
            'is_video' => $this->isVideo(),
            'is_image' => $this->isImage(),
            'public_url' => $this->public_url,
            'embed_url' => $this->embed_url,
            'stream_url' => $this->stream_url,
            'directory_id' => $this->directory_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'media' => $this->whenLoaded('media'),
        ];
    }
}
