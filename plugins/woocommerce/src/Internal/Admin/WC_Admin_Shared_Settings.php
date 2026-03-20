<?php

declare (strict_types=1);
/**
 * Manages the WC Admin settings that need to be pre-loaded.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use Automattic\Woo_Commerce\Admin\Page_Controller;
defined('ABSPATH') || exit;
/**
 * \Automattic\WooCommerce\Internal\Admin\WCAdminSharedSettings class.
 */
class Wc_Admin_Shared_Settings
{
    /**
     * Settings prefix used for the window.wcSettings object.
     */
    private string $settings_prefix = 'admin';
    /**
     * Class instance.
     *
     * @var WCAdminSharedSettings instance
     */
    protected static $instance;
    /**
     * Hook into WooCommerce Blocks.
     */
    protected function __construct()
    {
        if (did_action('woocommerce_blocks_loaded')) {
            $this->on_woocommerce_blocks_loaded();
        } else {
            add_action('woocommerce_blocks_loaded', $this->on_woocommerce_blocks_loaded(...), 10);
        }
    }
    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Adds settings to the Blocks AssetDataRegistry when woocommerce_blocks is loaded.
     */
    public function on_woocommerce_blocks_loaded(): void
    {
        // Ensure we only add admin settings on the admin.
        if (!is_admin()) {
            return;
        }
        if (class_exists(\Automattic\Woo_Commerce\Blocks\Assets\Asset_Data_Registry::class)) {
            \Automattic\Woo_Commerce\Blocks\Package::container()->get(\Automattic\Woo_Commerce\Blocks\Assets\Asset_Data_Registry::class)->add(
                $this->settings_prefix,
                /**
                 * Filters the shared settings that are passed to the client.
                 *
                 * @since 6.4.0
                 */
                fn() => apply_filters('woocommerce_admin_shared_settings', [])
            );
            add_action('admin_enqueue_scripts', function (): void {
                if (!Page_Controller::is_admin_or_embed_page()) {
                    return;
                }
                // Enqueue deprecation scripts (client/wp-admin-scripts/wcsettings-deprecation/index.js).
                Wc_Admin_Assets::register_script('wp-admin-scripts', 'wcsettings-deprecation', true);
            });
        }
    }
}