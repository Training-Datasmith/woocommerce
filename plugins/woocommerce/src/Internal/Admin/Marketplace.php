<?php

declare (strict_types=1);
/**
 * WooCommerce Marketplace.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use WC_Helper_Options;
use WC_Helper_Updater;
/**
 * Contains backend logic for the Marketplace feature.
 */
class Marketplace
{
    public const MARKETPLACE_TAB_SLUG = 'woo';
    /**
     * Class initialization, to be executed when the class is resolved by the container.
     *
     * @internal
     */
    final public function init(): void
    {
        add_action('init', $this->on_init(...));
    }
    /**
     * Hook into WordPress on init.
     */
    public function on_init(): void
    {
        add_action('admin_menu', $this->register_pages(...), 70);
        add_action('admin_enqueue_scripts', $this->enqueue_scripts(...));
        // Add a Woo Marketplace link to the plugin install action links.
        add_filter('install_plugins_tabs', $this->add_woo_plugin_install_action_link(...));
        add_action('install_plugins_pre_woo', $this->maybe_open_woo_tab(...));
    }
    /**
     * Registers report pages.
     */
    public function register_pages(): void
    {
        if (!function_exists('wc_admin_register_page')) {
            return;
        }
        $marketplace_pages = $this->get_marketplace_pages();
        foreach ($marketplace_pages as $marketplace_page) {
            if (!is_null($marketplace_page)) {
                wc_admin_register_page($marketplace_page);
            }
        }
    }
    /**
     * Get report pages.
     */
    public function get_marketplace_pages()
    {
        $marketplace_pages = [['id' => 'woocommerce-marketplace', 'parent' => 'woocommerce', 'title' => __('Extensions', 'woocommerce') . $this->badge(), 'page_title' => __('Extensions', 'woocommerce'), 'path' => '/extensions']];
        /**
         * The marketplace items used in the menu.
         *
         * @since 8.0
         */
        return apply_filters('woocommerce_marketplace_menu_items', $marketplace_pages);
    }
    private function badge(): string
    {
        $option = WC_Helper_Options::get('my_subscriptions_tab_loaded');
        if (!$option) {
            return WC_Helper_Updater::get_updates_count_html();
        }
        return '';
    }
    /**
     * Enqueue update script.
     *
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_scripts($hook_suffix): void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if ('woocommerce_page_wc-admin' !== $hook_suffix) {
            return;
        }
        if (!isset($_GET['path']) || '/extensions' !== $_GET['path']) {
            return;
        }
        // Enqueue WordPress updates script to enable plugin and theme installs and updates.
        wp_enqueue_script('updates');
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }
    /**
     * Add a Woo Marketplace link to the plugin install action links.
     *
     * @param array $tabs Plugins list tabs.
     */
    public function add_woo_plugin_install_action_link(array $tabs): array
    {
        $tabs[self::MARKETPLACE_TAB_SLUG] = 'WooCommerce Marketplace';
        return $tabs;
    }
    /**
     * Open the Woo tab when the user clicks on the Woo link in the plugin installer.
     */
    public function maybe_open_woo_tab(): void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['tab']) || self::MARKETPLACE_TAB_SLUG !== $_GET['tab']) {
            return;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $woo_url = add_query_arg(['page' => 'wc-admin', 'path' => '/extensions', 'tab' => 'extensions', 'ref' => 'plugins'], admin_url('admin.php'));
        wc_admin_record_tracks_event('marketplace_plugin_install_woo_clicked');
        wp_safe_redirect($woo_url);
        exit;
    }
}