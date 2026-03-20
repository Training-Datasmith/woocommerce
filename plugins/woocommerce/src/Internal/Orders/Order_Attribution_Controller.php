<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Integrations\WPConsentAPI;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\OrderAttributionMeta;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\OrderUtil;
use Exception;
use WC_Customer;
use WC_Log_Levels;
use WC_Logger_Interface;
use WC_Order;

/**
 * Class OrderAttributionController
 *
 * @since 8.5.0
 */
class OrderAttributionController implements RegisterHooksInterface
{
    use ScriptDebug;
    use OrderAttributionMeta {
        get_prefixed_field_name as public;
    }

    /**
     * The WPConsentAPI integration instance.
     */
    private ?\Automattic\WooCommerce\Internal\Integrations\WPConsentAPI $consent = null;

    /**
     * The FeatureController instance.
     */
    private ?\Automattic\WooCommerce\Internal\Features\FeaturesController $feature_controller = null;

    /**
     * WooCommerce logger class instance.
     *
     * @var WC_Logger_Interface
     */
    private $logger;

    /**
     * The LegacyProxy instance.
     */
    private ?\Automattic\WooCommerce\Proxies\LegacyProxy $proxy = null;

    /**
     * Tracks whether stamp_html_element() has been called in single-output mode during the current request.
     *
     * When wc_order_attribution_allow_multiple_elements filter returns false,
     * this flag prevents duplicate outputs across multiple action hooks within a single request.
     *
     * Note: This flag is reset at the start of each request in on_init() to ensure
     * proper behavior in persistent PHP environments (PHP-FPM, OpCache).
     */
    private static bool $is_stamp_html_called = false;

    /**
     * Initialization method.
     *
     * Takes the place of the constructor within WooCommerce Dependency injection.
     *
     * @internal
     *
     * @param LegacyProxy        $proxy      The legacy proxy.
     * @param FeaturesController $controller The feature controller.
     * @param WPConsentAPI       $consent    The WPConsentAPI integration.
     */
    final public function init(LegacyProxy $proxy, FeaturesController $controller, WPConsentAPI $consent): void
    {
        $this->proxy              = $proxy;
        $this->feature_controller = $controller;
        $this->consent            = $consent;
        $this->logger             = $proxy->call_function('wc_get_logger');
        $this->set_fields_and_prefix();
    }

    /**
     * Register this class instance to the appropriate hooks.
     */
    public function register(): void
    {
        // Don't run during install.
        if (Constants::get_constant('WC_INSTALLING')) {
            return;
        }

        add_action('init', $this->on_init(...));
    }

    /**
     * Hook into WordPress on init.
     */
    public function on_init(): void
    {
        // Bail if the feature is not enabled.
        if (! $this->feature_controller->feature_is_enabled('order_attribution')) {
            return;
        }

        // Reset the static flag at the start of each request to prevent issues in persistent PHP environments.
        self::$is_stamp_html_called = false;

        // Register WPConsentAPI integration.
        $this->consent->register();

        add_action(
            'wp_enqueue_scripts',
            function (): void {
                $this->enqueue_scripts_and_styles();
            }
        );

        add_action(
            'admin_enqueue_scripts',
            function (): void {
                $this->enqueue_admin_scripts_and_styles();
            }
        );

        /**
         * Filter set of actions used to stamp the checkout order attribution HTML container element.
         *
         * @since 9.0.0
         *
         * @param array $stamp_checkout_html_actions The set of actions used to stamp the checkout order attribution HTML container element.
         */
        $stamp_checkout_html_actions = apply_filters(
            'wc_order_attribution_stamp_checkout_html_actions',
            [
                'woocommerce_checkout_billing',
                'woocommerce_after_checkout_billing_form',
                'woocommerce_checkout_shipping',
                'woocommerce_after_order_notes',
                'woocommerce_checkout_after_customer_details',
            ]
        );
        foreach ($stamp_checkout_html_actions as $action) {
            add_action($action, $this->stamp_html_element(...));
        }

        add_action('woocommerce_register_form', $this->stamp_html_element(...));

        // Update order based on submitted fields.
        add_action(
            'woocommerce_checkout_order_created',
            function ($order): void {

                // Check if this order already has any attribution data to prevent duplicates attribution data.
                if ($this->has_attribution($order)) {
                    return;
                }

                // Nonce check is handled by WooCommerce before woocommerce_checkout_order_created hook.
                // phpcs:ignore WordPress.Security.NonceVerification
                $params = $this->get_unprefixed_field_values($_POST);
                /**
                 * Run an action to save order attribution data.
                 *
                 * @since 8.5.0
                 *
                 * @param WC_Order $order The order object.
                 * @param array    $params Unprefixed order attribution data.
                 */
                do_action('woocommerce_order_save_attribution_data', $order, $params);
            }
        );

        add_action(
            'woocommerce_order_save_attribution_data',
            function (\WC_Order $order, array $data): void {
                $source_data = $this->get_source_values($data);
                $this->send_order_tracks($source_data, $order);
                $this->set_order_source_data($source_data, $order);
            },
            10,
            2
        );

        add_action(
            'user_register',
            function ($customer_id): void {
                try {
                    $customer = new WC_Customer($customer_id);
                    $this->set_customer_source_data($customer);
                } catch (Exception $e) {
                    $this->log($e->getMessage(), __METHOD__, WC_Log_Levels::ERROR);
                }
            }
        );

        // Add origin data to the order table.
        add_action(
            'admin_init',
            function (): void {
                $this->register_order_origin_column();
            }
        );

        add_action(
            'woocommerce_new_order',
            function ($order_id, \WC_Order $order): void {
                $this->maybe_set_admin_source($order);
            },
            2,
            10
        );
    }

    /**
     * If the order is created in the admin, set the source type and origin to admin/Web admin.
     * Only execute this if the order is created in the admin interface (or via ajax in the admin interface).
     *
     * @param WC_Order $order The recently created order object.
     *
     * @since 8.5.0
     */
    private function maybe_set_admin_source(WC_Order $order): void
    {

        // For ajax requests, bail if the referer is not an admin page.
        $http_referer     = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'] ?? ''));
        $referer_is_admin = str_starts_with($http_referer, get_admin_url());
        if (! $referer_is_admin && wp_doing_ajax()) {
            return;
        }

        // If not admin interface page, bail.
        if (! is_admin()) {
            return;
        }

        $order->add_meta_data($this->get_meta_prefixed_field_name('source_type'), 'admin');
        $order->save();
    }

    /**
     * Get all of the field names.
     */
    public function get_field_names(): array
    {
        return $this->field_names;
    }

    /**
     * Get the prefix for the fields.
     */
    public function get_prefix(): string
    {
        return $this->field_prefix;
    }

    /**
     * Scripts & styles for custom source tracking and cart tracking.
     */
    private function enqueue_scripts_and_styles(): void
    {
        wp_enqueue_script(
            'sourcebuster-js',
            plugins_url("assets/js/sourcebuster/sourcebuster{$this->get_script_suffix()}.js", WC_PLUGIN_FILE),
            [],
            Constants::get_constant('WC_VERSION'),
            true
        );

        wp_enqueue_script(
            'wc-order-attribution',
            plugins_url("assets/js/frontend/order-attribution{$this->get_script_suffix()}.js", WC_PLUGIN_FILE),
            // Technically we do depend on 'wp-data', 'wc-blocks-checkout' for blocks checkout,
            // but as implementing conditional dependency on the server-side would be too complex,
            // we resolve this condition at the client-side.
            [ 'sourcebuster-js' ],
            Constants::get_constant('WC_VERSION'),
            true
        );

        /**
         * Filter the lifetime of the cookie used for source tracking.
         *
         * @since 8.5.0
         *
         * @param float $lifetime The lifetime of the Sourcebuster cookies in months.
         *
         * The default value forces Sourcebuster into making the cookies valid for the current session only.
         */
        $lifetime = (float) apply_filters('wc_order_attribution_cookie_lifetime_months', 0.00001);

        /**
         * Filter the session length for source tracking.
         *
         * @since 8.5.0
         *
         * @param int $session_length The session length in minutes.
         */
        $session_length = (int) apply_filters('wc_order_attribution_session_length_minutes', 30);

        /**
         * Filter to enable base64 encoding for cookie values.
         *
         * @since 9.0.0
         *
         * @param bool $use_base64_cookies True to enable base64 encoding, default is false.
         */
        $use_base64_cookies = apply_filters('wc_order_attribution_use_base64_cookies', false);

        /**
         * Filter to allow tracking.
         *
         * @since 8.5.0
         *
         * @param bool $allow_tracking True to allow tracking, false to disable.
         */
        $allow_tracking = wc_bool_to_string(apply_filters('wc_order_attribution_allow_tracking', true));

        // Create Order Attribution JS namespace with parameters.
        $namespace = [
            'params' => [
                'lifetime'      => $lifetime,
                'session'       => $session_length,
                'base64'        => $use_base64_cookies,
                'ajaxurl'       => admin_url('admin-ajax.php'),
                'prefix'        => $this->field_prefix,
                'allowTracking' => 'yes' === $allow_tracking,
            ],
            'fields' => $this->fields,
        ];

        wp_localize_script('wc-order-attribution', 'wc_order_attribution', $namespace);
    }

    /**
     * Enqueue the stylesheet for admin pages.
     */
    private function enqueue_admin_scripts_and_styles(): void
    {
        $screen = get_current_screen();
        if ($screen->id !== $this->get_order_screen_id()) {
            return;
        }

        // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
        wp_enqueue_script(
            'woocommerce-order-attribution-admin-js',
            plugins_url("assets/js/admin/order-attribution-admin{$this->get_script_suffix()}.js", WC_PLUGIN_FILE),
            [ 'jquery' ],
            Constants::get_constant('WC_VERSION')
        );
    }

    /**
     * Display the origin column in the orders table.
     *
     * @param int $order_id The order ID.
     */
    private function display_origin_column($order_id): void
    {
        try {
            // Ensure we've got a valid order.
            $order = $this->get_hpos_order_object($order_id);
            $this->output_origin_column($order);
        } catch (Exception) {
            return;
        }
    }

    /**
     * Output the translated origin label for the Origin column in the orders table.
     *
     * Default to "Unknown" if no origin is set.
     *
     * @param WC_Order $order The order object.
     */
    private function output_origin_column(WC_Order $order): void
    {
        $source_type = $order->get_meta($this->get_meta_prefixed_field_name('source_type'));
        $source      = $order->get_meta($this->get_meta_prefixed_field_name('utm_source'));
        $origin      = $this->get_origin_label($source_type, $source);
        echo esc_html($origin);
    }

    /**
     * Handles the `<wc-order-attribution-inputs>` element for checkout forms.
     *
     * @since 9.0.0
     * @deprecated 10.5.0 Use stamp_html_element() instead.
     */
    public function stamp_checkout_html_element_once(): void
    {
        wc_deprecated_function(__METHOD__, '10.5.0', 'stamp_html_element');
        $this->stamp_html_element();
    }

    /**
     * Output `<wc-order-attribution-inputs>` element that contributes the order attribution values to the enclosing form.
     *
     * Used for customer register forms and checkout forms.
     *
     * Note: By default, this method may output multiple instances of the element when called
     * multiple times (e.g., during checkout form pre-generation and actual rendering).
     * The JavaScript layer will remove duplicate elements and ensure only one set of data is submitted.
     */
    public function stamp_html_element(): void
    {
        /**
         * Filter to allow sites to opt back into single-output behavior.
         *
         * @since 10.5.0
         *
         * @param bool $allow_multiple_elements True to allow multiple elements (new behavior), false for single element (old behavior).
         */
        $allow_multiple = apply_filters('wc_order_attribution_allow_multiple_elements', true);

        // If single-output mode is enabled, use the static flag to prevent multiple outputs.
        if (! $allow_multiple && self::$is_stamp_html_called) {
            return;
        }

        printf('<wc-order-attribution-inputs></wc-order-attribution-inputs>');

        if (! $allow_multiple) {
            self::$is_stamp_html_called = true;
        }
    }

    /**
     * Save source data for a Customer object.
     *
     * @param WC_Customer $customer The customer object.
     */
    private function set_customer_source_data(WC_Customer $customer): void
    {
        // Nonce check is handled before user_register hook.
        // phpcs:ignore WordPress.Security.NonceVerification
        foreach ($this->get_source_values($this->get_unprefixed_field_values($_POST)) as $key => $value) {
            $customer->add_meta_data($this->get_meta_prefixed_field_name($key), $value);
        }

        $customer->save_meta_data();
    }

    /**
     * Save source data for an Order object.
     *
     * @param array    $source_data The source data.
     * @param WC_Order $order       The order object.
     */
    private function set_order_source_data(array $source_data, WC_Order $order): void
    {
        // If all the values are empty, bail.
        if (empty(array_filter($source_data))) {
            return;
        }
        foreach ($source_data as $key => $value) {
            $order->add_meta_data($this->get_meta_prefixed_field_name($key), $value);
        }

        $order->save_meta_data();
    }

    /**
     * Log a message as a debug log entry.
     *
     * @param string $message The message to log.
     * @param string $method  The method that is logging the message.
     * @param string $level   The log level.
     */
    private function log(string $message, string $method, string $level = WC_Log_Levels::DEBUG): void
    {
        /**
         * Filter to enable debug mode.
         *
         * @since 8.5.0
         *
         * @param string $enabled 'yes' to enable debug mode, 'no' to disable.
         */
        if ('yes' !== apply_filters('wc_order_attribution_debug_mode_enabled', 'no')) {
            return;
        }

        $this->logger->log(
            $level,
            sprintf('%s %s', $method, $message),
            [ 'source' => 'woocommerce-order-attribution' ]
        );
    }

    /**
     * Send order source data to Tracks.
     *
     * @param array    $source_data The source data.
     * @param WC_Order $order       The order object.
     */
    private function send_order_tracks(array $source_data, WC_Order $order): void
    {
        $origin_label = $this->get_origin_label(
            $source_data['source_type'] ?? '',
            $source_data['utm_source'] ?? '',
            false
        );

        $tracks_data = [
            'order_id'            => $order->get_id(),
            'source_type'         => $source_data['source_type'] ?? '',
            'medium'              => $source_data['utm_medium'] ?? '',
            'source'              => $source_data['utm_source'] ?? '',
            'device_type'         => strtolower($source_data['device_type'] ?? 'unknown'),
            'origin_label'        => strtolower($origin_label),
            'session_pages'       => $source_data['session_pages'] ?? 0,
            'session_count'       => $source_data['session_count'] ?? 0,
            'order_total'         => $order->get_total(),
            'customer_registered' => $order->get_customer_id() ? 'yes' : 'no',
        ];

        if (function_exists('wc_admin_record_tracks_event')) {
            wc_admin_record_tracks_event('order_attribution', $tracks_data);
        }
    }

    /**
     * Get the screen ID for the orders page.
     */
    private function get_order_screen_id(): string
    {
        return OrderUtil::custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id('shop-order') : 'shop_order';
    }

    /**
     * Register the origin column in the orders table.
     *
     * This accounts for the differences in hooks based on whether HPOS is enabled or not.
     */
    private function register_order_origin_column(): void
    {
        $screen_id = $this->get_order_screen_id();

        $add_column = function (array $columns): array {
            $columns['origin'] = esc_html__('Origin', 'woocommerce');

            return $columns;
        };
        // HPOS and non-HPOS use different hooks.
        add_filter("manage_{$screen_id}_columns", $add_column);
        add_filter("manage_edit-{$screen_id}_columns", $add_column);

        $display_column = function ($column_name, $order_id): void {
            if ('origin' !== $column_name) {
                return;
            }
            $this->display_origin_column($order_id);
        };
        // HPOS and non-HPOS use different hooks.
        add_action("manage_{$screen_id}_custom_column", $display_column, 10, 2);
        add_action("manage_{$screen_id}_posts_custom_column", $display_column, 10, 2);
    }

    /**
     * Check if this order already has any attribution data
     *
     * @param WC_Order $order The order object.
     *
     * @since 9.8.0
     */
    public function has_attribution($order): bool
    {
        foreach ($this->field_names as $field) {
            if ($order->meta_exists($this->get_meta_prefixed_field_name($field))) {
                return true;
            }
        }
        return false;
    }
}
