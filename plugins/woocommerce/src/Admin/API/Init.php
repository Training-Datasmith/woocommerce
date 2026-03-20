<?php

declare (strict_types=1);
/**
 * REST API bootstrap.
 */
namespace Automattic\Woo_Commerce\Admin\API;

use Allow_Dynamic_Properties;
use Automattic\Woo_Commerce\Admin\Features\Features;
defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Utilities\Rest_Api_Util;
/**
 * Init class.
 *
 * @internal
 */
#[Allow_Dynamic_Properties]
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
        $rest_api_util = wc_get_container()->get(Rest_Api_Util::class);
        $rest_api_util->lazy_load_namespace('wc-analytics', $this->rest_api_init_wc_analytics(...));
        if (Features::is_enabled('launch-your-store')) {
            $controller = \Automattic\Woo_Commerce\Admin\API\Launch_Your_Store::class;
            $this->{$controller} = new $controller();
            $this->{$controller}->register_routes();
        }
    }
    /**
     * Load the wc-admin namespace controllers.
     */
    public function rest_api_init_wc_admin(): void
    {
        $controllers = [\Automattic\Woo_Commerce\Admin\API\Notice::class, \Automattic\Woo_Commerce\Admin\API\Features::class, \Automattic\Woo_Commerce\Admin\API\Experiments::class, \Automattic\Woo_Commerce\Admin\API\Marketing::class, \Automattic\Woo_Commerce\Admin\API\Marketing_Overview::class, \Automattic\Woo_Commerce\Admin\API\Marketing_Recommendations::class, \Automattic\Woo_Commerce\Admin\API\Marketing_Channels::class, \Automattic\Woo_Commerce\Admin\API\Marketing_Campaigns::class, \Automattic\Woo_Commerce\Admin\API\Marketing_Campaign_Types::class, \Automattic\Woo_Commerce\Admin\API\Options::class, \Automattic\Woo_Commerce\Admin\API\Settings::class, \Automattic\Woo_Commerce\Admin\API\Payment_Gateway_Suggestions::class, \Automattic\Woo_Commerce\Admin\API\Themes::class, \Automattic\Woo_Commerce\Admin\API\Plugins::class, \Automattic\Woo_Commerce\Admin\API\Onboarding_Free_Extensions::class, \Automattic\Woo_Commerce\Admin\API\Onboarding_Product_Types::class, \Automattic\Woo_Commerce\Admin\API\Onboarding_Profile::class, \Automattic\Woo_Commerce\Admin\API\Onboarding_Tasks::class, \Automattic\Woo_Commerce\Admin\API\Onboarding_Themes::class, \Automattic\Woo_Commerce\Admin\API\Onboarding_Plugins::class, \Automattic\Woo_Commerce\Admin\API\Onboarding_Products::class, \Automattic\Woo_Commerce\Admin\API\Mobile_App_Magic_Link::class, \Automattic\Woo_Commerce\Admin\API\Shipping_Partner_Suggestions::class];
        if (!did_action('woocommerce_admin_rest_controllers')) {
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
            if (!is_array($controllers)) {
                return;
            }
        }
        $controllers = array_values(array_unique($controllers));
        foreach ($controllers as $controller) {
            if (is_string($controller)) {
                $this->{$controller} = new $controller();
                $this->{$controller}->register_routes();
            }
        }
    }
    /**
     * Load the wc-analytics namespace controllers.
     */
    public function rest_api_init_wc_analytics(): void
    {
        // Controllers in wc-analytics namespace, but loaded irrespective of analytics feature value.
        $controllers = [\Automattic\Woo_Commerce\Admin\API\Notes::class, \Automattic\Woo_Commerce\Admin\API\Note_Actions::class, \Automattic\Woo_Commerce\Admin\API\Coupons::class, \Automattic\Woo_Commerce\Admin\API\Data::class, \Automattic\Woo_Commerce\Admin\API\Data_Countries::class, \Automattic\Woo_Commerce\Admin\API\Data_Download_I_Ps::class, \Automattic\Woo_Commerce\Admin\API\Orders::class, \Automattic\Woo_Commerce\Admin\API\Products::class, \Automattic\Woo_Commerce\Admin\API\Product_Attributes::class, \Automattic\Woo_Commerce\Admin\API\Product_Attribute_Terms::class, \Automattic\Woo_Commerce\Admin\API\Product_Categories::class, \Automattic\Woo_Commerce\Admin\API\Product_Variations::class, \Automattic\Woo_Commerce\Admin\API\Product_Reviews::class, \Automattic\Woo_Commerce\Admin\API\Products_Low_In_Stock::class, \Automattic\Woo_Commerce\Admin\API\Setting_Options::class, \Automattic\Woo_Commerce\Admin\API\Taxes::class];
        $analytics_controllers = [];
        if (Features::is_enabled('analytics')) {
            $analytics_controllers = [\Automattic\Woo_Commerce\Admin\API\Customers::class, \Automattic\Woo_Commerce\Admin\API\Leaderboards::class, \Automattic\Woo_Commerce\Admin\API\Reports\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Import\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Export\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Products\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Variations\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Products\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Variations\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Revenue\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Orders\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Orders\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Categories\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Taxes\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Taxes\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Coupons\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Coupons\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Stock\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Stock\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Downloads\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Downloads\Stats\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Customers\Controller::class, \Automattic\Woo_Commerce\Admin\API\Reports\Customers\Stats\Controller::class];
            if (Features::is_enabled('analytics-scheduled-import')) {
                $analytics_controllers[] = \Automattic\Woo_Commerce\Admin\API\Analytics_Imports::class;
            }
            // The performance indicators controllerq must be registered last, after other /stats endpoints have been registered.
            $analytics_controllers[] = \Automattic\Woo_Commerce\Admin\API\Reports\Performance_Indicators\Controller::class;
        }
        $controllers = array_merge($analytics_controllers, $controllers);
        if (!did_action('woocommerce_admin_rest_controllers')) {
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
            if (!is_array($controllers)) {
                return;
            }
        }
        $controllers = array_values(array_unique($controllers));
        foreach ($controllers as $controller) {
            if (is_string($controller)) {
                $this->{$controller} = new $controller();
                $this->{$controller}->register_routes();
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
        return array_merge($data_stores, ['report-revenue-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Orders\Stats\Data_Store::class, 'report-orders' => \Automattic\Woo_Commerce\Admin\API\Reports\Orders\Data_Store::class, 'report-orders-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Orders\Stats\Data_Store::class, 'report-products' => \Automattic\Woo_Commerce\Admin\API\Reports\Products\Data_Store::class, 'report-variations' => \Automattic\Woo_Commerce\Admin\API\Reports\Variations\Data_Store::class, 'report-products-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Products\Stats\Data_Store::class, 'report-variations-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Variations\Stats\Data_Store::class, 'report-categories' => \Automattic\Woo_Commerce\Admin\API\Reports\Categories\Data_Store::class, 'report-taxes' => \Automattic\Woo_Commerce\Admin\API\Reports\Taxes\Data_Store::class, 'report-taxes-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Taxes\Stats\Data_Store::class, 'report-coupons' => \Automattic\Woo_Commerce\Admin\API\Reports\Coupons\Data_Store::class, 'report-coupons-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Coupons\Stats\Data_Store::class, 'report-downloads' => \Automattic\Woo_Commerce\Admin\API\Reports\Downloads\Data_Store::class, 'report-downloads-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Downloads\Stats\Data_Store::class, 'admin-note' => \Automattic\Woo_Commerce\Admin\Notes\Data_Store::class, 'report-customers' => \Automattic\Woo_Commerce\Admin\API\Reports\Customers\Data_Store::class, 'report-customers-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Customers\Stats\Data_Store::class, 'report-stock-stats' => \Automattic\Woo_Commerce\Admin\API\Reports\Stock\Stats\Data_Store::class]);
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
        $response_data = $response->get_data();
        $currency_code = $response_data['currency'];
        $currency_symbol = get_woocommerce_currency_symbol($currency_code);
        $response_data['currency_symbol'] = html_entity_decode($currency_symbol);
        $response->set_data($response_data);
        return $response;
    }
}