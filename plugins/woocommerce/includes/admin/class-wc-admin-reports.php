<?php

declare(strict_types=1);
/**
 * Admin Reports
 *
 * Functions used for displaying sales and customer reports in admin.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce\Admin\Reports
 * @version     2.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_Admin_Reports', false)) {
    return;
}

/**
 * WC_Admin_Reports Class.
 */
class WC_Admin_Reports
{
    /**
     * Register the hook handlers for integrating with admin.
     */
    public static function register_hook_handlers(): void
    {
        add_filter('woocommerce_after_dashboard_status_widget_parameter', self::get_report_instance(...));
        add_filter('woocommerce_dashboard_status_widget_reports', self::replace_dashboard_status_widget_reports(...));
    }

    /**
     * Register the hook handlers for integrating with orders.
     *
     * @internal
     * @since 10.6.0
     */
    public static function register_orders_hook_handlers(): void
    {
        add_action('woocommerce_delete_shop_order_transients', self::delete_legacy_reports_transients(...), 10, 1);
        add_action('woocommerce_delete_legacy_report_transients', self::delete_legacy_reports_transients(...), 10, 2);
    }

    /**
     * Execute legacy reports transient deletion (sync or async depending on the context)
     *
     * @internal
     * @since 10.6.0
     *
     * @param int  $order_id Order ID (unused, exists for compatibility between the hooks we are integrating with).
     * @param bool $defer    Whether to defer the deletion or execute.
     */
    public static function delete_legacy_reports_transients(int $order_id, bool $defer = true): void
    {
        // Deferring is only making sense on sites without object cache enabled (if enabled, no SQLs being executed).
        if ($defer && ! wp_using_ext_object_cache()) {
            static $skip_consequent;

            // Schedule the deletion, cap the execution to single pending event at any given time.
            $schedule = ! $skip_consequent && ! as_has_scheduled_action('woocommerce_delete_legacy_report_transients', null, 'woocommerce');
            if ($schedule) {
                as_schedule_single_action(time() + MINUTE_IN_SECONDS, 'woocommerce_delete_legacy_report_transients', [ $order_id, false ], 'woocommerce');
            }
            $skip_consequent = true;

            return;
        }

        delete_transient('wc_admin_report');
        foreach (self::get_reports() as $report_group) {
            foreach ($report_group['reports'] as $report_key => $report) {
                delete_transient('wc_report_' . $report_key);
            }
        }
    }

    /**
     * Get an instance of WC_Admin_Report.
     */
    public static function get_report_instance(): \WC_Admin_Report
    {
        include_once __DIR__ . '/reports/class-wc-admin-report.php';
        return new WC_Admin_Report();
    }

    /**
     * Filter handler for replacing the data of the status widget on the Dashboard page.
     *
     * @param array $status_widget_reports The data to display in the status widget.
     */
    public static function replace_dashboard_status_widget_reports(array $status_widget_reports): array
    {
        $report = self::get_report_instance();

        include_once __DIR__ . '/reports/class-wc-report-sales-by-date.php';

        $sales_by_date                 = new WC_Report_Sales_By_Date();
        $sales_by_date->start_date     = strtotime(gmdate('Y-m-01', current_time('timestamp'))); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
        $sales_by_date->end_date       = strtotime(gmdate('Y-m-d', current_time('timestamp'))); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
        $sales_by_date->chart_groupby  = 'day';
        $sales_by_date->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';

        $status_widget_reports['net_sales_link']      = 'admin.php?page=wc-reports&tab=orders&range=month';
        $status_widget_reports['top_seller_link']     = 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=';
        $status_widget_reports['lowstock_link']       = 'admin.php?page=wc-reports&tab=stock&report=low_in_stock';
        $status_widget_reports['outofstock_link']     = 'admin.php?page=wc-reports&tab=stock&report=out_of_stock';
        $status_widget_reports['report_data']         = $sales_by_date->get_report_data();
        $status_widget_reports['get_sales_sparkline'] = $report->get_sales_sparkline(...);

        return $status_widget_reports;
    }

    /**
     * Handles output of the reports page in admin.
     */
    public static function output(): void
    {
        $reports        = self::get_reports();
        $first_tab      = array_keys($reports);
        $current_tab    = ! empty($_GET['tab']) && array_key_exists($_GET['tab'], $reports) ? sanitize_title($_GET['tab']) : $first_tab[0];
        $current_report = isset($_GET['report']) ? sanitize_title($_GET['report']) : current(array_keys($reports[ $current_tab ]['reports']));

        include_once __DIR__ . '/reports/class-wc-admin-report.php';
        include_once __DIR__ . '/views/html-admin-page-reports.php';
    }

    /**
     * Returns the definitions for the reports to show in admin.
     *
     * @return array
     */
    public static function get_reports()
    {
        $reports = [
            'orders'    => [
                'title'   => __('Orders', 'woocommerce'),
                'reports' => [
                    'sales_by_date'     => [
                        'title'       => __('Sales by date', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'sales_by_product'  => [
                        'title'       => __('Sales by product', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'sales_by_category' => [
                        'title'       => __('Sales by category', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'coupon_usage'      => [
                        'title'       => __('Coupons by date', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'downloads'         => [
                        'title'       => __('Customer downloads', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                ],
            ],
            'customers' => [
                'title'   => __('Customers', 'woocommerce'),
                'reports' => [
                    'customers'     => [
                        'title'       => __('Customers vs. guests', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'customer_list' => [
                        'title'       => __('Customer list', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                ],
            ],
            'stock'     => [
                'title'   => __('Stock', 'woocommerce'),
                'reports' => [
                    'low_in_stock' => [
                        'title'       => __('Low in stock', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'out_of_stock' => [
                        'title'       => __('Out of stock', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'most_stocked' => [
                        'title'       => __('Most stocked', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                ],
            ],
        ];

        if (wc_tax_enabled()) {
            $reports['taxes'] = [
                'title'   => __('Taxes', 'woocommerce'),
                'reports' => [
                    'taxes_by_code' => [
                        'title'       => __('Taxes by code', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                    'taxes_by_date' => [
                        'title'       => __('Taxes by date', 'woocommerce'),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => self::get_report(...),
                    ],
                ],
            ];
        }

        /**
         * Filter the list and add reports to the legacy _WooCommerce > Reports_.
         *
         * Array items should be in the format of
         *
         * $reports['automatewoo'] = array(
         *     'title'   => 'AutomateWoo',
         *     'reports' => array(
         *         'runs_by_date' => array(
         *             'title'       => __( 'Workflow Runs', 'automatewoo' ),
         *             'description' => '',
         *             'hide_title'  => false,
         *             'callback'    => array( $this, 'get_runs_by_date' ),
         *         ),
         *         // ...
         *     ),
         * );
         *
         * This filter has a colliding name with the one in Automattic\WooCommerce\Admin\API\Reports\Controller.
         * To make sure your code runs in the context of the legacy _WooCommerce > Reports_ screen, and not the REST endpoint,
         * use the following:
         *
         * add_filter( 'woocommerce_admin_reports',
         *     function( $reports ) {
         *         if ( is_admin() ) {
         *             // ...
         *
         * @param array $reports The associative array of reports.
         */
        $reports = apply_filters('woocommerce_admin_reports', $reports);
        $reports = apply_filters('woocommerce_reports_charts', $reports); // Backwards compatibility.

        foreach ($reports as $key => &$report_group) {
            if (isset($report_group['charts'])) {
                $report_group['reports'] = $report_group['charts'];
            }

            // Silently ignore reports given for the filter in Automattic\WooCommerce\Admin\API\Reports\Controller.
            if (! isset($report_group['reports'])) {
                unset($reports[ $key ]);
                continue;
            }

            foreach ($report_group['reports'] as &$report) {
                if (isset($report['function'])) {
                    $report['callback'] = $report['function'];
                }
            }
        }

        return $reports;
    }

    /**
     * Get a report from our reports subfolder.
     *
     * @param string $name
     */
    public static function get_report($name): void
    {
        $name  = sanitize_title(str_replace('_', '-', $name));
        $class = 'WC_Report_' . str_replace('-', '_', $name);

        include_once apply_filters('wc_admin_reports_path', 'reports/class-wc-report-' . $name . '.php', $name, $class);

        if (! class_exists($class)) {
            return;
        }

        $report = new $class();
        $report->output_report();
    }
}
