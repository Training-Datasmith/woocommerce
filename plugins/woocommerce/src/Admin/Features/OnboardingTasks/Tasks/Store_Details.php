<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Tasks;

use Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Task;
/**
 * Store Details Task
 */
class Store_Details extends Task
{
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'store_details';
    }
    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        if (true === $this->get_parent_option('use_completed_title')) {
            if ($this->is_complete()) {
                return __('You added store details', 'woocommerce');
            }
            return __('Add store details', 'woocommerce');
        }
        return __('Store details', 'woocommerce');
    }
    /**
     * Content.
     *
     * @return string
     */
    public function get_content()
    {
        return __('Your store address is required to set the origin country for shipping, currencies, and payment options.', 'woocommerce');
    }
    /**
     * Time.
     *
     * @return string
     */
    public function get_time()
    {
        return __('4 minutes', 'woocommerce');
    }
    /**
     * Time.
     *
     * @return string
     */
    public function get_action_url()
    {
        return !$this->is_complete() ? admin_url('admin.php?page=wc-settings&tab=general&tutorial=true') : admin_url('admin.php?page=wc-settings&tab=general');
    }
    /**
     * Task completion.
     */
    public function is_complete(): bool
    {
        $country = WC()->countries->get_base_country();
        $country_locale = WC()->countries->get_country_locale();
        $locale = $country_locale[$country] ?? [];
        $hide_postcode = $locale['postcode']['hidden'] ?? false;
        // If postcode is hidden, just check that the store address and city are set.
        if ($hide_postcode) {
            return get_option('woocommerce_store_address', '') !== '' && get_option('woocommerce_store_city', '') !== '';
        }
        // Mark as completed if the store address, city and postcode are set. We don't need to check the country because it's set by default.
        return get_option('woocommerce_store_address', '') !== '' && get_option('woocommerce_store_city', '') !== '' && get_option('woocommerce_store_postcode', '') !== '';
    }
}