<?php

declare (strict_types=1);
/**
 * WooCommerce Marketing > Coupons.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Admin\Page_Controller;
/**
 * Contains backend logic for the Coupons feature.
 */
class Coupons
{
    use Coupons_Moved_Trait;
    /**
     * Class instance.
     *
     * @var Coupons instance
     */
    protected static $instance;
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
        if (!is_admin()) {
            return;
        }
        // If the main marketing feature is disabled, don't modify coupon behavior.
        if (!Features::is_enabled('marketing')) {
            return;
        }
        // Only support coupon modifications if coupons are enabled.
        if (!wc_coupons_enabled()) {
            return;
        }
        add_action('admin_enqueue_scripts', $this->maybe_add_marketing_coupon_script(...));
        add_action('woocommerce_register_post_type_shop_coupon', $this->move_coupons(...));
        add_action('admin_head', $this->fix_coupon_menu_highlight(...), 99);
        add_action('admin_menu', $this->maybe_add_coupon_menu_redirect(...));
    }
    /**
     * Maybe add menu item back in original spot to help people transition
     */
    public function maybe_add_coupon_menu_redirect(): void
    {
        if (!$this->should_display_legacy_menu()) {
            return;
        }
        add_submenu_page('woocommerce', __('Coupons', 'woocommerce'), __('Coupons', 'woocommerce'), 'manage_options', 'coupons-moved', $this->coupon_menu_moved(...));
    }
    /**
     * Call back for transition menu item
     */
    public function coupon_menu_moved(): never
    {
        wp_safe_redirect($this->get_legacy_coupon_url(), 301);
        exit;
    }
    /**
     * Modify registered post type shop_coupon
     *
     * @param array $args Array of post type parameters.
     *
     * @return array the filtered parameters.
     */
    public function move_coupons(array $args): array
    {
        $args['show_in_menu'] = current_user_can('manage_woocommerce') ? 'woocommerce-marketing' : true;
        return $args;
    }
    /**
     * Undo WC modifications to $parent_file for 'shop_coupon'
     */
    public function fix_coupon_menu_highlight(): void
    {
        global $parent_file, $post_type;
        if ($post_type === 'shop_coupon') {
            $parent_file = 'woocommerce-marketing';
            // phpcs:ignore WordPress.WP.GlobalVariablesOverride
        }
    }
    /**
     * Maybe add our wc-admin coupon scripts if viewing coupon pages
     */
    public function maybe_add_marketing_coupon_script(): void
    {
        $curent_screen = Page_Controller::get_instance()->get_current_page();
        if (!isset($curent_screen['id']) || $curent_screen['id'] !== 'woocommerce-coupons') {
            return;
        }
        Wc_Admin_Assets::register_style('marketing-coupons', 'style');
        Wc_Admin_Assets::register_script('wp-admin-scripts', 'marketing-coupons', true);
    }
}