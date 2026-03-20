<?php

declare (strict_types=1);
/**
 * REST API Onboarding Product Types Controller
 *
 * Handles requests to /onboarding/product-types
 */
namespace Automattic\Woo_Commerce\Admin\API;

use Automattic\Woo_Commerce\Internal\Admin\Onboarding\Onboarding_Products;
defined('ABSPATH') || exit;
/**
 * Onboarding Product Types Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Onboarding_Product_Types extends \WC_REST_Data_Controller
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
    protected $rest_base = 'onboarding/product-types';
    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_product_types(...), 'permission_callback' => $this->get_items_permissions_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Check whether a given request has permission to read onboarding profile data.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request): \WP_Error|true
    {
        if (!wc_rest_check_manager_permissions('settings', 'read')) {
            return new \WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Return available product types.
     *
     * @param \WP_REST_Request $request Request data.
     *
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_product_types($request)
    {
        return Onboarding_Products::get_product_types_with_data();
    }
}