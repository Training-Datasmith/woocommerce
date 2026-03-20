<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Fulfillments\Providers;

/**
 * Omniva Shipping Provider class.
 */
class Omniva_Shipping_Provider extends Abstract_Shipping_Provider
{
    /**
     * Get the key of the shipping provider.
     */
    public function get_key(): string
    {
        return 'omniva';
    }
    /**
     * Get the name of the shipping provider.
     */
    public function get_name(): string
    {
        return 'Omniva';
    }
    /**
     * Get the icon of the shipping provider.
     */
    public function get_icon(): string
    {
        return esc_url(WC()->plugin_url()) . '/assets/images/shipping_providers/omniva.png';
    }
    /**
     * Get the tracking URL for a given tracking number.
     *
     * @param string $tracking_number The tracking number.
     * @return string The tracking URL.
     */
    public function get_tracking_url(string $tracking_number): string
    {
        return 'https://www.omniva.ee/track/' . $tracking_number;
    }
}