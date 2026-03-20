<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Shipping_Partner_Suggestions;

use Automattic\Woo_Commerce\Admin\Remote_Specs\Data_Source_Poller;
use WC_Helper;
/**
 * Specs data source poller class for shipping partner suggestions.
 */
class Shipping_Partner_Suggestions_Data_Source_Poller extends Data_Source_Poller
{
    /**
     * Data Source Poller ID.
     */
    public const ID = 'shipping_partner_suggestions';
    /**
     * Default data sources array.
     *
     * @deprecated since 9.5.0. Use get_data_sources() instead.
     */
    public const DATA_SOURCES = [];
    /**
     * Class instance.
     *
     * @var ShippingPartnerSuggestionsDataSourcePoller instance
     */
    protected static $instance;
    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self(self::ID, self::get_data_sources());
        }
        return self::$instance;
    }
    /**
     * Get data sources.
     */
    public static function get_data_sources(): array
    {
        return [WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/shipping-partner-suggestions/2.0/suggestions.json'];
    }
}