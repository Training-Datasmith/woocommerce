<?php

declare (strict_types=1);
/**
 * REST API Reports Import Controller
 *
 * Handles requests to /reports/import
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports\Import;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Reports_Sync;
/**
 * Reports Imports controller.
 *
 * @internal
 * @extends \Automattic\WooCommerce\Admin\API\Reports\Controller
 */
class Controller extends \Automattic\Woo_Commerce\Admin\API\Reports\Controller
{
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/import';
    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->import_items(...), 'permission_callback' => $this->import_permissions_check(...), 'args' => $this->get_import_collection_params()], 'schema' => $this->get_import_public_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/cancel', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->cancel_import(...), 'permission_callback' => $this->import_permissions_check(...)], 'schema' => $this->get_import_public_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/delete', [['methods' => \WP_REST_Server::EDITABLE, 'callback' => $this->delete_imported_items(...), 'permission_callback' => $this->import_permissions_check(...)], 'schema' => $this->get_import_public_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/status', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_import_status(...), 'permission_callback' => $this->import_permissions_check(...)], 'schema' => $this->get_import_public_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/totals', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_import_totals(...), 'permission_callback' => $this->import_permissions_check(...), 'args' => $this->get_import_collection_params()], 'schema' => $this->get_import_public_schema(...)]);
    }
    /**
     * Makes sure the current user has access to WRITE the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function import_permissions_check($request)
    {
        if (!wc_rest_check_manager_permissions('settings', 'edit')) {
            return new \WP_Error('woocommerce_rest_cannot_edit', __('Sorry, you cannot edit this resource.', 'woocommerce'), ['status' => rest_authorization_required_code()]);
        }
        return true;
    }
    /**
     * Import data based on user request params.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function import_items($request)
    {
        $query_args = $this->prepare_objects_query($request);
        $import = Reports_Sync::regenerate_report_data($query_args['days'], $query_args['skip_existing']);
        if (is_wp_error($import)) {
            $result = ['status' => 'error', 'message' => $import->get_error_message()];
        } else {
            $result = ['status' => 'success', 'message' => $import];
        }
        $response = $this->prepare_item_for_response($result, $request);
        $data = $this->prepare_response_for_collection($response);
        return rest_ensure_response($data);
    }
    /**
     * Prepare request object as query args.
     *
     * @param WP_REST_Request $request Request data.
     * @return array
     */
    protected function prepare_objects_query($request)
    {
        $args = [];
        $args['skip_existing'] = $request['skip_existing'];
        $args['days'] = $request['days'];
        return $args;
    }
    /**
     * Prepare the data object for response.
     *
     * @param object          $item Data object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($item, $request)
    {
        $data = $this->add_additional_fields_to_object($item, $request);
        $data = $this->filter_response_by_context($data, 'view');
        $response = rest_ensure_response($data);
        /**
         * Filter the list returned from the API.
         *
         * @param WP_REST_Response $response The response object.
         * @param array            $item     The original item.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_reports_import', $response, $item, $request);
    }
    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_import_collection_params()
    {
        $params = [];
        $params['days'] = ['description' => __('Number of days to import.', 'woocommerce'), 'type' => 'integer', 'sanitize_callback' => 'absint', 'validate_callback' => 'rest_validate_request_arg', 'minimum' => 0];
        $params['skip_existing'] = ['description' => __('Skip importing existing order data.', 'woocommerce'), 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'wc_string_to_bool', 'validate_callback' => 'rest_validate_request_arg'];
        return $params;
    }
    /**
     * Get the Report's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_import_public_schema()
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'report_import', 'type' => 'object', 'properties' => ['status' => ['description' => __('Regeneration status.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'message' => ['description' => __('Regenerate data message.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true]]];
        return $this->add_additional_fields_schema($schema);
    }
    /**
     * Cancel all queued import actions.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function cancel_import($request)
    {
        Reports_Sync::clear_queued_actions();
        $result = ['status' => 'success', 'message' => __('All pending and in-progress import actions have been cancelled.', 'woocommerce')];
        $response = $this->prepare_item_for_response($result, $request);
        $data = $this->prepare_response_for_collection($response);
        return rest_ensure_response($data);
    }
    /**
     * Delete all imported items.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_imported_items($request)
    {
        $delete = Reports_Sync::delete_report_data();
        if (is_wp_error($delete)) {
            $result = ['status' => 'error', 'message' => $delete->get_error_message()];
        } else {
            $result = ['status' => 'success', 'message' => $delete];
        }
        $response = $this->prepare_item_for_response($result, $request);
        $data = $this->prepare_response_for_collection($response);
        return rest_ensure_response($data);
    }
    /**
     * Get the status of the current import.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_import_status($request)
    {
        $result = Reports_Sync::get_import_stats();
        $response = $this->prepare_item_for_response($result, $request);
        $data = $this->prepare_response_for_collection($response);
        return rest_ensure_response($data);
    }
    /**
     * Get the total orders and customers based on user supplied params.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_import_totals($request)
    {
        $query_args = $this->prepare_objects_query($request);
        $totals = Reports_Sync::get_import_totals($query_args['days'], $query_args['skip_existing']);
        $response = $this->prepare_item_for_response($totals, $request);
        $data = $this->prepare_response_for_collection($response);
        return rest_ensure_response($data);
    }
}