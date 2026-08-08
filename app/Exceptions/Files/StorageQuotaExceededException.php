<?php

declare(strict_types=1);

namespace App\Exceptions\Files;

use App\Exceptions\Base\DomainException;
use Symfony\Component\HttpFoundation\Response;

class StorageQuotaExceededException extends DomainException
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(
        string $message = '',
        private readonly ?int $requestedBytes = null,
        private readonly ?int $remainingBytes = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    protected function defaultMessage(): string
    {
        return __('files.quota_exceeded');
    }

    public function context(): array
    {
        return array_filter([
            'requested_bytes' => $this->requestedBytes,
            'remaining_bytes' => $this->remainingBytes,
        ], fn ($value) => $value !== null);
    }
}
