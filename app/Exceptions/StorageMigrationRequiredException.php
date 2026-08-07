<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class StorageMigrationRequiredException extends Exception
{
    protected $code = 409;

    public function __construct(string $message = 'Storage migration required', int $code = 409, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->getMessage(),
            ], 409);
        }

        return redirect()->back()->withErrors([
            'storage' => $this->getMessage(),
        ]);
    }
}
