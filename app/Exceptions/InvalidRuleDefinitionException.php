<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidRuleDefinitionException extends RuntimeException
{
    public function __construct(string $message, public readonly ?string $subject = null, public readonly ?string $ruleKey = null)
    {
        parent::__construct($message);
    }
}
