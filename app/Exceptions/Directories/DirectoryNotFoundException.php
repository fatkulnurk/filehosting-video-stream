<?php

declare(strict_types=1);

namespace App\Exceptions\Directories;

use App\Exceptions\Base\NotFoundException;
use Symfony\Component\HttpFoundation\Response;

class DirectoryNotFoundException extends NotFoundException
{
    protected $code = Response::HTTP_NOT_FOUND;

    protected function defaultMessage(): string
    {
        return __('directories.not_found');
    }
}
