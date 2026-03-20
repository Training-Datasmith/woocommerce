<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Marketing_Recommendations;

use Automattic\Woo_Commerce\Admin\Remote_Specs\Data_Source_Poller;
use WC_Helper;
/**
 * Specs data source poller class for misc recommendations.
 *
 * The misc recommendations are fetched from the WooCommerce.com API, the data structure looks like this:
 *
 * [
 *   {
 *     "id": "woocommerce-analytics",
 *     "order_attribution_promotion_percentage": [
 *       [ "9.7", 100 ],
 *       [ "9.6", 60 ],
 *       [ "9.5", 10 ]
 *     ]
 *   }
 * ]
 *
 * @since 9.5.0
 */
class Misc_Recommendations_Data_Source_Poller extends Data_Source_Poller
{
    /**
     * Data Source Poller ID.
     */
    public const ID = 'misc_recommendations';
    /**
     * Class instance.
     *
     * @var MiscRecommendationsDataSourcePoller instance
     */
    protected static $instance;
    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self(self::ID, self::get_data_sources(), ['transient_expiry' => DAY_IN_SECONDS]);
        }
        return self::$instance;
    }
    /**
     * Get data sources.
     */
    public static function get_data_sources(): array
    {
        return [WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/marketing-tab/misc/recommendations.json'];
    }
}