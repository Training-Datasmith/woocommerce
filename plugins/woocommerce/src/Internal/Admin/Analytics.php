<?php

declare (strict_types=1);
/**
 * WooCommerce Analytics.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use Automattic\Woo_Commerce\Admin\API\Reports\Cache;
use Automattic\Woo_Commerce\Admin\API\Reports\Orders\Stats\Data_Store as OrderStatsDataStore;
use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Internal\Data_Stores\Orders\Orders_Table_Data_Store;
use Automattic\Woo_Commerce\Internal\Features\Features_Controller;
use Automattic\Woo_Commerce\Utilities\Order_Util;
/**
 * Contains backend logic for the Analytics feature.
 */
class Analytics
{
    /**
     * Option name used to toggle this feature.
     */
    public const TOGGLE_OPTION_NAME = 'woocommerce_analytics_enabled';
    /**
     * Clear cache tool identifier.
     */
    public const CACHE_TOOL_ID = 'clear_woocommerce_analytics_cache';
    /**
     * Class instance.
     *
     * @var Analytics instance
     */
    protected static $instance;
    /**
     * Determines if the feature has been toggled on or off.
     *
     * @var boolean
     */
    protected static $is_updated = false;
    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Hook into WooCommerce.
     */
    public function __construct()
    {
        add_action('update_option_' . self::TOGGLE_OPTION_NAME, $this->reload_page_on_toggle(...), 10, 2);
        add_action('woocommerce_settings_saved', $this->maybe_reload_page(...));
        if (!Features::is_enabled('analytics')) {
            return;
        }
        add_filter('woocommerce_component_settings_preload_endpoints', $this->add_preload_endpoints(...));
        add_filter('woocommerce_admin_get_user_data_fields', $this->add_user_data_fields(...));
        add_action('admin_menu', $this->register_pages(...));
        add_filter('woocommerce_debug_tools', $this->register_cache_clear_tool(...));
        add_filter('woocommerce_debug_tools', $this->register_regenerate_order_fulfillment_status_tool(...), 12);
    }
    /**
     * Add the feature toggle to the features settings.
     *
     * @deprecated 7.0 The WooCommerce Admin features are now handled by the WooCommerce features engine (see the FeaturesController class).
     *
     * @param array $features Feature sections.
     * @return array
     */
    public static function add_feature_toggle($features)
    {
        return $features;
    }
    /**
     * Reloads the page when the option is toggled to make sure all Analytics features are loaded.
     *
     * @param string $old_value Old value.
     * @param string $value     New value.
     */
    public static function reload_page_on_toggle($old_value, $value): void
    {
        if ($old_value === $value) {
            return;
        }
        self::$is_updated = true;
    }
    /**
     * Reload the page if the setting has been updated.
     */
    public static function maybe_reload_page(): void
    {
        if (!isset($_SERVER['REQUEST_URI']) || !self::$is_updated) {
            return;
        }
        wp_safe_redirect(wp_unslash($_SERVER['REQUEST_URI']));
        exit;
    }
    /**
     * Preload data from the countries endpoint.
     *
     * @param array $endpoints Array of preloaded endpoints.
     */
    public function add_preload_endpoints(array $endpoints): array
    {
        $screen_id = function_exists('get_current_screen') && get_current_screen() ? get_current_screen()->id : '';
        // Only preload endpoints on wc-admin pages.
        if ('woocommerce_page_wc-admin' === $screen_id) {
            $endpoints['performanceIndicators'] = '/wc-analytics/reports/performance-indicators/allowed';
            $endpoints['leaderboards'] = '/wc-analytics/leaderboards/allowed';
        }
        return $endpoints;
    }
    /**
     * Adds fields so that we can store user preferences for the columns to display on a report.
     *
     * @param array $user_data_fields User data fields.
     */
    public function add_user_data_fields($user_data_fields): array
    {
        return array_merge($user_data_fields, ['categories_report_columns', 'coupons_report_columns', 'customers_report_columns', 'orders_report_columns', 'products_report_columns', 'revenue_report_columns', 'taxes_report_columns', 'variations_report_columns', 'dashboard_sections', 'dashboard_chart_type', 'dashboard_chart_interval', 'dashboard_leaderboard_rows', 'order_attribution_install_banner_dismissed', 'scheduled_updates_promotion_notice_dismissed']);
    }
    /**
     * Register the cache clearing tool on the WooCommerce > Status > Tools page.
     *
     * @param array $debug_tools Available debug tool registrations.
     * @return array Filtered debug tool registrations.
     */
    public function register_cache_clear_tool(array $debug_tools): array
    {
        $settings_url = add_query_arg(['page' => 'wc-admin', 'path' => '/analytics/settings'], get_admin_url(null, 'admin.php'));
        $debug_tools[self::CACHE_TOOL_ID] = ['name' => __('Clear analytics cache', 'woocommerce'), 'button' => __('Clear', 'woocommerce'), 'desc' => sprintf(
            /* translators: 1: opening link tag, 2: closing tag */
            __('This tool will reset the cached values used in WooCommerce Analytics. If numbers still look off, try %1$sReimporting Historical Data%2$s.', 'woocommerce'),
            '<a href="' . esc_url($settings_url) . '">',
            '</a>'
        ), 'callback' => $this->run_clear_cache_tool(...)];
        return $debug_tools;
    }
    /**
     * Register the regenerate order fulfillment status tool on the WooCommerce > Status > Tools page.
     *
     * @param array $debug_tools Available debug tool registrations.
     * @return array Filtered debug tool registrations.
     */
    public function register_regenerate_order_fulfillment_status_tool(array $debug_tools): array
    {
        // Check if the fulfillments feature is enabled.
        $container = wc_get_container();
        $features_controller = $container->get(Features_Controller::class);
        if (!$features_controller->feature_is_enabled('fulfillments')) {
            return $debug_tools;
        }
        // If the order fulfillment status has already been regenerated, don't register the tool again.
        if (true === (bool) get_option('woocommerce_analytics_order_fulfillment_status_regenerated')) {
            return $debug_tools;
        }
        $debug_tools['regenerate_order_fulfillment_status'] = ['name' => __('Regenerate order fulfillment status for Analytics', 'woocommerce'), 'button' => __('Regenerate', 'woocommerce'), 'desc' => __('This tool will regenerate the order fulfillment status for all orders and update the Analytics data using a direct SQL query.', 'woocommerce'), 'callback' => $this->run_regenerate_order_fulfillment_status_tool(...)];
        return $debug_tools;
    }
    /**
     * Regenerate order fulfillment status directly using SQL.
     *
     * @return string Success message or error message.
     */
    public function run_regenerate_order_fulfillment_status_tool()
    {
        global $wpdb;
        // Check if the column exists, create it if not.
        if (!Order_Stats_Data_Store::has_fulfillment_status_column()) {
            $create_column_result = Order_Stats_Data_Store::add_fulfillment_status_column();
            if (true !== $create_column_result) {
                return sprintf(
                    /* translators: %s: error message */
                    __('Failed to create fulfillment status column: %s', 'woocommerce'),
                    $create_column_result
                );
            }
        }
        $order_stats_table = $wpdb->prefix . 'wc_order_stats';
        // If HPOS is enabled, use the wc_orders_meta table, else use wp_postmeta.
        if (Order_Util::custom_orders_table_usage_is_enabled()) {
            $order_meta_table = Orders_Table_Data_Store::get_meta_table_name();
            $order_meta_column = 'order_id';
        } else {
            $order_meta_table = $wpdb->postmeta;
            $order_meta_column = 'post_id';
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $updated = $wpdb->query($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table and column names cannot be prepared.
            "UPDATE {$order_stats_table} os INNER JOIN {$order_meta_table} om ON os.order_id = om.{$order_meta_column}\n\t\t\t\tSET os.fulfillment_status = CASE\n\t\t\t\t\tWHEN om.meta_value = %s THEN NULL\n\t\t\t\t\tELSE om.meta_value\n\t\t\t\tEND\n\t\t\t\tWHERE om.meta_key = %s",
            'no_fulfillments',
            '_fulfillment_status'
        ));
        if (false === $updated) {
            return __('Failed to update order fulfillment status. Please check the database logs for errors.', 'woocommerce');
        }
        // Mark as completed.
        update_option('woocommerce_analytics_order_fulfillment_status_regenerated', true, false);
        return sprintf(
            /* translators: %d: number of orders updated */
            __('Successfully updated fulfillment status for %d orders.', 'woocommerce'),
            $updated
        );
    }
    /**
     * Registers report pages.
     */
    public function register_pages(): void
    {
        $report_pages = self::get_report_pages();
        foreach ($report_pages as $report_page) {
            if (!is_null($report_page)) {
                wc_admin_register_page($report_page);
            }
        }
    }
    /**
     * Get report pages.
     */
    public static function get_report_pages()
    {
        $overview_page = ['id' => 'woocommerce-analytics', 'title' => __('Analytics', 'woocommerce'), 'path' => '/analytics/overview', 'icon' => 'dashicons-chart-bar', 'position' => 57];
        $report_pages = [$overview_page, ['id' => 'woocommerce-analytics-overview', 'title' => __('Overview', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/overview'], ['id' => 'woocommerce-analytics-products', 'title' => __('Products', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/products'], ['id' => 'woocommerce-analytics-revenue', 'title' => __('Revenue', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/revenue'], ['id' => 'woocommerce-analytics-orders', 'title' => __('Orders', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/orders'], ['id' => 'woocommerce-analytics-variations', 'title' => __('Variations', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/variations'], ['id' => 'woocommerce-analytics-categories', 'title' => __('Categories', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/categories'], ['id' => 'woocommerce-analytics-coupons', 'title' => __('Coupons', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/coupons'], ['id' => 'woocommerce-analytics-taxes', 'title' => __('Taxes', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/taxes'], ['id' => 'woocommerce-analytics-downloads', 'title' => __('Downloads', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/downloads'], 'yes' === get_option('woocommerce_manage_stock') ? ['id' => 'woocommerce-analytics-stock', 'title' => __('Stock', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/stock'] : null, ['id' => 'woocommerce-analytics-customers', 'title' => __('Customers', 'woocommerce'), 'parent' => 'woocommerce', 'path' => '/customers'], ['id' => 'woocommerce-analytics-settings', 'title' => __('Settings', 'woocommerce'), 'parent' => 'woocommerce-analytics', 'path' => '/analytics/settings']];
        /**
         * The analytics report items used in the menu.
         *
         * @since 6.4.0
         */
        return apply_filters('woocommerce_analytics_report_menu_items', $report_pages);
    }
    /**
     * "Clear" analytics cache by invalidating it.
     */
    public function run_clear_cache_tool()
    {
        Cache::invalidate();
        return __('Analytics cache cleared.', 'woocommerce');
    }
}