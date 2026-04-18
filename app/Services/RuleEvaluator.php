<?php

namespace App\Services;

use App\Exceptions\InvalidRuleDefinitionException;

class RuleEvaluator
{
    /**
     * Evaluate a rule definition against a context map.
     */
    public function evaluateDefinition(array $definition, array $context): bool
    {
        if (! isset($definition['conditions']) || ! is_array($definition['conditions'])) {
            throw new InvalidRuleDefinitionException('Rule definition is missing required "conditions" key.');
        }

        $combinator = $definition['combinator'] ?? 'and';
        $results    = [];

        foreach ($definition['conditions'] as $condition) {
            $fieldValue = $context[$condition['field']] ?? null;
            $results[]  = $this->applyOperator($condition['operator'], $fieldValue, $condition['value']);
        }

        return match ($combinator) {
            'and'   => ! in_array(false, $results, true),
            'or'    => in_array(true, $results, true),
            'not'   => in_array(false, $results, true), // negation of AND
            default => throw new InvalidRuleDefinitionException("Unknown combinator: {$combinator}"),
        };
    }

    /**
     * Apply a single comparison operator.
     */
    public function applyOperator(string $operator, mixed $fieldValue, mixed $ruleValue): bool
    {
        return match ($operator) {
            'equals'       => $fieldValue === $ruleValue,
            'not_equals'   => $fieldValue !== $ruleValue,
            'in'           => is_array($ruleValue) && in_array($fieldValue, $ruleValue, true),
            'not_in'       => is_array($ruleValue) && ! in_array($fieldValue, $ruleValue, true),
            'gte'          => $fieldValue !== null && $fieldValue >= $ruleValue,
            'lte'          => $fieldValue !== null && $fieldValue <= $ruleValue,
            'gt'           => $fieldValue !== null && $fieldValue > $ruleValue,
            'lt'           => $fieldValue !== null && $fieldValue < $ruleValue,
            'contains'     => is_array($fieldValue)
                                ? in_array($ruleValue, $fieldValue, true)
                                : (is_string($fieldValue) && str_contains($fieldValue, (string) $ruleValue)),
            'not_contains' => is_array($fieldValue)
                                ? ! in_array($ruleValue, $fieldValue, true)
                                : (is_string($fieldValue) && ! str_contains($fieldValue, (string) $ruleValue)),
            default        => throw new InvalidRuleDefinitionException("Unknown operator: {$operator}"),
        };
    }
}
