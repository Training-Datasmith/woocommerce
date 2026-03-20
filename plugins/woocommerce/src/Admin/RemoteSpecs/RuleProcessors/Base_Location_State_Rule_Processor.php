<?php

declare (strict_types=1);
/**
 * Rule processor that performs a comparison operation against the base
 * location - state.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
/**
 * Rule processor that performs a comparison operation against the base
 * location - state.
 */
class Base_Location_State_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * Performs a comparison operation against the base location - state.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state)
    {
        $base_location = wc_get_base_location();
        if (!is_array($base_location) || !array_key_exists('state', $base_location)) {
            return false;
        }
        return Comparison_Operation::compare($base_location['state'], $rule->value, $rule->operation);
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
        if (!isset($rule->value)) {
            return false;
        }
        if (!isset($rule->operation)) {
            return false;
        }
        return true;
    }
}