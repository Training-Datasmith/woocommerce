<?php

declare(strict_types=1);
/**
 * Beta Tester Admin Assets class
 *
 * @package WC_Beta_Tester
 */

defined('ABSPATH') || exit;

/**
 * WC_Beta_Tester_Admin_Assets Class.
 */
class WC_Beta_Tester_Admin_Assets
{
    /**
     * Hook in tabs.
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', $this->admin_scripts(...));
    }

    /**
     * Enqueue scripts.
     */
    public function admin_scripts(): void
    {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        $suffix  = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $version = WC_VERSION;

        // Need admin styles for the modal.
        wp_register_style('wc-beta-tester-admin', WC_Beta_Tester::instance()->plugin_url() . '/assets/css/admin.css', [ 'woocommerce_admin_styles' ], WC_BETA_TESTER_VERSION);

        // Register scripts.
        wp_register_script('wc-beta-tester-version-info', WC_Beta_Tester::instance()->plugin_url() . '/assets/js/version-information' . $suffix . '.js', [ 'wc-backbone-modal' ], WC_BETA_TESTER_VERSION, false);
        wp_register_script('wc-beta-tester-version-picker', WC_Beta_Tester::instance()->plugin_url() . '/assets/js/version-picker' . $suffix . '.js', [ 'wc-backbone-modal' ], WC_BETA_TESTER_VERSION, false);

        wp_localize_script(
            'wc-beta-tester-version-info',
            'wc_beta_tester_version_info_params',
            [
                'version'     => $version,
                /* translators: %s: Release version number */
                'description' => sprintf(__('Release of version %s', 'woocommerce-beta-tester'), $version),
            ]
        );

        wp_localize_script(
            'wc-beta-tester-version-picker',
            'wc_beta_tester_version_picker_params',
            [
                'i18n_pick_version' => __('Please pick a WooCommerce version.', 'woocommerce-beta-tester'),
            ]
        );

        if (in_array($screen_id, [ 'plugins_page_wc-beta-tester', 'plugins_page_wc-beta-tester-version-picker' ], true)) {
            wp_enqueue_style('wc-beta-tester-admin');
            wp_enqueue_script('wc-beta-tester-version-info');
            wp_enqueue_script('wc-beta-tester-version-picker');
        }
    }
}

return new WC_Beta_Tester_Admin_Assets();
