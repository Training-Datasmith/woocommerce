<?php

declare(strict_types=1);
/**
 * REST API bootstrap.
 */

namespace Automattic\WooCommerce\Admin\API;

use AllowDynamicProperties;
use Automattic\WooCommerce\Admin\Features\Features;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Utilities\RestApiUtil;

/**
 * Init class.
 *
 * @internal
 */
#[AllowDynamicProperties]
class Init
{
    /**
     * The single instance of the class.
     *
     * @var object
     */
    protected static $instance;

    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    final public static function instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Bootstrap REST API.
     */
    public function __construct()
    {
        // Hook in data stores.
        add_filter('woocommerce_data_stores', self::add_data_stores(...));
        // REST API extensions init.
        add_action('rest_api_init', $this->rest_api_init(...));

        // Add currency symbol to orders endpoint response.
        add_filter('woocommerce_rest_prepare_shop_order_object', self::add_currency_symbol_to_order_response(...));

        include_once WC_ABSPATH . 'includes/admin/class-wc-admin-upload-downloadable-product.php';
    }

    /**
     * Initialize the API namespaces under WooCommerce Admin.
     */
    public function rest_api_init(): void
    {
        if (wc_rest_should_load_namespace('wc-admin')) {
            $this->rest_api_init_wc_admin();
        }

        $rest_api_util = wc_get_container()->get(RestApiUtil::class);
        $rest_api_util->lazy_load_namespace('wc-analytics', $this->rest_api_init_wc_analytics(...));

        if (Features::is_enabled('launch-your-store')) {
            $controller        = \Automattic\WooCommerce\Admin\API\LaunchYourStore::class;
            $this->$controller = new $controller();
            $this->$controller->register_routes();
        }
    }

    /**
     * Load the wc-admin namespace controllers.
     */
    public function rest_api_init_wc_admin(): void
    {
        $controllers = [
            \Automattic\WooCommerce\Admin\API\Notice::class,
            \Automattic\WooCommerce\Admin\API\Features::class,
            \Automattic\WooCommerce\Admin\API\Experiments::class,
            \Automattic\WooCommerce\Admin\API\Marketing::class,
            \Automattic\WooCommerce\Admin\API\MarketingOverview::class,
            \Automattic\WooCommerce\Admin\API\MarketingRecommendations::class,
            \Automattic\WooCommerce\Admin\API\MarketingChannels::class,
            \Automattic\WooCommerce\Admin\API\MarketingCampaigns::class,
            \Automattic\WooCommerce\Admin\API\MarketingCampaignTypes::class,
            \Automattic\WooCommerce\Admin\API\Options::class,
            \Automattic\WooCommerce\Admin\API\Settings::class,
            \Automattic\WooCommerce\Admin\API\PaymentGatewaySuggestions::class,
            \Automattic\WooCommerce\Admin\API\Themes::class,
            \Automattic\WooCommerce\Admin\API\Plugins::class,
            \Automattic\WooCommerce\Admin\API\OnboardingFreeExtensions::class,
            \Automattic\WooCommerce\Admin\API\OnboardingProductTypes::class,
            \Automattic\WooCommerce\Admin\API\OnboardingProfile::class,
            \Automattic\WooCommerce\Admin\API\OnboardingTasks::class,
            \Automattic\WooCommerce\Admin\API\OnboardingThemes::class,
            \Automattic\WooCommerce\Admin\API\OnboardingPlugins::class,
            \Automattic\WooCommerce\Admin\API\OnboardingProducts::class,
            \Automattic\WooCommerce\Admin\API\MobileAppMagicLink::class,
            \Automattic\WooCommerce\Admin\API\ShippingPartnerSuggestions::class,
        ];

        if (! did_action('woocommerce_admin_rest_controllers')) {
            /**
             * Filter for the WooCommerce Admin REST controllers.
             *
             * Admin and Analytics controllers were originally loaded in one place.  However, with attempts to dynamically
             * load namespaces based on context, these were split up.  However, to maintain backward compatibility, we
             * must run this hook if either namespace is loaded because extensions could be targeting either namespace.
             *
             * @param array $controllers List of rest API controllers.
             *
             * @since 3.5.0
             */
            $controllers = apply_filters('woocommerce_admin_rest_controllers', $controllers);
            if (! is_array($controllers)) {
                return;
            }
        }

        $controllers = array_values(array_unique($controllers));
        foreach ($controllers as $controller) {
            if (is_string($controller)) {
                $this->$controller = new $controller();
                $this->$controller->register_routes();
            }
        }
    }

    /**
     * Load the wc-analytics namespace controllers.
     */
    public function rest_api_init_wc_analytics(): void
    {
        // Controllers in wc-analytics namespace, but loaded irrespective of analytics feature value.
        $controllers = [
            \Automattic\WooCommerce\Admin\API\Notes::class,
            \Automattic\WooCommerce\Admin\API\NoteActions::class,
            \Automattic\WooCommerce\Admin\API\Coupons::class,
            \Automattic\WooCommerce\Admin\API\Data::class,
            \Automattic\WooCommerce\Admin\API\DataCountries::class,
            \Automattic\WooCommerce\Admin\API\DataDownloadIPs::class,
            \Automattic\WooCommerce\Admin\API\Orders::class,
            \Automattic\WooCommerce\Admin\API\Products::class,
            \Automattic\WooCommerce\Admin\API\ProductAttributes::class,
            \Automattic\WooCommerce\Admin\API\ProductAttributeTerms::class,
            \Automattic\WooCommerce\Admin\API\ProductCategories::class,
            \Automattic\WooCommerce\Admin\API\ProductVariations::class,
            \Automattic\WooCommerce\Admin\API\ProductReviews::class,
            \Automattic\WooCommerce\Admin\API\ProductsLowInStock::class,
            \Automattic\WooCommerce\Admin\API\SettingOptions::class,
            \Automattic\WooCommerce\Admin\API\Taxes::class,
        ];

        $analytics_controllers = [];
        if (Features::is_enabled('analytics')) {
            $analytics_controllers = [
                \Automattic\WooCommerce\Admin\API\Customers::class,
                \Automattic\WooCommerce\Admin\API\Leaderboards::class,
                \Automattic\WooCommerce\Admin\API\Reports\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Import\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Export\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Products\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Variations\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Products\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Variations\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Revenue\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Orders\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Categories\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Taxes\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Coupons\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Stock\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Downloads\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Customers\Controller::class,
                \Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\Controller::class,
            ];

            if (Features::is_enabled('analytics-scheduled-import')) {
                $analytics_controllers[] = \Automattic\WooCommerce\Admin\API\AnalyticsImports::class;
            }

            // The performance indicators controllerq must be registered last, after other /stats endpoints have been registered.
            $analytics_controllers[] = \Automattic\WooCommerce\Admin\API\Reports\PerformanceIndicators\Controller::class;
        }

        $controllers = array_merge($analytics_controllers, $controllers);

        if (! did_action('woocommerce_admin_rest_controllers')) {
            /**
             * Filter for the WooCommerce Admin REST controllers.
             *
             * @param array $controllers List of rest API controllers.
             *
             * @since 3.5.0
             *
             * @see   self::rest_api_init_wc_admin() for extended documentation.
             */
            $controllers = apply_filters('woocommerce_admin_rest_controllers', $controllers);
            if (! is_array($controllers)) {
                return;
            }
        }

        $controllers = array_values(array_unique($controllers));
        foreach ($controllers as $controller) {
            if (is_string($controller)) {
                $this->$controller = new $controller();
                $this->$controller->register_routes();
            }
        }
    }

    /**
     * Adds data stores.
     *
     * @internal
     * @param array $data_stores List of data stores.
     */
    public static function add_data_stores($data_stores): array
    {
        return array_merge(
            $data_stores,
            [
                'report-revenue-stats'    => \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::class,
                'report-orders'           => \Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore::class,
                'report-orders-stats'     => \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::class,
                'report-products'         => \Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::class,
                'report-variations'       => \Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore::class,
                'report-products-stats'   => \Automattic\WooCommerce\Admin\API\Reports\Products\Stats\DataStore::class,
                'report-variations-stats' => \Automattic\WooCommerce\Admin\API\Reports\Variations\Stats\DataStore::class,
                'report-categories'       => \Automattic\WooCommerce\Admin\API\Reports\Categories\DataStore::class,
                'report-taxes'            => \Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore::class,
                'report-taxes-stats'      => \Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore::class,
                'report-coupons'          => \Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::class,
                'report-coupons-stats'    => \Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats\DataStore::class,
                'report-downloads'        => \Automattic\WooCommerce\Admin\API\Reports\Downloads\DataStore::class,
                'report-downloads-stats'  => \Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats\DataStore::class,
                'admin-note'              => \Automattic\WooCommerce\Admin\Notes\DataStore::class,
                'report-customers'        => \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::class,
                'report-customers-stats'  => \Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\DataStore::class,
                'report-stock-stats'      => \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\DataStore::class,
            ]
        );
    }

    /**
     * Add the currency symbol (in addition to currency code) to each Order
     * object in REST API responses. For use in formatAmount().
     *
     * @internal
     * @param WP_REST_Response $response REST response object.
     * @returns WP_REST_Response
     */
    public static function add_currency_symbol_to_order_response($response)
    {
        $response_data                    = $response->get_data();
        $currency_code                    = $response_data['currency'];
        $currency_symbol                  = get_woocommerce_currency_symbol($currency_code);
        $response_data['currency_symbol'] = html_entity_decode($currency_symbol);
        $response->set_data($response_data);

        return $response;
    }
}
