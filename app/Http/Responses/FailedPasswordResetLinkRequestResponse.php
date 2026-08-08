<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedPasswordResetLinkRequestResponseContract;
use Symfony\Component\HttpFoundation\Response;

class FailedPasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponseContract
{
    protected string $status;

    public function __construct(string $status)
    {
        $this->status = $status;
    }

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => trans('passwords.sent')], 200);
        }

        // Anti user-enumeration: always report success so we never leak
        // whether an account exists for the submitted email address.
        return back()
            ->withInput($request->only('email'))
            ->with('status', trans('passwords.sent'));
    }
}
