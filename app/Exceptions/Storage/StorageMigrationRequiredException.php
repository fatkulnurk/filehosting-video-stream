<?php

declare(strict_types=1);

namespace App\Exceptions\Storage;

use App\Exceptions\Base\DomainException;
use Symfony\Component\HttpFoundation\Response;

class StorageMigrationRequiredException extends DomainException
{
    protected $code = Response::HTTP_CONFLICT;

    public function __construct(
        string $message = '',
        private readonly ?string $storageServerId = null,
        private readonly ?int $fileCount = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    protected function defaultMessage(): string
    {
        return __('storage.migration_required');
    }

    public function context(): array
    {
        return array_filter([
            'storage_server_id' => $this->storageServerId,
            'file_count' => $this->fileCount,
        ], fn ($value) => $value !== null);
    }
}
