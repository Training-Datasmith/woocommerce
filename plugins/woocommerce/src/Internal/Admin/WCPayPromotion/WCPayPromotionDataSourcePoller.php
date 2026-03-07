<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\WCPayPromotion;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use WC_Helper;

/**
 * Specs data source poller class for WooPayments Promotion.
 */
class WCPayPromotionDataSourcePoller extends DataSourcePoller
{
    public const ID = 'payment_method_promotion';

    /**
     * Default data sources array.
     *
     * @deprecated since 9.5.0. Use get_data_sources() instead.
     */
    public const DATA_SOURCES = [];

    /**
     * Class instance.
     *
     * @var WCPayPromotionDataSourcePoller instance
     */
    protected static $instance;

    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (! self::$instance) {
            self::$instance = new self(self::ID, self::get_data_sources());
        }
        return self::$instance;
    }

    /**
     * Get data sources.
     */
    public static function get_data_sources(): array
    {
        $data_sources = [
            WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/payment-gateway-suggestions/2.0/payment-method/promotions.json',
        ];

        // Add country query param to data sources.
        $base_location             = wc_get_base_location();
        return array_map(
            fn ($url) => add_query_arg(
                'country',
                $base_location['country'],
                $url
            ),
            $data_sources
        );
    }
}
