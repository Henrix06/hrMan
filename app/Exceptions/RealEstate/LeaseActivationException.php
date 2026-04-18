<?php

namespace App\Exceptions\RealEstate;

use RuntimeException;

class LeaseActivationException extends RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct("Lease activation failed: {$reason}");
    }
}
