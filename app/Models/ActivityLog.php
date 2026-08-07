<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function getDescriptionAttribute(): string
    {
        return match ($this->action) {
            'login' => 'Login ke akun',
            'logout' => 'Logout dari akun',
            'file.upload' => 'Upload file',
            'file.download' => 'Download file',
            'file.delete' => 'Hapus file',
            'file.restore' => 'Restore file',
            'file.force_delete' => 'Hapus permanen file',
            'file.rename' => 'Rename file',
            'file.move' => 'Pindahkan file',
            'file.update' => 'Update file',
            'directory.create' => 'Buat folder',
            'directory.rename' => 'Rename folder',
            'directory.delete' => 'Hapus folder',
            'directory.move' => 'Pindahkan folder',
            'storage.create' => 'Tambah storage server',
            'storage.update' => 'Update storage server',
            'storage.deactivate' => 'Nonaktifkan storage server',
            'api_key.create' => 'Buat API key',
            'webhook.create' => 'Buat webhook',
            'subscription.change' => 'Ganti paket',
            default => ucfirst(str_replace('.', ' ', $this->action)),
        };
    }
}
