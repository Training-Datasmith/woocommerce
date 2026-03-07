<?php

declare(strict_types=1);
/**
 * REST API endpoints for live branches installation.
 *
 * @package WC_Beta_Tester
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/../../includes/class-wc-beta-tester-live-branches-installer.php';

/**
 * Check if the user has the necessary permissions to perform live branches actions.
 * Avoid using WC functions so user can call these API without WooCommerce active.
 */
function check_live_branches_permissions(): \WP_Error|true
{
    if (! current_user_can('install_plugins')) {
        return new \WP_Error(
            'woocommerce_rest_cannot_edit',
            __('Sorry, you cannot perform this action', 'woocommerce-beta-tester')
        );
    }
    return true;
}

register_woocommerce_admin_test_helper_rest_route(
    '/live-branches/install/latest/v1',
    'install_latest_version',
    [
        'methods'             => 'POST',
        'permission_callback' => 'check_live_branches_permissions',
        'args'                => [
            'include_pre_releases' => [
                'required'          => false,
                'type'              => 'boolean',
                'description'       => 'Whether to include pre-releases in the installation. Defaults to false.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]
);

register_woocommerce_admin_test_helper_rest_route(
    '/live-branches/install/v1',
    'install_version',
    [
        'methods'             => 'POST',
        'permission_callback' => 'check_live_branches_permissions',
        'args'                => [
            'download_url' => [
                'required'          => true,
                'type'              => 'string',
                'description'       => 'The URL to download the WooCommerce plugin zip file.',
                'validate_callback' => fn ($param) => filter_var($param, FILTER_VALIDATE_URL),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'pr_name'      => [
                'required'          => true,
                'type'              => 'string',
                'description'       => 'The name or identifier of the pull request being installed.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'version'      => [
                'required'          => true,
                'type'              => 'string',
                'description'       => 'The version identifier of WooCommerce to be installed.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]
);

register_woocommerce_admin_test_helper_rest_route(
    '/live-branches/deactivate/v1',
    'deactivate_woocommerce',
    [
        'methods'             => 'GET',
        'permission_callback' => 'check_live_branches_permissions',
        'description'         => 'Deactivates the currently active WooCommerce plugin.',
    ]
);

register_woocommerce_admin_test_helper_rest_route(
    '/live-branches/activate/v1',
    'activate_version',
    [
        'methods'             => 'POST',
        'permission_callback' => 'check_live_branches_permissions',
        'args'                => [
            'version' => [
                'required'          => true,
                'type'              => 'string',
                'description'       => 'The version identifier of WooCommerce to activate.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
    ]
);

/**
 * Get the latest WooCommerce pre-release release from GitHub API.
 *
 * @return array|WP_Error Latest pre-release release or error
 */
function get_latest_wc_release($include_pre_releases = false)
{
    // Get all releases including pre-releases
    $response = wp_remote_get('https://api.github.com/repos/woocommerce/woocommerce/releases');

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (! is_array($body) || empty($body)) {
        return new WP_Error('invalid_response', 'GitHub API returned an invalid response');
    }

    foreach ($body as $release) {
        // Ensure the release is a WooCommerce release, not nightly or beta tester releases.
        if (isset($release['target_commitish']) && str_starts_with((string) $release['target_commitish'], 'release/')) {
            // Skip pre-releases if not included.
            if ($include_pre_releases || ! $release['prerelease']) {
                return $release;
            }
        }
    }

    return new WP_Error('no_version_found', 'Could not find any releases on GitHub');
}

/**
 * Respond to POST request to install the latest WooCommerce version.
 *
 * @param Object $request - The request parameter.
 */
function install_latest_version($request)
{
    $params               = json_decode((string) $request->get_body());
    $include_pre_releases = ! empty($params->include_pre_releases);

    $release = get_latest_wc_release($include_pre_releases);
    if (is_wp_error($release)) {
        return $release;
    }

    // Validate the presence of assets in the release.
    if (empty($release['assets']) || ! isset($release['assets'][0])) {
        return new WP_Error('no_assets_found', sprintf('No assets found for the release %s', $release['tag_name']));
    }

    $installer = new WC_Beta_Tester_Live_Branches_Installer();
    $version   = $release['tag_name'];
    $result    = $installer->install(
        $release['assets'][0]['browser_download_url'],
        $version
    );

    if (is_wp_error($result)) {
        return new WP_Error(400, sprintf('Could not install %s with error %s', $version, $result->get_error_message()), '');
    }

    return new WP_REST_Response(
        [
            'ok'      => true,
            'version' => $version,
        ],
        200
    );
}

/**
 * Respond to POST request to install a plugin by download url.
 *
 * @param Object $request - The request parameter.
 */
function install_version($request): \WP_Error|\WP_REST_Response
{
    $params       = json_decode((string) $request->get_body());
    $download_url = $params->download_url;
    $pr_name      = $params->pr_name;
    $version      = $params->version;

    $installer = new WC_Beta_Tester_Live_Branches_Installer();
    $result    = $installer->install($download_url, $version);

    if (is_wp_error($result)) {
        return new WP_Error(400, "Could not install $pr_name with error {$result->get_error_message()}", '');
    }
    return new WP_REST_Response(wp_json_encode([ 'ok' => true ]), 200);
}

/**
 * Respond to POST request to activate a plugin by version.
 *
 * @param Object $request - The request parameter.
 */
function activate_version($request): \WP_Error|\WP_REST_Response
{
    $params  = json_decode((string) $request->get_body());
    $version = $params->version;

    $installer = new WC_Beta_Tester_Live_Branches_Installer();
    $result    = $installer->activate($version);

    if (is_wp_error($result)) {
        return new WP_Error(400, "Could not activate version: $version with error {$result->get_error_message()}", '');
    }
    return new WP_REST_Response(wp_json_encode([ 'ok' => true ]), 200);
}

/**
 * Respond to GET request to deactivate WooCommerce.
 */
function deactivate_woocommerce()
{
    $installer = new WC_Beta_Tester_Live_Branches_Installer();
    $installer->deactivate_woocommerce();

    return new WP_REST_Response(wp_json_encode([ 'ok' => true ]), 200);
}
