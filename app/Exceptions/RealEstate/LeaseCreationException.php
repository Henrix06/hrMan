<?php

namespace App\Exceptions\RealEstate;

use RuntimeException;

class LeaseCreationException extends RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct("Lease creation failed: {$reason}");
    }
}
