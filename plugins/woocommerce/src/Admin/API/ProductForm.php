<?php

declare (strict_types=1);
/**
 * REST API Product Form Controller
 *
 * Handles requests to retrieve product form data.
 */
namespace Automattic\Woo_Commerce\Admin\API;

use Automattic\Woo_Commerce\Internal\Admin\Product_Form\Form_Factory;
defined('ABSPATH') || exit;
/**
 * ProductForm Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Product_Form extends \WC_REST_Data_Controller
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
    protected $rest_base = 'product-form';
    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_form_config(...), 'permission_callback' => $this->get_product_form_permission_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/fields', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_fields(...), 'permission_callback' => $this->get_product_form_permission_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Check if a given request has access to manage woocommerce.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_product_form_permission_check($request)
    {
        if (!current_user_can('manage_woocommerce')) {
            return new \WP_Error('woocommerce_rest_cannot_create', __('Sorry, you are not allowed to retrieve product form data.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Get the form fields.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_fields($request)
    {
        $json = array_map(fn($field) => $field->get_json(), Form_Factory::get_fields());
        return rest_ensure_response($json);
    }
    /**
     * Get the form config.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_form_config($request)
    {
        $fields = array_map(fn($field) => $field->get_json(), Form_Factory::get_fields());
        $subsections = array_map(fn($subsection) => $subsection->get_json(), Form_Factory::get_subsections());
        $sections = array_map(fn($section) => $section->get_json(), Form_Factory::get_sections());
        $tabs = array_map(fn($tab) => $tab->get_json(), Form_Factory::get_tabs());
        return rest_ensure_response(['fields' => $fields, 'subsections' => $subsections, 'sections' => $sections, 'tabs' => $tabs]);
    }
}