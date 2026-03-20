<?php

declare (strict_types=1);
/**
 * REST API Payment Gateway Suggestions Controller
 *
 * Handles requests to install and activate dependent plugins.
 */
namespace Automattic\Woo_Commerce\Admin\API;

use Automattic\Woo_Commerce\Admin\Features\Payment_Gateway_Suggestions\Default_Payment_Gateways;
use Automattic\Woo_Commerce\Admin\Features\Payment_Gateway_Suggestions\Init as Suggestions;
defined('ABSPATH') || exit;
/**
 * PaymentGatewaySuggetsions Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Payment_Gateway_Suggestions extends \WC_REST_Data_Controller
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
    protected $rest_base = 'payment-gateway-suggestions';
    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_suggestions(...), 'permission_callback' => $this->user_can_manage_woocommerce(...), 'args' => ['force_default_suggestions' => ['type' => 'boolean', 'description' => __('Return the default payment suggestions when woocommerce_show_marketplace_suggestions and woocommerce_setting_payments_recommendations_hidden options are set to no', 'woocommerce')]]], 'schema' => $this->get_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/dismiss', [['methods' => \WP_REST_Server::CREATABLE, 'callback' => $this->dismiss_payment_gateway_suggestion(...), 'permission_callback' => $this->get_permission_check(...)], 'schema' => $this->get_item_schema(...)]);
    }
    /**
     * Check if a given request has access to manage plugins.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_permission_check($request)
    {
        if (!current_user_can('install_plugins')) {
            return new \WP_Error('woocommerce_rest_cannot_update', __('Sorry, you cannot manage plugins.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Check if a given request has access to manage woocommerce.
     *
     * @return \WP_Error|boolean
     */
    public function user_can_manage_woocommerce()
    {
        if (current_user_can('manage_woocommerce')) {
            return true;
        }
        return new \WP_Error('woocommerce_rest_invalid_user', __('You are not allowed to make this request.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
    }
    /**
     * Return suggested payment gateways.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_suggestions($request)
    {
        $should_display = Suggestions::should_display();
        $force_default = $request->get_param('force_default_suggestions');
        if ($should_display) {
            return Suggestions::get_suggestions();
        }
        if (false === $should_display && true === $force_default) {
            return rest_ensure_response(Suggestions::get_suggestions(Default_Payment_Gateways::get_all()));
        }
        return rest_ensure_response([]);
    }
    /**
     * Dismisses suggested payment gateways.
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function dismiss_payment_gateway_suggestion()
    {
        $success = Suggestions::dismiss();
        return rest_ensure_response($success);
    }
    /**
     * Get the schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'payment-gateway-suggestions', 'type' => 'object', 'properties' => ['content' => ['description' => __('Suggestion description.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'id' => ['description' => __('Suggestion ID.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'image' => ['description' => __('Gateway image.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'is_visible' => ['description' => __('Suggestion visibility.', 'woocommerce'), 'type' => 'boolean', 'context' => ['view', 'edit'], 'readonly' => true], 'plugins' => ['description' => __('Array of plugin slugs.', 'woocommerce'), 'type' => 'array', 'context' => ['view', 'edit'], 'readonly' => true], 'recommendation_priority' => ['description' => __('Priority of recommendation.', 'woocommerce'), 'type' => 'integer', 'context' => ['view', 'edit'], 'readonly' => true], 'title' => ['description' => __('Gateway title.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'transaction_processors' => ['description' => __('Array of transaction processors and their images.', 'woocommerce'), 'type' => 'object', 'addtionalProperties' => ['type' => 'string', 'format' => 'uri'], 'context' => ['view', 'edit'], 'readonly' => true]]];
        return $this->add_additional_fields_schema($schema);
    }
}