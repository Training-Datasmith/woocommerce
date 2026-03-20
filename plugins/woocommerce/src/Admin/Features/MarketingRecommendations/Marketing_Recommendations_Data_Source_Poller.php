<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Marketing_Recommendations;

use Automattic\Woo_Commerce\Admin\Remote_Specs\Data_Source_Poller;
use WC_Helper;
/**
 * Specs data source poller class for marketing recommendations.
 */
class Marketing_Recommendations_Data_Source_Poller extends Data_Source_Poller
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
        if (!self::$instance) {
            self::$instance = new self(self::ID, self::get_data_sources(), ['spec_key' => 'product']);
        }
        return self::$instance;
    }
    /**
     * Get data sources.
     */
    public static function get_data_sources(): array
    {
        return [WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/marketing-tab/1.3/recommendations.json'];
    }
}