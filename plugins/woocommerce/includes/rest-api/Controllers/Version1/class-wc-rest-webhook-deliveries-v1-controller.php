<?php

declare(strict_types=1);
/**
 * REST API Webhooks controller
 *
 * Handles requests to the /webhooks/<webhook_id>/deliveries endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST API Webhook Deliveries controller class.
 *
 * @deprecated 3.3.0 Webhooks deliveries logs now uses logging system.
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Webhook_Deliveries_V1_Controller extends WC_REST_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'webhooks/(?P<webhook_id>[\d]+)/deliveries';

    /**
     * Register the routes for webhook deliveries.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'args' => [
                'webhook_id' => [
                    'description' => __('Unique identifier for the webhook.', 'woocommerce'),
                    'type'        => 'integer',
                ],
            ],
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => $this->get_items(...),
                'permission_callback' => $this->get_items_permissions_check(...),
                'args'                => $this->get_collection_params(),
            ],
            'schema' => [ $this, 'get_public_item_schema' ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            'args' => [
                'webhook_id' => [
                    'description' => __('Unique identifier for the webhook.', 'woocommerce'),
                    'type'        => 'integer',
                ],
                'id' => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                ],
            ],
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => $this->get_item(...),
                'permission_callback' => $this->get_item_permissions_check(...),
                'args'                => [
                    'context' => $this->get_context_param([ 'default' => 'view' ]),
                ],
            ],
            'schema' => [ $this, 'get_public_item_schema' ],
        ]);
    }

    /**
     * Check whether a given request has permission to read taxes.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request)
    {
        if (! wc_rest_check_manager_permissions('webhooks', 'read')) {
            return new WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), [ 'status' => rest_authorization_required_code() ]);
        }

        return true;
    }

    /**
     * Check if a given request has access to read a tax.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request)
    {
        if (! wc_rest_check_manager_permissions('webhooks', 'read')) {
            return new WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot view this resource.', 'woocommerce'), [ 'status' => rest_authorization_required_code() ]);
        }

        return true;
    }

    /**
     * Get all webhook deliveries.
     *
     * @param WP_REST_Request $request
     *
     * @return array|WP_Error
     */
    public function get_items($request)
    {
        $webhook = wc_get_webhook((int) $request['webhook_id']);

        if (empty($webhook) || is_null($webhook)) {
            return new WP_Error('woocommerce_rest_webhook_invalid_id', __('Invalid webhook ID.', 'woocommerce'), [ 'status' => 404 ]);
        }

        $logs = [];
        $data = [];
        foreach ($logs as $log) {
            $delivery = $this->prepare_item_for_response((object) $log, $request);
            $delivery = $this->prepare_response_for_collection($delivery);
            $data[]   = $delivery;
        }

        return rest_ensure_response($data);
    }

    /**
     * Get a single webhook delivery.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        $webhook = wc_get_webhook((int) $request['webhook_id']);

        if (empty($webhook) || is_null($webhook)) {
            return new WP_Error('woocommerce_rest_webhook_invalid_id', __('Invalid webhook ID.', 'woocommerce'), [ 'status' => 404 ]);
        }
        return new WP_Error('woocommerce_rest_invalid_id', __('Invalid resource ID.', 'woocommerce'), [ 'status' => 404 ]);
    }

    /**
     * Prepare a single webhook delivery output for response.
     *
     * @param stdClass $log Delivery log object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($log, $request)
    {
        $data    = (array) $log;
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
     * Prepare links for the request.
     *
     * @param stdClass $log Delivery log object.
     * @return array Links for the given webhook delivery.
     */
    protected function prepare_links($log)
    {
        $webhook_id = (int) $log->request_headers['X-WC-Webhook-ID'];
        $base       = str_replace('(?P<webhook_id>[\d]+)', $webhook_id, $this->rest_base);

        return [
            'self' => [
                'href' => rest_url(sprintf('/%s/%s/%d', $this->namespace, $base, $log->id)),
            ],
            'collection' => [
                'href' => rest_url(sprintf('/%s/%s', $this->namespace, $base)),
            ],
            'up' => [
                'href' => rest_url(sprintf('/%s/webhooks/%d', $this->namespace, $webhook_id)),
            ],
        ];
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
                'id' => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'duration' => [
                    'description' => __('The delivery duration, in seconds.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'summary' => [
                    'description' => __('A friendly summary of the response including the HTTP response code, message, and body.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'request_url' => [
                    'description' => __('The URL where the webhook was delivered.', 'woocommerce'),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'request_headers' => [
                    'description' => __('Request headers.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'    => 'string',
                    ],
                ],
                'request_body' => [
                    'description' => __('Request body.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'response_code' => [
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
                        'type'    => 'string',
                    ],
                ],
                'response_body' => [
                    'description' => __('The response body from the receiving server.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'date_created' => [
                    'description' => __("The date the webhook delivery was logged, in the site's timezone.", 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }

    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params()
    {
        return [
            'context' => $this->get_context_param([ 'default' => 'view' ]),
        ];
    }
}
