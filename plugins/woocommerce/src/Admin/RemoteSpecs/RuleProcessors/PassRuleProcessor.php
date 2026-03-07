<?php

declare(strict_types=1);
/**
 * Rule processor that passes. This is required because an empty set of rules
 * (or predicate) evaluates to false.
 */

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

defined('ABSPATH') || exit;

/**
 * Rule processor that passes.
 */
class PassRuleProcessor implements RuleProcessorInterface
{
    /**
     * Passes the rule.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool Always true.
     */
    public function process($rule, $stored_state): bool
    {
        return true;
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
        return true;
    }
}
