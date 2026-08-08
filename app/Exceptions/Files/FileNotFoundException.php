<?php

declare(strict_types=1);

namespace App\Exceptions\Files;

use App\Exceptions\Base\NotFoundException;
use Symfony\Component\HttpFoundation\Response;

class FileNotFoundException extends NotFoundException
{
    protected $code = Response::HTTP_NOT_FOUND;

    protected function defaultMessage(): string
    {
        return __('files.not_found');
    }
}
