<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidModuleKeyException extends RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Invalid module key: {$key}");
    }
}
