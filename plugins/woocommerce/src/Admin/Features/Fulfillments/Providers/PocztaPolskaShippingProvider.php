<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Fulfillments\Providers;

/**
 * Poczta Polska Shipping Provider class.
 */
class Poczta_Polska_Shipping_Provider extends Abstract_Shipping_Provider
{
    /**
     * Get the key of the shipping provider.
     */
    public function get_key(): string
    {
        return 'poczta-polska';
    }
    /**
     * Get the name of the shipping provider.
     */
    public function get_name(): string
    {
        return 'Poczta Polska';
    }
    /**
     * Get the icon of the shipping provider.
     */
    public function get_icon(): string
    {
        return esc_url(WC()->plugin_url()) . '/assets/images/shipping_providers/poczta-polska.png';
    }
    /**
     * Get the tracking URL for a given tracking number.
     *
     * @param string $tracking_number The tracking number.
     * @return string The tracking URL.
     */
    public function get_tracking_url(string $tracking_number): string
    {
        return 'https://emonitoring.poczta-polska.pl/track/' . $tracking_number;
    }
}