<?php

declare (strict_types=1);
/**
 * Rule processor that passes (or fails) when the site is on the eCommerce
 * plan.
 *
 * @package WooCommerce\Admin\Classes
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
/**
 * Rule processor that passes (or fails) when the site is on the eCommerce
 * plan.
 */
class Is_Ecommerce_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * Passes (or fails) based on whether the site is on the eCommerce plan or
     * not.
     *
     * @param object $rule         The rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state): bool
    {
        if (!function_exists('wc_calypso_bridge_is_ecommerce_plan')) {
            return false === $rule->value;
        }
        return (bool) wc_calypso_bridge_is_ecommerce_plan() === $rule->value;
    }
    /**
     * Validate the rule.
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
        return true;
    }
}