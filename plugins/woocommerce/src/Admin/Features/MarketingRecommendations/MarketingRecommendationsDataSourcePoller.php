<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\MarketingRecommendations;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use WC_Helper;

/**
 * Specs data source poller class for marketing recommendations.
 */
class MarketingRecommendationsDataSourcePoller extends DataSourcePoller
{
    /**
     * Data Source Poller ID.
     */
    public const ID = 'marketing_recommendations';

    /**
     * Default data sources array.
     *
     * @deprecated since 9.5.0. Use get_data_sources() instead.
     */
    public const DATA_SOURCES = [];

    /**
     * Class instance.
     *
     * @var MarketingRecommendationsDataSourcePoller instance
     */
    protected static $instance;

    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (! self::$instance) {
            self::$instance = new self(
                self::ID,
                self::get_data_sources(),
                [
                    'spec_key' => 'product',
                ]
            );
        }
        return self::$instance;
    }

    /**
     * Get data sources.
     */
    public static function get_data_sources(): array
    {
        return [
            WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/marketing-tab/1.3/recommendations.json',
        ];
    }
}
