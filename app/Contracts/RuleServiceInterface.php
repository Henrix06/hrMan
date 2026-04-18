<?php

namespace App\Contracts;

interface RuleServiceInterface
{
    public function evaluate(string $subject, array $context = []): bool;
    public function upsert(string $subject, string $ruleKey, array $ruleDefinition): void;
    public function remove(string $subject, string $ruleKey): void;
}
