<?php

declare (strict_types=1);
/**
 * Rule processor that negates the rules in the rule's operand.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
/**
 * Rule processor that negates the rules in the rule's operand.
 */
class Not_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * The rule evaluator to use.
     *
     * @var RuleEvaluator
     */
    protected $rule_evaluator;
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
     * Evaluates the rules in the operand and negates the result.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state): bool
    {
        $evaluated_operand = $this->rule_evaluator->evaluate($rule->operand, $stored_state);
        return !$evaluated_operand;
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
        if (!isset($rule->operand)) {
            return false;
        }
        return true;
    }
}