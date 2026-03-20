<?php

declare (strict_types=1);
/**
 * Rule processor for sending when WooCommerce Admin has been updated.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

use Automattic\Woo_Commerce\Admin\Remote_Inbox_Notifications\Remote_Inbox_Notifications_Engine;
defined('ABSPATH') || exit;
/**
 * Rule processor for sending when WooCommerce Admin has been updated.
 */
class Woo_Commerce_Admin_Updated_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * Process the rule.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool Whether the rule passes or not.
     */
    public function process($rule, $stored_state)
    {
        return get_option(Remote_Inbox_Notifications_Engine::WCA_UPDATED_OPTION_NAME, false);
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