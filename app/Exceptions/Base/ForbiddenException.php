<?php

declare(strict_types=1);

namespace App\Exceptions\Base;

use Symfony\Component\HttpFoundation\Response;

abstract class ForbiddenException extends DomainException
{
    protected $code = Response::HTTP_FORBIDDEN;
}
