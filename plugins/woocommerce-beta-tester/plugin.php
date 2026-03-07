<?php
/**
 * Beta Tester Plugin filters and actions.
 *
 * @package WC_Beta_Tester
 */

add_action(
    'admin_menu',
    function (): void {
        add_management_page(
            'WooCommerce Admin Test Helper',
            'WCA Test Helper',
            'install_plugins',
            'woocommerce-admin-test-helper',
            function (): void {
                ?><div id="woocommerce-admin-test-helper-app-root"></div>
				<?php
            }
        );
    }
);

add_action(
    'wp_loaded',
    function (): void {
        require_once __DIR__ . '/vendor/autoload.php';
        require 'api/api.php';
    }
);

add_filter(
    'woocommerce_admin_get_feature_config',
    function (array $feature_config): array {
        $feature_config['beta-tester-slotfill-examples'] = false;
        $custom_feature_values                           = get_option('wc_admin_helper_feature_values', []);
        foreach ($custom_feature_values as $feature => $value) {
            if (isset($feature_config[ $feature ])) {
                $feature_config[ $feature ] = $value;
            }
        }
        return $feature_config;
    }
);

/**
 * Register the JS.
 */
function enqueue_beta_tester_app_script(): void
{
    if (! defined('WC_ADMIN_APP')) {
        return;
    }
    $screen = get_current_screen();
    if (! $screen || 'tools_page_woocommerce-admin-test-helper' !== $screen->id) {
        return;
    }
    $script_path       = '/build/app.js';
    $script_asset_path = __DIR__ . '/build/app.asset.php';
    $script_asset      = file_exists($script_asset_path)
        ? require $script_asset_path
        : [
            'dependencies' => [],
            'version'      => filemtime($script_path),
        ];
    $script_url        = plugins_url($script_path, __FILE__);

    $script_asset['dependencies'][] = WC_ADMIN_APP; // Add WCA as a dependency to ensure it loads first.

    wp_register_script(
        'woocommerce-admin-test-helper-app',
        $script_url,
        $script_asset['dependencies'],
        $script_asset['version'],
        true
    );
    wp_enqueue_script('woocommerce-admin-test-helper-app');

    $css_file_version = filemtime(__DIR__ . '/build/app.css');

    wp_register_style(
        'wp-components',
        plugins_url('dist/components/style.css', __FILE__),
        [],
        $css_file_version
    );

    wp_register_style(
        'woocommerce-admin-test-helper-app',
        plugins_url('/build/app.css', __FILE__),
        [ 'wp-components' ],
        $css_file_version
    );

    wp_enqueue_style('woocommerce-admin-test-helper-app');
}

add_action('admin_enqueue_scripts', 'enqueue_beta_tester_app_script');
