<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class StorageQuotaExceededException extends Exception
{
    protected $message = 'Storage quota exceeded';

    protected $code = 413;

    public function __construct(string $message = 'Storage quota exceeded', int $code = 413, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Storage quota exceeded',
                'message' => $this->getMessage(),
            ], 413);
        }

        return redirect()->back()->withErrors([
            'storage' => $this->getMessage(),
        ]);
    }
}
