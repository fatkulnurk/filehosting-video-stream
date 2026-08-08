<?php

declare(strict_types=1);

namespace App\Exceptions\Base;

use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

abstract class DomainException extends Exception
{
    protected $code = HttpResponse::HTTP_INTERNAL_SERVER_ERROR;

    public function __construct(
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message !== '' ? $message : $this->defaultMessage(),
            (int) $this->code,
            $previous
        );
    }

    public function getStatusCode(): int
    {
        return (int) $this->code;
    }

    public function getErrorCode(): string
    {
        // App\Exceptions\Files\FileNotFoundException → file_not_found
        $short = class_basename(static::class);
        $withoutSuffix = preg_replace('/Exception$/', '', $short);
        $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', (string) $withoutSuffix);

        return strtolower((string) $snake);
    }

    /**
     * Default translated message used when the exception is thrown without one.
     */
    protected function defaultMessage(): string
    {
        return 'An error occurred.';
    }

    /**
     * Structured context appended to the exception's log entry.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [];
    }

    /**
     * Render the exception as an HTTP response (Laravel 13 renderable exception).
     */
    public function render(Request $request): HttpResponse
    {
        $status = $this->getStatusCode();

        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'error' => [
                    'code' => $this->getErrorCode(),
                    'message' => $this->getMessage(),
                ],
            ], $status);
        }

        return Inertia::render('Error', [
            'status' => $status,
            'message' => $this->getMessage(),
        ])->toResponse($request)->setStatusCode($status);
    }
}
