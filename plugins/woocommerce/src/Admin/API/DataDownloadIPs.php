<?php

declare (strict_types=1);
/**
 * REST API Data Download IP Controller
 *
 * Handles requests to /data/download-ips
 */
namespace Automattic\Woo_Commerce\Admin\API;

defined('ABSPATH') || exit;
/**
 * Data Download IP controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Data_Download_I_Ps extends \WC_REST_Data_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-analytics';
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'data/download-ips';
    /**
     * Register routes.
     *
     * @since 3.5.0
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_items(...), 'permission_callback' => $this->get_items_permissions_check(...)], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Return the download IPs matching the passed parameters.
     *
     * @since  3.5.0
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        global $wpdb;
        if (isset($request['match'])) {
            $downloads = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT( user_ip_address ) FROM {$wpdb->prefix}wc_download_log\n\t\t\t\t\tWHERE user_ip_address LIKE %s\n\t\t\t\t\tLIMIT 10", $request['match'] . '%'));
        } else {
            return new \WP_Error('woocommerce_rest_data_download_ips_invalid_request', __('Invalid request. Please pass the match parameter.', 'woocommerce'), ['status' => 400]);
        }
        $data = [];
        if (!empty($downloads)) {
            foreach ($downloads as $download) {
                $response = $this->prepare_item_for_response($download, $request);
                $data[] = $this->prepare_response_for_collection($response);
            }
        }
        return rest_ensure_response($data);
    }
    /**
     * Prepare the data object for response.
     *
     * @since  3.5.0
     * @param object          $item Data object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($item, $request)
    {
        $data = $this->add_additional_fields_to_object($item, $request);
        $data = $this->filter_response_by_context($data, 'view');
        $response = rest_ensure_response($data);
        $response->add_links($this->prepare_links($item));
        /**
         * Filter the list returned from the API.
         *
         * @param WP_REST_Response $response The response object.
         * @param array            $item     The original item.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_data_download_ip', $response, $item, $request);
    }
    /**
     * Prepare links for the request.
     *
     * @param object $item Data object.
     * @return array Links for the given object.
     */
    protected function prepare_links($item): array
    {
        return ['collection' => ['href' => rest_url(sprintf('/%s/%s', $this->namespace, $this->rest_base))]];
    }
    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params()
    {
        $params = [];
        $params['context'] = $this->get_context_param(['default' => 'view']);
        $params['match'] = ['description' => __('A partial IP address can be passed and matching results will be returned.', 'woocommerce'), 'type' => 'string', 'validate_callback' => 'rest_validate_request_arg'];
        return $params;
    }
    /**
     * Get the schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'data_download_ips', 'type' => 'object', 'properties' => ['user_ip_address' => ['type' => 'string', 'description' => __('IP address.', 'woocommerce'), 'context' => ['view'], 'readonly' => true]]];
        return $this->add_additional_fields_schema($schema);
    }
}