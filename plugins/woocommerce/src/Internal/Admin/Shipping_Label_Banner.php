<?php

/**
 * WooCommerce Shipping Label banner.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;
use Automattic\Woo_Commerce\Utilities\Order_Util;
/**
 * Shows print shipping label banner on edit order page.
 */
class Shipping_Label_Banner
{
    /**
     * Singleton for the display rules class
     */
    private ?\Automattic\Woo_Commerce\Internal\Admin\Shipping_Label_Banner_Display_Rules $shipping_label_banner_display_rules = null;
    private const MIN_COMPATIBLE_WCST_VERSION = '2.7.0';
    private const MIN_COMPATIBLE_WCSHIPPING_VERSION = '1.1.0';
    /**
     * Constructor
     */
    public function __construct()
    {
        if (!is_admin()) {
            return;
        }
        add_action('add_meta_boxes', $this->add_meta_boxes(...), 6, 2);
    }
    /**
     * Check if WooCommerce Shipping makes sense for this merchant.
     *
     * @return bool
     */
    private function should_show_meta_box()
    {
        if (!$this->shipping_label_banner_display_rules) {
            $dotcom_connected = null;
            $wcs_version = null;
            if (class_exists(Jetpack_Connection_Manager::class)) {
                $dotcom_connected = (new Jetpack_Connection_Manager())->has_connected_owner();
            }
            if (class_exists('\Automattic\WCShipping\Utils')) {
                $wcs_version = \Automattic\Wc_Shipping\Utils::get_wcshipping_version();
            }
            $incompatible_plugins = class_exists('\WC_Shipping_Fedex_Init') || class_exists('\WC_Shipping_UPS_Init') || class_exists('\WC_Integration_ShippingEasy') || class_exists('\WC_ShipStation_Integration');
            $this->shipping_label_banner_display_rules = new Shipping_Label_Banner_Display_Rules($dotcom_connected, $wcs_version, $incompatible_plugins);
        }
        return $this->shipping_label_banner_display_rules->should_display_banner();
    }
    /**
     * Add metabox to order page.
     */
    public function add_meta_boxes(): void
    {
        if (!Order_Util::is_order_edit_screen()) {
            return;
        }
        if ($this->should_show_meta_box()) {
            add_meta_box('woocommerce-admin-print-label', __('Shipping Label', 'woocommerce'), $this->meta_box(...), null, 'normal', 'high', ['context' => 'shipping_label']);
            add_action('admin_enqueue_scripts', $this->add_print_shipping_label_script(...));
        }
    }
    /**
     * Adds JS to order page to render shipping banner.
     *
     * @param string $hook current page hook.
     */
    public function add_print_shipping_label_script($hook): void
    {
        Wc_Admin_Assets::register_style('print-shipping-label-banner', 'style', ['wp-components']);
        Wc_Admin_Assets::register_script('wp-admin-scripts', 'print-shipping-label-banner', true);
        $wcst_version = null;
        $wcshipping_installed_version = null;
        $order = wc_get_order();
        if (class_exists('\WC_Connect_Loader')) {
            $wcst_version = \WC_Connect_Loader::get_wcs_version();
        }
        $wc_shipping_plugin_file = WP_PLUGIN_DIR . '/woocommerce-shipping/woocommerce-shipping.php';
        if (file_exists($wc_shipping_plugin_file)) {
            $plugin_data = get_plugin_data($wc_shipping_plugin_file);
            $wcshipping_installed_version = $plugin_data['Version'];
        }
        $payload = [
            // If WCS&T is not installed, it's considered compatible.
            'is_wcst_compatible' => $wcst_version ? (int) version_compare($wcst_version, self::MIN_COMPATIBLE_WCST_VERSION, '>=') : 1,
            'order_id' => $order ? $order->get_id() : null,
            // The banner is shown if the plugin is installed but not active, so we need to check if the installed version is compatible.
            'is_incompatible_wcshipping_installed' => $wcshipping_installed_version ? (int) version_compare($wcshipping_installed_version, self::MIN_COMPATIBLE_WCSHIPPING_VERSION, '<') : 0,
        ];
        wp_localize_script('wc-admin-print-shipping-label-banner', 'wcShippingCoreData', $payload);
    }
    /**
     * Render placeholder metabox.
     *
     * @param \WP_Post $post current post.
     * @param array    $args empty args.
     */
    public function meta_box($post, array $args): void
    {
        ?>
		<div id="wc-admin-shipping-banner-root" class="woocommerce <?php 
        echo esc_attr('wc-admin-shipping-banner');
        ?>" data-args="<?php 
        echo esc_attr(wp_json_encode($args['args']));
        ?>">
		</div>
		<?php 
    }
}