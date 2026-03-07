<?php

declare(strict_types=1);
/**
 * REST API Customer Downloads controller
 *
 * Handles requests to the /customers/<customer_id>/downloads endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

use Automattic\WooCommerce\Internal\Utilities\Users;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST API Customers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Customer_Downloads_V1_Controller extends WC_REST_Controller
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
    protected $rest_base = 'customers/(?P<customer_id>[\d]+)/downloads';

    /**
     * Register the routes for customers.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            'args' => [
                'customer_id' => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
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
    }

    /**
     * Check whether a given request has permission to read customers.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request)
    {
        $user = Users::get_user_in_current_site($request['customer_id']);
        if (is_wp_error($user)) {
            $user->add_data([ 'status' => 404 ]);
            return $user;
        }

        if (! wc_rest_check_user_permissions('read', $user->ID)) {
            return new WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), [ 'status' => rest_authorization_required_code() ]);
        }

        return true;
    }

    /**
     * Get all customer downloads.
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function get_items($request)
    {
        $downloads = wc_get_customer_available_downloads((int) $request['customer_id']);

        $data = [];
        foreach ($downloads as $download_data) {
            $download = $this->prepare_item_for_response((object) $download_data, $request);
            $download = $this->prepare_response_for_collection($download);
            $data[]   = $download;
        }

        return rest_ensure_response($data);
    }

    /**
     * Prepare a single download output for response.
     *
     * @param stdObject $download Download object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($download, $request)
    {
        $data = (array) $download;
        $data['access_expires']      = $data['access_expires'] ? wc_rest_prepare_date_response($data['access_expires']) : 'never';
        $data['downloads_remaining'] = '' === $data['downloads_remaining'] ? 'unlimited' : $data['downloads_remaining'];

        // Remove "product_name" since it's new in 3.0.
        unset($data['product_name']);

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object($data, $request);
        $data    = $this->filter_response_by_context($data, $context);

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        $response->add_links($this->prepare_links($download, $request));

        /**
         * Filter customer download data returned from the REST API.
         *
         * @param WP_REST_Response $response  The response object.
         * @param stdObject        $download  Download object used to create response.
         * @param WP_REST_Request  $request   Request object.
         */
        return apply_filters('woocommerce_rest_prepare_customer_download', $response, $download, $request);
    }

    /**
     * Prepare links for the request.
     *
     * @param stdClass $download Download object.
     * @param WP_REST_Request $request Request object.
     * @return array Links for the given customer download.
     */
    protected function prepare_links($download, $request)
    {
        $base  = str_replace('(?P<customer_id>[\d]+)', $request['customer_id'], $this->rest_base);

        return [
            'collection' => [
                'href' => rest_url(sprintf('/%s/%s', $this->namespace, $base)),
            ],
            'product' => [
                'href' => rest_url(sprintf('/%s/products/%d', $this->namespace, $download->product_id)),
            ],
            'order' => [
                'href' => rest_url(sprintf('/%s/orders/%d', $this->namespace, $download->order_id)),
            ],
        ];
    }

    /**
     * Get the Customer Download's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'customer_download',
            'type'       => 'object',
            'properties' => [
                'download_url' => [
                    'description' => __('Download file URL.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'download_id' => [
                    'description' => __('Download ID (MD5).', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'product_id' => [
                    'description' => __('Downloadable product ID.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'download_name' => [
                    'description' => __('Downloadable file name.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'order_id' => [
                    'description' => __('Order ID.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'order_key' => [
                    'description' => __('Order key.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'downloads_remaining' => [
                    'description' => __('Number of downloads remaining.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'access_expires' => [
                    'description' => __("The date when download access expires, in the site's timezone.", 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'file' => [
                    'description' => __('File details.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                    'properties' => [
                        'name' => [
                            'description' => __('File name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view' ],
                            'readonly'    => true,
                        ],
                        'file' => [
                            'description' => __('File URL.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view' ],
                            'readonly'    => true,
                        ],
                    ],
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
