<?php

declare(strict_types=1);
/**
 * Rule processor that fails.
 */

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

defined('ABSPATH') || exit;

/**
 * Rule processor that fails.
 */
class FailRuleProcessor implements RuleProcessorInterface
{
    /**
     * Fails the rule.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool Always false.
     */
    public function process($rule, $stored_state): bool
    {
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
        return true;
    }
}
