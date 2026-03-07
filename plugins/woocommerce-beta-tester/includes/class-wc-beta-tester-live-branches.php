<?php

declare(strict_types=1);
/**
 * Beta Tester Plugin Live Branches feature class.
 *
 * @package WC_Beta_Tester
 */

defined('ABSPATH') || exit;

/**
 * WC_Beta_Tester Live Branches Feature Class.
 */
class WC_Beta_Tester_Live_Branches
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', $this->register_page(...));
        add_action('admin_init', $this->register_scripts(...));
    }

    /**
     * Check if WooCommerce is installed.
     *
     * @return bool - True if WooCommerce is installed, false otherwise.
     */
    private function woocommerce_is_installed(): bool
    {
        return class_exists('WooCommerce');
    }

    /**
     * Register live branches scripts.
     */
    public function register_scripts(): void
    {
        if (! $this->woocommerce_is_installed()) {
            return;
        }

        if (! is_admin()) {
            return;
        }

        $script_path       = '/build/live-branches.js';
        $script_asset_path = __DIR__ . '/../build/live-branches.asset.php';
        $script_asset      = file_exists($script_asset_path)
            ? require $script_asset_path
            : [
                'dependencies' => [],
                'version'      => filemtime($script_path),
            ];
        $script_url        = WC_Beta_Tester::instance()->plugin_url() . $script_path;

        wp_register_script(
            'woocommerce-beta-tester-live-branches',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_enqueue_script('woocommerce-beta-tester-live-branches');
    }

    /**
     * Register live branches page.
     */
    public function register_page(): void
    {
        if (! $this->woocommerce_is_installed()) {
            return;
        }

        if (! function_exists('wc_admin_register_page')) {
            return;
        }

        wc_admin_register_page(
            [
                'id'         => 'woocommerce-beta-tester-live-branches',
                'title'      => __('Live Branches', 'woocommerce-beta-tester'),
                'path'       => '/live-branches',
                'parent'     => 'woocommerce',
                'capability' => 'read',
            ]
        );
    }
}

return new WC_Beta_Tester_Live_Branches();
