<?php

declare(strict_types=1);
/**
 * REST API Order Refunds controller
 *
 * Handles requests to the /orders/<order_id>/refunds endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Internal\Utilities\Types;

/**
 * REST API Order Refunds controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Orders_V2_Controller
 */
class WC_REST_Order_Refunds_V2_Controller extends WC_REST_Orders_V2_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v2';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'orders/(?P<order_id>[\d]+)/refunds';

    /**
     * Post type.
     *
     * @var string
     */
    protected $post_type = 'shop_order_refund';

    /**
     * Stores the request.
     *
     * @var array
     */
    protected $request = [];

    /**
     * Order refunds actions.
     */
    public function __construct()
    {
        add_filter("woocommerce_rest_{$this->post_type}_object_trashable", '__return_false');
    }

    /**
     * Register the routes for order refunds.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'args'   => [
                    'order_id' => [
                        'description' => __('The order ID.', 'woocommerce'),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => $this->get_items(...),
                    'permission_callback' => $this->get_items_permissions_check(...),
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => $this->create_item(...),
                    'permission_callback' => $this->create_item_permissions_check(...),
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                'args'   => [
                    'order_id' => [
                        'description' => __('The order ID.', 'woocommerce'),
                        'type'        => 'integer',
                    ],
                    'id'       => [
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
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => $this->delete_item(...),
                    'permission_callback' => $this->delete_item_permissions_check(...),
                    'args'                => [
                        'force' => [
                            'default'     => true,
                            'type'        => 'boolean',
                            'description' => __('Required to be true, as resource does not support trashing.', 'woocommerce'),
                        ],
                    ],
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Get object.
     *
     * @since  3.0.0
     * @param  int $id Object ID.
     * @return WC_Data
     */
    protected function get_object($id)
    {
        return wc_get_order($id);
    }

    /**
     * Get formatted item data.
     *
     * @since  3.0.0
     * @param  WC_Data $object WC_Data instance.
     */
    protected function get_formatted_item_data($object): array
    {
        $data              = $object->get_data();
        $format_decimal    = [ 'amount' ];
        $format_date       = [ 'date_created' ];
        $format_line_items = [ 'line_items', 'shipping_lines', 'tax_lines', 'fee_lines' ];

        // Format decimal values.
        foreach ($format_decimal as $key) {
            $data[ $key ] = wc_format_decimal($data[ $key ], $this->request['dp']);
        }

        // Format date values.
        foreach ($format_date as $key) {
            $datetime              = $data[ $key ];
            $data[ $key ]          = wc_rest_prepare_date_response($datetime, false);
            $data[ $key . '_gmt' ] = wc_rest_prepare_date_response($datetime);
        }

        // Format line items.
        foreach ($format_line_items as $key) {
            $data[ $key ] = array_values(array_map($this->get_order_item_data(...), $data[ $key ]));
        }

        return [
            'id'               => $object->get_id(),
            'date_created'     => $data['date_created'],
            'date_created_gmt' => $data['date_created_gmt'],
            'amount'           => $data['amount'],
            'reason'           => $data['reason'],
            'refunded_by'      => $data['refunded_by'],
            'refunded_payment' => $data['refunded_payment'],
            'meta_data'        => $data['meta_data'],
            'line_items'       => $data['line_items'],
            'shipping_lines'   => $data['shipping_lines'],
            'tax_lines'        => $data['tax_lines'],
            'fee_lines'        => $data['fee_lines'],
        ];
    }

    /**
     * Prepare a single order output for response.
     *
     * @since  3.0.0
     *
     * @param  WC_Data         $object  Object data.
     * @param  WP_REST_Request $request Request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function prepare_object_for_response($object, $request)
    {
        $this->request       = $request;
        $this->request['dp'] = is_null($this->request['dp']) ? wc_get_price_decimals() : absint($this->request['dp']);
        $order               = wc_get_order((int) $request['order_id']);

        if (! $order) {
            return new WP_Error('woocommerce_rest_invalid_order_id', __('Invalid order ID.', 'woocommerce'), 404);
        }

        if (! $object || $object->get_parent_id() !== $order->get_id()) {
            return new WP_Error('woocommerce_rest_invalid_order_refund_id', __('Invalid order refund ID.', 'woocommerce'), 404);
        }

        $data    = $this->get_formatted_item_data($object);
        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object($data, $request);
        $data    = $this->filter_response_by_context($data, $context);

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        $response->add_links($this->prepare_links($object, $request));

        /**
         * Filter the data for a response.
         *
         * The dynamic portion of the hook name, $this->post_type,
         * refers to object type being prepared for the response.
         *
         * @param WP_REST_Response $response The response object.
         * @param WC_Data          $object   Object data.
         * @param WP_REST_Request  $request  Request object.
         */
        return apply_filters("woocommerce_rest_prepare_{$this->post_type}_object", $response, $object, $request);
    }

    /**
     * Prepare links for the request.
     *
     * @param WC_Data         $object  Object data.
     * @param WP_REST_Request $request Request object.
     * @return array                   Links for the given post.
     */
    protected function prepare_links($object, $request): array
    {
        $base  = str_replace('(?P<order_id>[\d]+)', $object->get_parent_id(), $this->rest_base);

        return [
            'self'       => [
                'href' => rest_url(sprintf('/%s/%s/%d', $this->namespace, $base, $object->get_id())),
            ],
            'collection' => [
                'href' => rest_url(sprintf('/%s/%s', $this->namespace, $base)),
            ],
            'up'         => [
                'href' => rest_url(sprintf('/%s/orders/%d', $this->namespace, $object->get_parent_id())),
            ],
        ];
    }

    /**
     * Prepare objects query.
     *
     * @since  3.0.0
     * @param  WP_REST_Request $request Full details about the request.
     * @return array
     */
    protected function prepare_objects_query($request)
    {
        $args = parent::prepare_objects_query($request);

        $args['post_status']     = array_keys(wc_get_order_statuses());
        $args['post_parent__in'] = [ absint($request['order_id']) ];

        return $args;
    }

    /**
     * Prepares one object for create or update operation.
     *
     * @since  3.0.0
     * @param  WP_REST_Request $request Request object.
     * @param  bool            $creating If is creating a new object.
     * @return WP_Error|WC_Data The prepared item, or WP_Error object on failure.
     */
    protected function prepare_object_for_database($request, $creating = false)
    {
        $order = wc_get_order((int) $request['order_id']);

        if (! $order) {
            return new WP_Error('woocommerce_rest_invalid_order_id', __('Invalid order ID.', 'woocommerce'), 404);
        }

        if (0 > $request['amount']) {
            return new WP_Error('woocommerce_rest_invalid_order_refund', __('Refund amount must be greater than zero.', 'woocommerce'), 400);
        }

        // Create the refund.
        $refund = wc_create_refund(
            [
                'order_id'       => $order->get_id(),
                'amount'         => $request['amount'],
                'reason'         => empty($request['reason']) ? null : $request['reason'],
                'refund_payment' => is_bool($request['api_refund']) ? $request['api_refund'] : true,
                'restock_items'  => true,
            ]
        );

        if (is_wp_error($refund)) {
            return new WP_Error('woocommerce_rest_cannot_create_order_refund', $refund->get_error_message(), 500);
        }

        if (! $refund) {
            return new WP_Error('woocommerce_rest_cannot_create_order_refund', __('Cannot create order refund, please try again.', 'woocommerce'), 500);
        }

        if (! empty($request['meta_data']) && is_array($request['meta_data'])) {
            foreach ($request['meta_data'] as $meta) {
                $refund->update_meta_data($meta['key'], $meta['value'], $meta['id'] ?? '');
            }
            $refund->save_meta_data();
        }

        /**
         * Filters an object before it is inserted via the REST API.
         *
         * The dynamic portion of the hook name, `$this->post_type`,
         * refers to the object type slug.
         *
         * @since 4.5.0
         *
         * @param WC_Data         $coupon   Object object.
         * @param WP_REST_Request $request  Request object.
         * @param bool            $creating If is creating a new object.
         */
        $refund = apply_filters("woocommerce_rest_pre_insert_{$this->post_type}_object", $refund, $request, $creating);

        // If the filtered result is not a WC_Data instance and is not a WP_Error then something went wrong, but we
        // still need to honor the declared return type.
        return Types::ensure_instance_of(
            $refund,
            WC_Data::class,
            fn ($thing) => is_wp_error($thing)
                    ? $thing
                    : new WP_Error(
                        'woocommerce_rest_cannot_verify_refund_created',
                        __('An unexpected error occurred while generating the refund.', 'woocommerce')
                    )
        );
    }

    /**
     * Save an object data.
     *
     * @since  3.0.0
     * @param  WP_REST_Request $request  Full details about the request.
     * @param  bool            $creating If is creating a new object.
     * @return WC_Data|WP_Error
     */
    protected function save_object($request, $creating = false)
    {
        try {
            $object = $this->prepare_object_for_database($request, $creating);

            if (is_wp_error($object)) {
                return $object;
            }

            return $this->get_object($object->get_id());
        } catch (WC_Data_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), $e->getErrorData());
        } catch (WC_REST_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }
    }

    /**
     * Get the refund schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => $this->post_type,
            'type'       => 'object',
            'properties' => [
                'id'               => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_created'     => [
                    'description' => __("The date the order refund was created, in the site's timezone.", 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_created_gmt' => [
                    'description' => __('The date the order refund was created, as GMT.', 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'amount'           => [
                    'description' => __('Refund amount.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'reason'           => [
                    'description' => __('Reason for refund.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'refunded_by'      => [
                    'description' => __('User ID of user who created the refund.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
                'refunded_payment' => [
                    'description' => __('If the payment was refunded via the API.', 'woocommerce'),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'meta_data'        => [
                    'description' => __('Meta data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'    => [
                                'description' => __('Meta ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'key'   => [
                                'description' => __('Meta key.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'value' => [
                                'description' => __('Meta value.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                        ],
                    ],
                ],
                'line_items'       => [
                    'description' => __('Line items data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'           => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'name'         => [
                                'description' => __('Product name.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'product_id'   => [
                                'description' => __('Product ID.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'variation_id' => [
                                'description' => __('Variation ID, if applicable.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'quantity'     => [
                                'description' => __('Quantity ordered.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'tax_class'    => [
                                'description' => __('Tax class of product.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'subtotal'     => [
                                'description' => __('Line subtotal (before discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'subtotal_tax' => [
                                'description' => __('Line subtotal tax (before discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'total'        => [
                                'description' => __('Line total (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'total_tax'    => [
                                'description' => __('Line total tax (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'taxes'        => [
                                'description' => __('Line taxes.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'       => [
                                            'description' => __('Tax rate ID.', 'woocommerce'),
                                            'type'        => 'integer',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'total'    => [
                                            'description' => __('Tax total.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'subtotal' => [
                                            'description' => __('Tax subtotal.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                            'meta_data'    => [
                                'description' => __('Meta data.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'    => [
                                            'description' => __('Meta ID.', 'woocommerce'),
                                            'type'        => 'integer',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'key'   => [
                                            'description' => __('Meta key.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'value' => [
                                            'description' => __('Meta value.', 'woocommerce'),
                                            'type'        => 'mixed',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                            'sku'          => [
                                'description' => __('Product SKU.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'price'        => [
                                'description' => __('Product price.', 'woocommerce'),
                                'type'        => 'number',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                ],
                'tax_lines'        => [
                    'description' => __('Tax lines data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'                 => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'rate_code'          => [
                                'description' => __('Tax rate code.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'rate_id'            => [
                                'description' => __('Tax rate ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'label'              => [
                                'description' => __('Tax rate label.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'compound'           => [
                                'description' => __('Show if is a compound tax rate.', 'woocommerce'),
                                'type'        => 'boolean',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'tax_total'          => [
                                'description' => __('Tax total (not including shipping taxes).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'shipping_tax_total' => [
                                'description' => __('Shipping tax total.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'meta_data'          => [
                                'description' => __('Meta data.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'    => [
                                            'description' => __('Meta ID.', 'woocommerce'),
                                            'type'        => 'integer',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'key'   => [
                                            'description' => __('Meta key.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                        ],
                                        'value' => [
                                            'description' => __('Meta value.', 'woocommerce'),
                                            'type'        => 'mixed',
                                            'context'     => [ 'view', 'edit' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'shipping_lines'   => [
                    'description' => __('Shipping lines data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'           => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'method_title' => [
                                'description' => __('Shipping method name.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'method_id'    => [
                                'description' => __('Shipping method ID.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'instance_id'  => [
                                'description' => __('Shipping instance ID.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total'        => [
                                'description' => __('Line total (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total_tax'    => [
                                'description' => __('Line total tax (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'taxes'        => [
                                'description' => __('Line taxes.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'    => [
                                            'description' => __('Tax rate ID.', 'woocommerce'),
                                            'type'        => 'integer',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'total' => [
                                            'description' => __('Tax total.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                            'meta_data'    => [
                                'description' => __('Meta data.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'    => [
                                            'description' => __('Meta ID.', 'woocommerce'),
                                            'type'        => 'integer',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'key'   => [
                                            'description' => __('Meta key.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                        ],
                                        'value' => [
                                            'description' => __('Meta value.', 'woocommerce'),
                                            'type'        => 'mixed',
                                            'context'     => [ 'view', 'edit' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'fee_lines'        => [
                    'description' => __('Fee lines data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'         => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'name'       => [
                                'description' => __('Fee name.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'tax_class'  => [
                                'description' => __('Tax class of fee.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'tax_status' => [
                                'description' => __('Tax status of fee.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'enum'        => [ ProductTaxStatus::TAXABLE, ProductTaxStatus::NONE ],
                            ],
                            'total'      => [
                                'description' => __('Line total (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total_tax'  => [
                                'description' => __('Line total tax (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'taxes'      => [
                                'description' => __('Line taxes.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'       => [
                                            'description' => __('Tax rate ID.', 'woocommerce'),
                                            'type'        => 'integer',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'total'    => [
                                            'description' => __('Tax total.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'subtotal' => [
                                            'description' => __('Tax subtotal.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                            'meta_data'  => [
                                'description' => __('Meta data.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id'    => [
                                            'description' => __('Meta ID.', 'woocommerce'),
                                            'type'        => 'integer',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'key'   => [
                                            'description' => __('Meta key.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                        ],
                                        'value' => [
                                            'description' => __('Meta value.', 'woocommerce'),
                                            'type'        => 'mixed',
                                            'context'     => [ 'view', 'edit' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'api_refund'       => [
                    'description' => __('When true, the payment gateway API is used to generate the refund.', 'woocommerce'),
                    'type'        => 'boolean',
                    'context'     => [ 'edit' ],
                    'default'     => true,
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
        $params = parent::get_collection_params();

        unset($params['status'], $params['customer'], $params['product']);

        return $params;
    }
}
