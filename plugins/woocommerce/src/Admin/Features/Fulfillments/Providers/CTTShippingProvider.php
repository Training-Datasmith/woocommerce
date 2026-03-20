<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Fulfillments\Providers;

/**
 * CTT Shipping Provider class.
 */
class Ctt_Shipping_Provider extends Abstract_Shipping_Provider
{
    /**
     * Get the key of the shipping provider.
     */
    public function get_key(): string
    {
        return 'ctt';
    }
    /**
     * Get the name of the shipping provider.
     */
    public function get_name(): string
    {
        return 'CTT';
    }
    /**
     * Get the icon of the shipping provider.
     */
    public function get_icon(): string
    {
        return esc_url(WC()->plugin_url()) . '/assets/images/shipping_providers/ctt.png';
    }
    /**
     * Get the tracking URL for a given tracking number.
     *
     * @param string $tracking_number The tracking number.
     * @return string The tracking URL.
     */
    public function get_tracking_url(string $tracking_number): string
    {
        return 'https://www.ctt.pt/feapl_2/app/open/objectTrackingSearch.do?objectCode=' . $tracking_number;
    }
}