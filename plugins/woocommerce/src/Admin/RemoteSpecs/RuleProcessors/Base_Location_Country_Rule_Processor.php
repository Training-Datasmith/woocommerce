<?php

declare (strict_types=1);
/**
 * Rule processor that performs a comparison operation against the base
 * location - country.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

use Automattic\Woo_Commerce\Internal\Admin\Onboarding\Onboarding_Profile;
defined('ABSPATH') || exit;
/**
 * Rule processor that performs a comparison operation against the base
 * location - country.
 */
class Base_Location_Country_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * Performs a comparison operation against the base location - country.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state)
    {
        $base_location = wc_get_base_location();
        if (!is_array($base_location) || !array_key_exists('country', $base_location) || !array_key_exists('state', $base_location)) {
            return false;
        }
        $onboarding_profile = get_option('woocommerce_onboarding_profile', []);
        $is_address_default = 'US' === $base_location['country'] && 'CA' === $base_location['state'] && empty(get_option('woocommerce_store_address', ''));
        $is_store_country_set = isset($onboarding_profile['is_store_country_set']) && $onboarding_profile['is_store_country_set'];
        // Return false if the location is the default country and if onboarding hasn't been finished or the store address not been updated.
        if ($is_address_default && Onboarding_Profile::needs_completion() && !$is_store_country_set) {
            return false;
        }
        return Comparison_Operation::compare($base_location['country'], $rule->value, $rule->operation);
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