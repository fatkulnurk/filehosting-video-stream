<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreTorrentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'magnet' => ['required_without:torrent_file', 'nullable', 'string', 'max:2048'],
            'torrent_file' => ['required_without:magnet', 'nullable', 'file', 'max:4096'],
            'directory_id' => ['nullable', 'uuid', 'exists:directories,id'],
            'disk' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'magnet.required_without' => 'Provide a magnet link or upload a .torrent file.',
            'torrent_file.required_without' => 'Provide a magnet link or upload a .torrent file.',
        ];
    }

    public function magnet(): ?string
    {
        $magnet = $this->input('magnet');

        return $magnet !== null && $magnet !== '' ? trim($magnet) : null;
    }

    public function torrentFile(): ?UploadedFile
    {
        return $this->file('torrent_file');
    }
}
