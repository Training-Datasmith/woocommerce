<?php

declare(strict_types=1);
/**
 * Gets the processor for the specified rule type.
 */

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

defined('ABSPATH') || exit;

/**
 * Class encapsulating getting the processor for a given rule type.
 */
class GetRuleProcessor
{
    /**
     * Get the processor for the specified rule type.
     *
     * @param string $rule_type The rule type.
     *
     * @return RuleProcessorInterface The matching processor for the specified rule type, or a FailRuleProcessor if no matching processor is found.
     */
    public static function get_processor($rule_type): \Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\PluginsActivatedRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\PublishAfterTimeRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\PublishBeforeTimeRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\NotRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\OrRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\FailRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\PassRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\PluginVersionRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\StoredStateRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\OrderCountRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\WCAdminActiveForRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\ProductCountRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\OnboardingProfileRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\IsEcommerceRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\IsWooExpressRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\BaseLocationCountryRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\BaseLocationStateRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\NoteStatusRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\OptionRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\WooCommerceAdminUpdatedRuleProcessor|\Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\TotalPaymentsVolumeProcessor
    {
        return match ($rule_type) {
            'plugins_activated' => new PluginsActivatedRuleProcessor(),
            'publish_after_time' => new PublishAfterTimeRuleProcessor(),
            'publish_before_time' => new PublishBeforeTimeRuleProcessor(),
            'not' => new NotRuleProcessor(),
            'or' => new OrRuleProcessor(),
            'fail' => new FailRuleProcessor(),
            'pass' => new PassRuleProcessor(),
            'plugin_version' => new PluginVersionRuleProcessor(),
            'stored_state' => new StoredStateRuleProcessor(),
            'order_count' => new OrderCountRuleProcessor(),
            'wcadmin_active_for' => new WCAdminActiveForRuleProcessor(),
            'product_count' => new ProductCountRuleProcessor(),
            'onboarding_profile' => new OnboardingProfileRuleProcessor(),
            'is_ecommerce' => new IsEcommerceRuleProcessor(),
            'is_woo_express' => new IsWooExpressRuleProcessor(),
            'base_location_country' => new BaseLocationCountryRuleProcessor(),
            'base_location_state' => new BaseLocationStateRuleProcessor(),
            'note_status' => new NoteStatusRuleProcessor(),
            'option' => new OptionRuleProcessor(),
            'wca_updated' => new WooCommerceAdminUpdatedRuleProcessor(),
            'total_payments_value' => new TotalPaymentsVolumeProcessor(),
            default => new FailRuleProcessor(),
        };
    }
}
