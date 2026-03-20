<?php

declare (strict_types=1);
/**
 * REST API Plugins Controller
 *
 * Handles requests to install and activate dependent plugins.
 */
namespace Automattic\Woo_Commerce\Admin\API;

use Automattic\Woo_Commerce\Admin\Plugins_Helper;
use Automattic\Woo_Commerce\Internal\Admin\Onboarding\Onboarding_Profile;
defined('ABSPATH') || exit;
/**
 * Plugins Controller.
 *
 * @internal
 * @extends \WC_REST_Data_Controller
 */
class Plugins extends \WC_REST_Data_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-admin';
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'plugins';
    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/install', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->install_plugins(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/install/status', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_installation_status(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/install/status/(?P<job_id>[a-z0-9_\-]+)', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_job_installation_status(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/active', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->active_plugins(...), 'permission_callback' => $this->get_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/installed', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->installed_plugins(...), 'permission_callback' => $this->get_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/activate', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->activate_plugins(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/activate/status', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_activation_status(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/activate/status/(?P<job_id>[a-z0-9_\-]+)', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_job_activation_status(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/connect-jetpack', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->connect_jetpack(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_connect_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/request-wccom-connect', [['methods' => 'POST', 'callback' => $this->request_wccom_connect(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_connect_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/finish-wccom-connect', [['methods' => 'POST', 'callback' => $this->finish_wccom_connect(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_connect_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/connect-wcpay', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->connect_wcpay(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_connect_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/connect-square', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->connect_square(...), 'permission_callback' => $this->update_item_permissions_check(...)], 'schema' => $this->get_connect_schema(...)]);
    }
    /**
     * Check if a given request has access to manage plugins.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|boolean
     */
    public function update_item_permissions_check($request)
    {
        if (!current_user_can('install_plugins')) {
            return new \WP_Error('woocommerce_rest_cannot_update', __('Sorry, you cannot manage plugins.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Install the requested plugin.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|array Plugin Status
     */
    public function install_plugin($request)
    {
        wc_deprecated_function('install_plugin', '4.3', '\Automattic\WooCommerce\Admin\API\Plugins()->install_plugins');
        // This method expects a `plugin` argument to be sent, install plugins requires plugins.
        $request['plugins'] = $request['plugin'];
        return self::install_plugins($request);
    }
    /**
     * Installs the requested plugins.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|array Plugin Status
     */
    public function install_plugins($request)
    {
        $plugins = explode(',', (string) $request['plugins']);
        $source = !empty($request['source']) ? $request['source'] : null;
        if (empty($request['plugins']) || !is_array($plugins)) {
            return new \WP_Error('woocommerce_rest_invalid_plugins', __('Plugins must be a non-empty array.', 'woocommerce'), 404);
        }
        if (isset($request['async']) && $request['async']) {
            $job_id = Plugins_Helper::schedule_install_plugins($plugins);
            return ['data' => ['job_id' => $job_id, 'plugins' => $plugins], 'message' => __('Plugin installation has been scheduled.', 'woocommerce')];
        }
        $data = Plugins_Helper::install_plugins($plugins, null, $source);
        // Gather some plugin details for each installed plugin.
        $plugin_details = [];
        if (is_array($data['installed'])) {
            foreach ($data['installed'] as $plugin_slug) {
                $plugin_data = Plugins_Helper::get_plugin_data($plugin_slug);
                if (empty($plugin_data)) {
                    continue;
                }
                $plugin_details[$plugin_slug] = ['name' => $plugin_data['Name'], 'description' => $plugin_data['Description'], 'uri' => $plugin_data['PluginURI'], 'version' => $plugin_data['Version']];
            }
        }
        return ['data' => ['installed' => $data['installed'], 'results' => $data['results'], 'install_time' => $data['time'], 'plugin_details' => $plugin_details], 'errors' => $data['errors'], 'success' => count($data['errors']->errors) === 0, 'message' => count($data['errors']->errors) === 0 ? __('Plugins were successfully installed.', 'woocommerce') : __('There was a problem installing some of the requested plugins.', 'woocommerce')];
    }
    /**
     * Returns a list of recently scheduled installation jobs.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return array Jobs.
     */
    public function get_installation_status($request)
    {
        return Plugins_Helper::get_installation_status();
    }
    /**
     * Returns a list of recently scheduled installation jobs.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return array Job.
     */
    public function get_job_installation_status($request)
    {
        $job_id = $request->get_param('job_id');
        $jobs = Plugins_Helper::get_installation_status($job_id);
        return reset($jobs);
    }
    /**
     * Returns a list of active plugins in API format.
     *
     * @return array Active plugins
     */
    public static function active_plugins()
    {
        return ['plugins' => array_values(Plugins_Helper::get_active_plugin_slugs())];
    }
    /**
     * Returns a list of active plugins.
     *
     * @internal
     * @return array Active plugins
     */
    public static function get_active_plugins()
    {
        $data = self::active_plugins();
        return $data['plugins'];
    }
    /**
     * Returns a list of installed plugins.
     *
     * @return array Installed plugins
     */
    public function installed_plugins()
    {
        return ['plugins' => Plugins_Helper::get_installed_plugin_slugs()];
    }
    /**
     * Activate the requested plugin.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|array Plugin Status
     */
    public function activate_plugins($request)
    {
        $plugins = explode(',', (string) $request['plugins']);
        if (empty($request['plugins']) || !is_array($plugins)) {
            return new \WP_Error('woocommerce_rest_invalid_plugins', __('Plugins must be a non-empty array.', 'woocommerce'), 404);
        }
        if (isset($request['async']) && $request['async']) {
            $job_id = Plugins_Helper::schedule_activate_plugins($plugins);
            return ['data' => ['job_id' => $job_id, 'plugins' => $plugins], 'message' => __('Plugin activation has been scheduled.', 'woocommerce')];
        }
        $data = Plugins_Helper::activate_plugins($plugins);
        // Gather some plugin details for each activated plugin.
        $plugin_details = [];
        if (is_array($data['activated'])) {
            foreach ($data['activated'] as $plugin_slug) {
                $plugin_data = Plugins_Helper::get_plugin_data($plugin_slug);
                if (empty($plugin_data)) {
                    continue;
                }
                $plugin_details[$plugin_slug] = ['name' => $plugin_data['Name'], 'description' => $plugin_data['Description'], 'uri' => $plugin_data['PluginURI'], 'version' => $plugin_data['Version']];
            }
        }
        return ['data' => ['activated' => $data['activated'], 'active' => $data['active'], 'plugin_details' => $plugin_details], 'errors' => $data['errors'], 'success' => count($data['errors']->errors) === 0, 'message' => count($data['errors']->errors) === 0 ? __('Plugins were successfully activated.', 'woocommerce') : __('There was a problem activating some of the requested plugins.', 'woocommerce')];
    }
    /**
     * Returns a list of recently scheduled activation jobs.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return array Job.
     */
    public function get_activation_status($request)
    {
        return Plugins_Helper::get_activation_status();
    }
    /**
     * Returns a list of recently scheduled activation jobs.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return array Jobs.
     */
    public function get_job_activation_status($request)
    {
        $job_id = $request->get_param('job_id');
        $jobs = Plugins_Helper::get_activation_status($job_id);
        return reset($jobs);
    }
    /**
     * Generates a Jetpack Connect URL.
     *
     * @param  \WP_REST_Request $request Full details about the request.
     * @return \WP_Error|array Connection URL for Jetpack
     */
    public function connect_jetpack($request)
    {
        if (!class_exists('\Jetpack')) {
            return new \WP_Error('woocommerce_rest_jetpack_not_active', __('Jetpack is not installed or active.', 'woocommerce'), 404);
        }
        // phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
        $redirect_url = apply_filters('woocommerce_admin_onboarding_jetpack_connect_redirect_url', esc_url_raw($request['redirect_url']));
        $connect_url = \Jetpack::init()->build_connect_url(true, $redirect_url, 'woocommerce-onboarding');
        $calypso_env = defined('WOOCOMMERCE_CALYPSO_ENVIRONMENT') && in_array(WOOCOMMERCE_CALYPSO_ENVIRONMENT, ['development', 'wpcalypso', 'horizon', 'stage'], true) ? WOOCOMMERCE_CALYPSO_ENVIRONMENT : 'production';
        $connect_url = add_query_arg(['calypso_env' => $calypso_env], $connect_url);
        return ['slug' => 'jetpack', 'name' => __('Jetpack', 'woocommerce'), 'connectAction' => $connect_url];
    }
    /**
     *  Kicks off the WCCOM Connect process.
     *
     * @return \WP_Error|array Connection URL for WooCommerce.com
     */
    public function request_wccom_connect()
    {
        include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php';
        if (!class_exists('WC_Helper_API')) {
            return new \WP_Error('woocommerce_rest_helper_not_active', __('There was an error loading the WooCommerce.com Helper API.', 'woocommerce'), 404);
        }
        $redirect_uri = wc_admin_url('&task=connect&wccom-connected=1');
        $request = \WC_Helper_API::post('oauth/request_token', ['body' => ['home_url' => home_url(), 'redirect_uri' => $redirect_uri]]);
        $code = wp_remote_retrieve_response_code($request);
        if (200 !== $code) {
            return new \WP_Error('woocommerce_rest_helper_connect', __('There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce'), 500);
        }
        $secret = json_decode(wp_remote_retrieve_body($request));
        if (empty($secret)) {
            return new \WP_Error('woocommerce_rest_helper_connect', __('There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce'), 500);
        }
        do_action('woocommerce_helper_connect_start');
        $connect_url = add_query_arg(['home_url' => rawurlencode(home_url()), 'redirect_uri' => rawurlencode($redirect_uri), 'secret' => rawurlencode((string) $secret), 'wccom-from' => 'onboarding'], \WC_Helper_API::url('oauth/authorize'));
        if (defined('WOOCOMMERCE_CALYPSO_ENVIRONMENT') && in_array(WOOCOMMERCE_CALYPSO_ENVIRONMENT, ['development', 'wpcalypso', 'horizon', 'stage'], true)) {
            $connect_url = add_query_arg(['calypso_env' => WOOCOMMERCE_CALYPSO_ENVIRONMENT], $connect_url);
        }
        return ['connectAction' => $connect_url];
    }
    /**
     * Finishes connecting to WooCommerce.com.
     *
     * @param  object $rest_request Request details.
     * @return \WP_Error|array Contains success status.
     */
    public function finish_wccom_connect($rest_request)
    {
        include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php';
        include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php';
        include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-updater.php';
        include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-options.php';
        if (!class_exists('WC_Helper_API')) {
            return new \WP_Error('woocommerce_rest_helper_not_active', __('There was an error loading the WooCommerce.com Helper API.', 'woocommerce'), 404);
        }
        // Obtain an access token.
        $request = \WC_Helper_API::post('oauth/access_token', ['body' => [
            'request_token' => wp_unslash($rest_request['request_token']),
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            'home_url' => home_url(),
        ]]);
        $code = wp_remote_retrieve_response_code($request);
        if (200 !== $code) {
            return new \WP_Error('woocommerce_rest_helper_connect', __('There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce'), 500);
        }
        $access_token = json_decode(wp_remote_retrieve_body($request), true);
        if (!$access_token) {
            return new \WP_Error('woocommerce_rest_helper_connect', __('There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce'), 500);
        }
        \WC_Helper_Options::update('auth', ['access_token' => $access_token['access_token'], 'access_token_secret' => $access_token['access_token_secret'], 'site_id' => $access_token['site_id'], 'user_id' => get_current_user_id(), 'updated' => time()]);
        if (!\WC_Helper::_flush_authentication_cache()) {
            \WC_Helper_Options::update('auth', []);
            return new \WP_Error('woocommerce_rest_helper_connect', __('There was an error connecting to WooCommerce.com. Please try again.', 'woocommerce'), 500);
        }
        delete_transient('_woocommerce_helper_subscriptions');
        \WC_Helper_Updater::flush_updates_cache();
        do_action('woocommerce_helper_connected');
        return ['success' => true];
    }
    /**
     * Returns a URL that can be used to connect to Square.
     *
     * @return \WP_Error|array Connect URL.
     */
    public function connect_square()
    {
        if (!class_exists('\WooCommerce\Square\Handlers\Connection')) {
            return new \WP_Error('woocommerce_rest_helper_connect', __('There was an error connecting to Square.', 'woocommerce'), 500);
        }
        $has_cbd_industry = false;
        if ('US' === WC()->countries->get_base_country()) {
            $profile = get_option(Onboarding_Profile::DATA_OPTION, []);
            if (!empty($profile['industry'])) {
                $has_cbd_industry = in_array('cbd-other-hemp-derived-products', array_column($profile['industry'], 'slug'), true);
            }
        }
        if ($has_cbd_industry) {
            $url = 'https://squareup.com/t/f_partnerships/d_referrals/p_woocommerce/c_general/o_none/l_us/dt_alldevice/pr_payments/?route=/solutions/cbd';
        } else {
            $url = \Woo_Commerce\Square\Handlers\Connection::CONNECT_URL_PRODUCTION;
        }
        $redirect_url = wp_nonce_url(wc_admin_url('&task=payments&method=square&square-connect-finish=1'), 'wc_square_connected');
        $args = ['redirect' => rawurlencode(rawurlencode($redirect_url)), 'scopes' => implode(',', ['MERCHANT_PROFILE_READ', 'PAYMENTS_READ', 'PAYMENTS_WRITE', 'ORDERS_READ', 'ORDERS_WRITE', 'CUSTOMERS_READ', 'CUSTOMERS_WRITE', 'SETTLEMENTS_READ', 'ITEMS_READ', 'ITEMS_WRITE', 'INVENTORY_READ', 'INVENTORY_WRITE'])];
        $connect_url = add_query_arg($args, $url);
        return ['connectUrl' => $connect_url];
    }
    /**
     * Returns a URL that can be used to point the merchant to the WooPayments onboarding flow.
     *
     * @return \WP_Error|array Connect URL.
     */
    public function connect_wcpay()
    {
        if (!class_exists('WC_Payments')) {
            return new \WP_Error('woocommerce_rest_helper_connect', __('There was an error communicating with the WooPayments plugin.', 'woocommerce'), 500);
        }
        // Use a WooPayments connect link to let the WooPayments plugin handle the connection flow.
        return ['connectUrl' => add_query_arg(['wcpay-connect' => '1', 'from' => 'WCADMIN_PAYMENT_TASK', '_wpnonce' => wp_create_nonce('wcpay-connect')], admin_url('admin.php'))];
    }
    /**
     * Get the schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'plugins', 'type' => 'object', 'properties' => ['slug' => ['description' => __('Plugin slug.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'name' => ['description' => __('Plugin name.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'status' => ['description' => __('Plugin status.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true]]];
        return $this->add_additional_fields_schema($schema);
    }
    /**
     * Get the schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_connect_schema()
    {
        $schema = $this->get_item_schema();
        unset($schema['properties']['status']);
        $schema['properties']['connectAction'] = ['description' => __('Action that should be completed to connect Jetpack.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true];
        return $schema;
    }
}