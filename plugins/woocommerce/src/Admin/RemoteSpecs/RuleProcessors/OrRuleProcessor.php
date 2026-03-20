<?php

declare (strict_types=1);
/**
 * Rule processor that performs an OR operation on the rule's left and right
 * operands.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
/**
 * Rule processor that performs an OR operation on the rule's left and right
 * operands.
 */
class Or_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * Rule evaluator to use.
     *
     * @var RuleEvaluator
     */
    private $rule_evaluator;
    /**
     * Constructor.
     *
     * @param RuleEvaluator $rule_evaluator The rule evaluator to use.
     */
    public function __construct($rule_evaluator = null)
    {
        $this->rule_evaluator = $rule_evaluator ?? new Rule_Evaluator();
    }
    /**
     * Performs an OR operation on the rule's left and right operands.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state): bool
    {
        foreach ($rule->operands as $operand) {
            $evaluated_operand = $this->rule_evaluator->evaluate($operand, $stored_state);
            if ($evaluated_operand) {
                return true;
            }
        }
        return false;
    }
    /**
     * Validates the rule.
     *
     * @param object $rule The rule to validate.
     *
     * @return bool Pass/fail.
     */
    public function validate($rule): bool
    {
        if (!isset($rule->operands) || !is_array($rule->operands)) {
            return false;
        }
        return true;
    }
}