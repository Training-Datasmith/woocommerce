<?php

declare(strict_types=1);
/**
 * REST API Webhooks controller
 *
 * Handles requests to the /webhooks/<webhook_id>/deliveries endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined('ABSPATH') || exit;

/**
 * REST API Webhook Deliveries controller class.
 *
 * @deprecated 3.3.0 Webhooks deliveries logs now uses logging system.
 * @package WooCommerce\RestApi
 * @extends WC_REST_Webhook_Deliveries_V1_Controller
 */
class WC_REST_Webhook_Deliveries_V2_Controller extends WC_REST_Webhook_Deliveries_V1_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v2';

    /**
     * Prepare a single webhook delivery output for response.
     *
     * @param  stdClass        $log Delivery log object.
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function prepare_item_for_response($log, $request)
    {
        $data = (array) $log;

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object($data, $request);
        $data    = $this->filter_response_by_context($data, $context);

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        $response->add_links($this->prepare_links($log));

        /**
         * Filter webhook delivery object returned from the REST API.
         *
         * @param WP_REST_Response $response The response object.
         * @param stdClass         $log      Delivery log object used to create response.
         * @param WP_REST_Request  $request  Request object.
         */
        return apply_filters('woocommerce_rest_prepare_webhook_delivery', $response, $log, $request);
    }

    /**
     * Get the Webhook's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'webhook_delivery',
            'type'       => 'object',
            'properties' => [
                'id'               => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'duration'         => [
                    'description' => __('The delivery duration, in seconds.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'summary'          => [
                    'description' => __('A friendly summary of the response including the HTTP response code, message, and body.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'request_url'      => [
                    'description' => __('The URL where the webhook was delivered.', 'woocommerce'),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'request_headers'  => [
                    'description' => __('Request headers.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'items'       => [
                        'type' => 'string',
                    ],
                ],
                'request_body'     => [
                    'description' => __('Request body.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'response_code'    => [
                    'description' => __('The HTTP response code from the receiving server.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'response_message' => [
                    'description' => __('The HTTP response message from the receiving server.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'response_headers' => [
                    'description' => __('Array of the response headers from the receiving server.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'items'       => [
                        'type' => 'string',
                    ],
                ],
                'response_body'    => [
                    'description' => __('The response body from the receiving server.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'date_created'     => [
                    'description' => __("The date the webhook delivery was logged, in the site's timezone.", 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_created_gmt' => [
                    'description' => __('The date the webhook delivery was logged, as GMT.', 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }
}
