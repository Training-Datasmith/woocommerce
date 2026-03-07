<?php

declare(strict_types=1);
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Internal\Utilities\Users;
use Automattic\WooCommerce\Utilities\{ ArrayUtil, NumberUtil, StringUtil };

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Posts_Controller
 */
class WC_REST_Orders_V1_Controller extends WC_REST_Posts_Controller
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
    protected $rest_base = 'orders';

    /**
     * Post type.
     *
     * @var string
     */
    protected $post_type = 'shop_order';

    /**
     * Initialize orders actions.
     */
    public function __construct()
    {
        add_filter("woocommerce_rest_{$this->post_type}_query", $this->query_args(...), 10, 2);
    }

    /**
     * Register the routes for orders.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
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
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            'args' => [
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
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => $this->update_item(...),
                'permission_callback' => $this->update_item_permissions_check(...),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => $this->delete_item(...),
                'permission_callback' => $this->delete_item_permissions_check(...),
                'args'                => [
                    'force' => [
                        'default'     => false,
                        'type'        => 'boolean',
                        'description' => __('Whether to bypass trash and force deletion.', 'woocommerce'),
                    ],
                ],
            ],
            'schema' => [ $this, 'get_public_item_schema' ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/batch', [
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => $this->batch_items(...),
                'permission_callback' => $this->batch_items_permissions_check(...),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ],
            'schema' => $this->get_public_batch_schema(...),
        ]);
    }

    /**
     * Prepare a single order output for response.
     *
     * @param WP_Post $post Post object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $data
     */
    public function prepare_item_for_response($post, $request)
    {
        $order = wc_get_order($post);
        $dp    = is_null($request['dp']) ? wc_get_price_decimals() : absint($request['dp']);

        $data = [
            'id'                   => $order->get_id(),
            'parent_id'            => $order->get_parent_id(),
            'status'               => $order->get_status(),
            'order_key'            => $order->get_order_key(),
            'number'               => $order->get_order_number(),
            'currency'             => $order->get_currency(),
            'version'              => $order->get_version(),
            'prices_include_tax'   => $order->get_prices_include_tax(),
            'date_created'         => wc_rest_prepare_date_response($order->get_date_created()),  // v1 API used UTC.
            'date_modified'        => wc_rest_prepare_date_response($order->get_date_modified()), // v1 API used UTC.
            'customer_id'          => $order->get_customer_id(),
            'discount_total'       => wc_format_decimal($order->get_total_discount(), $dp),
            'discount_tax'         => wc_format_decimal($order->get_discount_tax(), $dp),
            'shipping_total'       => wc_format_decimal($order->get_shipping_total(), $dp),
            'shipping_tax'         => wc_format_decimal($order->get_shipping_tax(), $dp),
            'cart_tax'             => wc_format_decimal($order->get_cart_tax(), $dp),
            'total'                => wc_format_decimal($order->get_total(), $dp),
            'total_tax'            => wc_format_decimal($order->get_total_tax(), $dp),
            'billing'              => [],
            'shipping'             => [],
            'payment_method'       => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'transaction_id'       => $order->get_transaction_id(),
            'customer_ip_address'  => $order->get_customer_ip_address(),
            'customer_user_agent'  => $order->get_customer_user_agent(),
            'created_via'          => $order->get_created_via(),
            'customer_note'        => $order->get_customer_note(),
            'date_completed'       => wc_rest_prepare_date_response($order->get_date_completed(), false), // v1 API used local time.
            'date_paid'            => wc_rest_prepare_date_response($order->get_date_paid(), false), // v1 API used local time.
            'cart_hash'            => $order->get_cart_hash(),
            'line_items'           => [],
            'tax_lines'            => [],
            'shipping_lines'       => [],
            'fee_lines'            => [],
            'coupon_lines'         => [],
            'refunds'              => [],
        ];

        // Add addresses.
        $data['billing']  = $order->get_address('billing');
        $data['shipping'] = $order->get_address('shipping');

        // Add line items.
        foreach ($order->get_items() as $item_id => $item) {
            $product      = $item->get_product();
            $product_id   = 0;
            $variation_id = 0;
            $product_sku  = null;

            // Check if the product exists.
            if (is_object($product)) {
                $product_id   = $item->get_product_id();
                $variation_id = $item->get_variation_id();
                $product_sku  = $product->get_sku();
            }

            $item_meta = [];

            $hideprefix = 'true' === $request['all_item_meta'] ? null : '_';

            foreach ($item->get_all_formatted_meta_data($hideprefix) as $formatted_meta) {
                $item_meta[] = [
                    'key'   => $formatted_meta->key,
                    'label' => $formatted_meta->display_key,
                    'value' => wc_clean($formatted_meta->display_value),
                ];
            }

            $line_item = [
                'id'           => $item_id,
                'name'         => $item['name'],
                'sku'          => $product_sku,
                'product_id'   => (int) $product_id,
                'variation_id' => (int) $variation_id,
                'quantity'     => wc_stock_amount($item['qty']),
                'tax_class'    => ! empty($item['tax_class']) ? $item['tax_class'] : '',
                'price'        => wc_format_decimal($order->get_item_total($item, false, false), $dp),
                'subtotal'     => wc_format_decimal($order->get_line_subtotal($item, false, false), $dp),
                'subtotal_tax' => wc_format_decimal($item['line_subtotal_tax'], $dp),
                'total'        => wc_format_decimal($order->get_line_total($item, false, false), $dp),
                'total_tax'    => wc_format_decimal($item['line_tax'], $dp),
                'taxes'        => [],
                'meta'         => $item_meta,
            ];

            $item_line_taxes = maybe_unserialize($item['line_tax_data']);
            if (isset($item_line_taxes['total'])) {
                $line_tax = [];

                foreach ($item_line_taxes['total'] as $tax_rate_id => $tax) {
                    $line_tax[ $tax_rate_id ] = [
                        'id'       => $tax_rate_id,
                        'total'    => $tax,
                        'subtotal' => '',
                    ];
                }

                foreach ($item_line_taxes['subtotal'] as $tax_rate_id => $tax) {
                    $line_tax[ $tax_rate_id ]['subtotal'] = $tax;
                }

                $line_item['taxes'] = array_values($line_tax);
            }

            $data['line_items'][] = $line_item;
        }

        // Add taxes.
        foreach ($order->get_items('tax') as $key => $tax) {
            $tax_line = [
                'id'                 => $key,
                'rate_code'          => $tax['name'],
                'rate_id'            => $tax['rate_id'],
                'label'              => $tax['label'] ?? $tax['name'],
                'compound'           => (bool) $tax['compound'],
                'tax_total'          => wc_format_decimal($tax['tax_amount'], $dp),
                'shipping_tax_total' => wc_format_decimal($tax['shipping_tax_amount'], $dp),
            ];

            $data['tax_lines'][] = $tax_line;
        }

        // Add shipping.
        foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
            $shipping_line = [
                'id'           => $shipping_item_id,
                'method_title' => $shipping_item['name'],
                'method_id'    => $shipping_item['method_id'],
                'total'        => wc_format_decimal($shipping_item['cost'], $dp),
                'total_tax'    => wc_format_decimal('', $dp),
                'taxes'        => [],
            ];

            $shipping_taxes = $shipping_item->get_taxes();

            if (! empty($shipping_taxes['total'])) {
                $total_tax = NumberUtil::array_sum($shipping_taxes['total']);

                $shipping_line['total_tax'] = wc_format_decimal($total_tax, $dp);

                foreach ($shipping_taxes['total'] as $tax_rate_id => $tax) {
                    $shipping_line['taxes'][] = [
                        'id'       => $tax_rate_id,
                        'total'    => $tax,
                    ];
                }
            }

            $data['shipping_lines'][] = $shipping_line;
        }

        // Add fees.
        foreach ($order->get_fees() as $fee_item_id => $fee_item) {
            $fee_line = [
                'id'         => $fee_item_id,
                'name'       => $fee_item['name'],
                'tax_class'  => ! empty($fee_item['tax_class']) ? $fee_item['tax_class'] : '',
                'tax_status' => ProductTaxStatus::TAXABLE,
                'total'      => wc_format_decimal($order->get_line_total($fee_item), $dp),
                'total_tax'  => wc_format_decimal($order->get_line_tax($fee_item), $dp),
                'taxes'      => [],
            ];

            $fee_line_taxes = maybe_unserialize($fee_item['line_tax_data']);
            if (isset($fee_line_taxes['total'])) {
                $fee_tax = [];

                foreach ($fee_line_taxes['total'] as $tax_rate_id => $tax) {
                    $fee_tax[ $tax_rate_id ] = [
                        'id'       => $tax_rate_id,
                        'total'    => $tax,
                        'subtotal' => '',
                    ];
                }

                if (isset($fee_line_taxes['subtotal'])) {
                    foreach ($fee_line_taxes['subtotal'] as $tax_rate_id => $tax) {
                        $fee_tax[ $tax_rate_id ]['subtotal'] = $tax;
                    }
                }

                $fee_line['taxes'] = array_values($fee_tax);
            }

            $data['fee_lines'][] = $fee_line;
        }

        // Add coupons.
        foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
            $coupon_line = [
                'id'           => $coupon_item_id,
                'code'         => $coupon_item->get_name(),
                'discount'     => wc_format_decimal($coupon_item->get_discount(), $dp),
                'discount_tax' => wc_format_decimal($coupon_item->get_discount_tax(), $dp),
            ];

            $data['coupon_lines'][] = $coupon_line;
        }

        // Add refunds.
        foreach ($order->get_refunds() as $refund) {
            $data['refunds'][] = [
                'id'     => $refund->get_id(),
                'refund' => $refund->get_reason() ?: '',
                'total'  => '-' . wc_format_decimal($refund->get_amount(), $dp),
            ];
        }

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object($data, $request);
        $data    = $this->filter_response_by_context($data, $context);

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        $response->add_links($this->prepare_links($order, $request));

        /**
         * Filter the data for a response.
         *
         * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
         * prepared for the response.
         *
         * @param WP_REST_Response   $response   The response object.
         * @param WP_Post            $post       Post object.
         * @param WP_REST_Request    $request    Request object.
         */
        return apply_filters("woocommerce_rest_prepare_{$this->post_type}", $response, $post, $request);
    }

    /**
     * Prepare links for the request.
     *
     * @param WC_Order $order Order object.
     * @param WP_REST_Request $request Request object.
     * @return array Links for the given order.
     */
    protected function prepare_links($order, $request): array
    {
        $links = [
            'self' => [
                'href' => rest_url(sprintf('/%s/%s/%d', $this->namespace, $this->rest_base, $order->get_id())),
            ],
            'collection' => [
                'href' => rest_url(sprintf('/%s/%s', $this->namespace, $this->rest_base)),
            ],
        ];
        if (0 !== (int) $order->get_user_id()) {
            $links['customer'] = [
                'href' => rest_url(sprintf('/%s/customers/%d', $this->namespace, $order->get_user_id())),
            ];
        }
        if (0 !== (int) $order->get_parent_id()) {
            $links['up'] = [
                'href' => rest_url(sprintf('/%s/orders/%d', $this->namespace, $order->get_parent_id())),
            ];
        }
        return $links;
    }

    /**
     * Query args.
     *
     * @param array $args
     * @param WP_REST_Request $request
     * @return array
     */
    public function query_args($args, $request)
    {
        global $wpdb;

        // Set post_status.
        if ('any' !== $request['status']) {
            $args['post_status'] = 'wc-' . $request['status'];
        } else {
            $args['post_status'] = 'any';
        }

        if (isset($request['customer'])) {
            if (! empty($args['meta_query'])) {
                $args['meta_query'] = [];
            }

            $args['meta_query'][] = [
                'key'   => '_customer_user',
                'value' => $request['customer'],
                'type'  => 'NUMERIC',
            ];
        }

        // Search by product.
        if (! empty($request['product'])) {
            $order_ids = $wpdb->get_col($wpdb->prepare("
				SELECT order_id
				FROM {$wpdb->prefix}woocommerce_order_items
				WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d )
				AND order_item_type = 'line_item'
			 ", $request['product']));

            // Force WP_Query return empty if don't found any order.
            $order_ids = ! empty($order_ids) ? $order_ids : [ 0 ];

            $args['post__in'] = $order_ids;
        }

        // Search.
        if (! empty($args['s'])) {
            $order_ids = wc_order_search($args['s']);

            if (! empty($order_ids)) {
                unset($args['s']);
                $args['post__in'] = array_merge($order_ids, [ 0 ]);
            }
        }

        return $args;
    }

    /**
     * Prepare a single order for create.
     *
     * @throws WC_REST_Exception If the customer ID is invalid.
     *
     * @param  WP_REST_Request $request Request object.
     * @return WP_Error|WC_Order $data Object.
     */
    protected function prepare_item_for_database($request)
    {
        $id        = isset($request['id']) ? absint($request['id']) : 0;
        $order     = new WC_Order($id);
        $schema    = $this->get_item_schema();
        $data_keys = array_keys(array_filter($schema['properties'], $this->filter_writable_props(...)));

        if (! is_null($request['customer_id']) && 0 !== $request['customer_id']) {
            if (is_wp_error(Users::get_user_in_current_site($request['customer_id']))) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new WC_REST_Exception('woocommerce_rest_invalid_customer_id', __('Customer ID is invalid.', 'woocommerce'), 400);
            }

            // Make sure customer is part of blog.
            if (is_multisite() && ! is_user_member_of_blog($request['customer_id'])) {
                add_user_to_blog(get_current_blog_id(), $request['customer_id'], 'customer');
            }
        }

        // Handle all writable props
        foreach ($data_keys as $key) {
            $value = $request[ $key ];

            if (! is_null($value)) {
                switch ($key) {
                    case 'billing':
                    case 'shipping':
                        $this->update_address($order, $value, $key);
                        break;
                    case 'line_items':
                    case 'shipping_lines':
                    case 'fee_lines':
                    case 'coupon_lines':
                        if (is_array($value)) {
                            foreach ($value as $item) {
                                if (is_array($item)) {
                                    if ($this->item_is_null($item) || (isset($item['quantity']) && 0 === $item['quantity'])) {
                                        $order->remove_item($item['id']);
                                    } else {
                                        $this->set_item($order, $key, $item);
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        if (is_callable([ $order, "set_{$key}" ])) {
                            $order->{"set_{$key}"}($value);
                        }
                        break;
                }
            }
        }

        /**
         * Filter the data for the insert.
         *
         * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
         * prepared for the response.
         *
         * @param WC_Order           $order      The order object.
         * @param WP_REST_Request    $request    Request object.
         */
        return apply_filters("woocommerce_rest_pre_insert_{$this->post_type}", $order, $request);
    }

    /**
     * Create base WC Order object.
     * @deprecated 3.0.0
     * @param array $data
     * @return WC_Order
     */
    protected function create_base_order($data)
    {
        return wc_create_order($data);
    }

    /**
     * Only return writable props from schema.
     * @param  array $schema
     * @return bool
     */
    protected function filter_writable_props($schema)
    {
        return empty($schema['readonly']);
    }

    /**
     * Create order.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return int|WP_Error
     */
    protected function create_order($request)
    {
        try {
            $order = $this->prepare_item_for_database($request);
            $order->set_created_via('rest-api');
            $order->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
            $order->calculate_totals();
            $order->save();

            // Handle set paid.
            if (true === $request['set_paid']) {
                $order->payment_complete($request['transaction_id']);
            }

            return $order->get_id();
        } catch (WC_Data_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), $e->getErrorData());
        } catch (WC_REST_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }
    }

    /**
     * Update order.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return int|WP_Error
     */
    protected function update_order($request)
    {
        try {
            $order = $this->prepare_item_for_database($request);
            $order->save();

            // Handle set paid.
            if ($order->needs_payment() && true === $request['set_paid']) {
                $order->payment_complete($request['transaction_id']);
            }

            // If items have changed, recalculate order totals.
            if (isset($request['billing']) || isset($request['shipping']) || isset($request['line_items']) || isset($request['shipping_lines']) || isset($request['fee_lines']) || isset($request['coupon_lines'])) {
                $order->calculate_totals(true);
            }

            return $order->get_id();
        } catch (WC_Data_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), $e->getErrorData());
        } catch (WC_REST_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }
    }

    /**
     * Update address.
     *
     * @param WC_Order $order  Order object.
     * @param array    $posted Request data.
     * @param string   $type   Type of address; 'billing' or 'shipping'.
     */
    protected function update_address($order, $posted, $type = 'billing')
    {
        foreach ($posted as $key => $value) {
            if (is_callable([ $order, "set_{$type}_{$key}" ])) {
                $order->{"set_{$type}_{$key}"}($value);
            }
        }
    }

    /**
     * Gets the product ID from the SKU or posted ID.
     *
     * @throws WC_REST_Exception When SKU or ID is not valid.
     * @param array           $posted Request data.
     * @param string          $action 'create' to add line item or 'update' to update it.
     * @return int
     */
    protected function get_product_id($posted, $action = 'create')
    {
        if (! empty($posted['sku'])) {
            $product_id = (int) wc_get_product_id_by_sku($posted['sku']);
        } elseif (! empty($posted['product_id']) && empty($posted['variation_id'])) {
            $product_id = (int) $posted['product_id'];
        } elseif (! empty($posted['variation_id'])) {
            $product_id = (int) $posted['variation_id'];
        } elseif ('update' === $action) {
            $product_id = 0;
        } else {
            throw new WC_REST_Exception('woocommerce_rest_required_product_reference', __('Product ID or SKU is required.', 'woocommerce'), 400);
        }
        return $product_id;
    }

    /**
     * Maybe set an item prop if the value was posted.
     * @param WC_Order_Item $item
     * @param string $prop
     * @param array $posted Request data.
     */
    protected function maybe_set_item_prop($item, $prop, $posted)
    {
        if (isset($posted[ $prop ])) {
            $item->{"set_$prop"}($posted[ $prop ]);
        }
    }

    /**
     * Maybe set item props if the values were posted.
     * @param WC_Order_Item $item
     * @param string[] $props
     * @param array $posted Request data.
     */
    protected function maybe_set_item_props($item, $props, $posted)
    {
        foreach ($props as $prop) {
            $this->maybe_set_item_prop($item, $prop, $posted);
        }
    }

    /**
     * Create or update a line item.
     *
     * @param array $posted Line item data.
     * @param string $action 'create' to add line item or 'update' to update it.
     *
     * @return WC_Order_Item_Product
     * @throws WC_REST_Exception Invalid data, server error.
     */
    protected function prepare_line_items($posted, $action = 'create')
    {
        $item    = new WC_Order_Item_Product(! empty($posted['id']) ? $posted['id'] : '');
        $product = wc_get_product($this->get_product_id($posted, $action));

        if ($product && $product !== $item->get_product()) {
            $item->set_product($product);

            if ('create' === $action) {
                $quantity = $posted['quantity'] ?? 1;
                $total    = wc_get_price_excluding_tax($product, [ 'qty' => $quantity ]);
                $item->set_total($total);
                $item->set_subtotal($total);
            }
        }

        $this->maybe_set_item_props($item, [ 'name', 'quantity', 'total', 'subtotal', 'tax_class' ], $posted);

        return $item;
    }

    /**
     * Create or update an order shipping method.
     *
     * @param $posted $shipping Item data.
     * @param string $action 'create' to add shipping or 'update' to update it.
     *
     * @return WC_Order_Item_Shipping
     * @throws WC_REST_Exception Invalid data, server error.
     */
    protected function prepare_shipping_lines($posted, $action)
    {
        $item = new WC_Order_Item_Shipping(! empty($posted['id']) ? $posted['id'] : '');

        if ('create' === $action) {
            if (empty($posted['method_id'])) {
                throw new WC_REST_Exception('woocommerce_rest_invalid_shipping_item', __('Shipping method ID is required.', 'woocommerce'), 400);
            }
        }

        $this->maybe_set_item_props($item, [ 'method_id', 'method_title', 'total' ], $posted);

        return $item;
    }

    /**
     * Create or update an order fee.
     *
     * @param array $posted Item data.
     * @param string $action 'create' to add fee or 'update' to update it.
     *
     * @return WC_Order_Item_Fee
     * @throws WC_REST_Exception Invalid data, server error.
     */
    protected function prepare_fee_lines($posted, $action)
    {
        $item = new WC_Order_Item_Fee(! empty($posted['id']) ? $posted['id'] : '');

        if ('create' === $action) {
            if (empty($posted['name'])) {
                throw new WC_REST_Exception('woocommerce_rest_invalid_fee_item', __('Fee name is required.', 'woocommerce'), 400);
            }
        }

        $this->maybe_set_item_props($item, [ 'name', 'tax_class', 'tax_status', 'total' ], $posted);

        return $item;
    }

    /**
     * Create or update an order coupon.
     *
     * @param array $posted Item data.
     * @param string $action 'create' to add coupon or 'update' to update it.
     *
     * @return WC_Order_Item_Coupon
     * @throws WC_REST_Exception Invalid data, server error.
     */
    protected function prepare_coupon_lines($posted, $action)
    {
        $item = new WC_Order_Item_Coupon(! empty($posted['id']) ? $posted['id'] : '');

        if ('create' === $action) {
            $coupon_code = ArrayUtil::get_value_or_default($posted, 'code');
            if (StringUtil::is_null_or_whitespace($coupon_code)) {
                throw new WC_REST_Exception('woocommerce_rest_invalid_coupon_coupon', __('Coupon code is required.', 'woocommerce'), 400);
            }
        }

        $this->maybe_set_item_props($item, [ 'code', 'discount' ], $posted);

        return $item;
    }

    /**
     * Wrapper method to create/update order items.
     * When updating, the item ID provided is checked to ensure it is associated
     * with the order.
     *
     * @param WC_Order $order order
     * @param string $item_type
     * @param array $posted item provided in the request body
     * @throws WC_REST_Exception If item ID is not associated with order
     */
    protected function set_item($order, $item_type, $posted)
    {
        global $wpdb;

        if (! empty($posted['id'])) {
            $action = 'update';
        } else {
            $action = 'create';
        }

        $method = 'prepare_' . $item_type;

        // Verify provided line item ID is associated with order.
        if ('update' === $action) {
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d AND order_id = %d",
                    absint($posted['id']),
                    absint($order->get_id())
                )
            );
            if (is_null($result)) {
                throw new WC_REST_Exception('woocommerce_rest_invalid_item_id', __('Order item ID provided is not associated with order.', 'woocommerce'), 400);
            }
        }

        // Prepare item data
        $item = $this->$method($posted, $action);

        /**
         * Action hook to adjust item before save.
         * @since 3.0.0
         */
        do_action('woocommerce_rest_set_order_item', $item, $posted);

        // Save or add to order
        if ('create' === $action) {
            $order->add_item($item);
        } else {
            $item->save();
        }
    }

    /**
     * Helper method to check if the resource ID associated with the provided item is null.
     * Items can be deleted by setting the resource ID to null.
     *
     * @param array $item Item provided in the request body.
     * @return bool True if the item resource ID is null, false otherwise.
     */
    protected function item_is_null($item)
    {
        $keys = [ 'product_id', 'method_id', 'method_title', 'name', 'code' ];

        foreach ($keys as $key) {
            if (array_key_exists($key, $item) && is_null($item[ $key ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a single item.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {
        if (! empty($request['id'])) {
            /* translators: %s: post type */
            return new WP_Error("woocommerce_rest_{$this->post_type}_exists", sprintf(__('Cannot create existing %s.', 'woocommerce'), $this->post_type), [ 'status' => 400 ]);
        }

        $order_id = $this->create_order($request);
        if (is_wp_error($order_id)) {
            return $order_id;
        }

        $post = get_post($order_id);
        $this->update_additional_fields_for_object($post, $request);

        /**
         * Fires after a single item is created or updated via the REST API.
         *
         * @param WP_Post         $post      Post object.
         * @param WP_REST_Request $request   Request object.
         * @param boolean         $creating  True when creating item, false when updating.
         */
        do_action("woocommerce_rest_insert_{$this->post_type}", $post, $request, true);
        $request->set_param('context', 'edit');
        $response = $this->prepare_item_for_response($post, $request);
        $response = rest_ensure_response($response);
        $response->set_status(201);
        $response->header('Location', rest_url(sprintf('/%s/%s/%d', $this->namespace, $this->rest_base, $post->ID)));

        return $response;
    }

    /**
     * Update a single order.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item($request)
    {
        try {
            $post_id = (int) $request['id'];

            if (empty($post_id) || get_post_type($post_id) !== $this->post_type) {
                return new WP_Error("woocommerce_rest_{$this->post_type}_invalid_id", __('ID is invalid.', 'woocommerce'), [ 'status' => 400 ]);
            }

            $order_id = $this->update_order($request);
            if (is_wp_error($order_id)) {
                return $order_id;
            }

            $post = get_post($order_id);
            $this->update_additional_fields_for_object($post, $request);

            /**
             * Fires after a single item is created or updated via the REST API.
             *
             * @param WP_Post         $post      Post object.
             * @param WP_REST_Request $request   Request object.
             * @param boolean         $creating  True when creating item, false when updating.
             */
            do_action("woocommerce_rest_insert_{$this->post_type}", $post, $request, false);
            $request->set_param('context', 'edit');
            $response = $this->prepare_item_for_response($post, $request);
            return rest_ensure_response($response);

        } catch (Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }
    }

    /**
     * Get order statuses without prefixes.
     * @return array
     */
    protected function get_order_statuses()
    {
        $order_statuses = [];

        foreach (array_keys(wc_get_order_statuses()) as $status) {
            $order_statuses[] = str_replace('wc-', '', $status);
        }

        return $order_statuses;
    }

    /**
     * Check if a given request has access to read an item.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request)
    {
        $object = wc_get_order((int) $request['id']);

        if ((! $object || 0 === $object->get_id()) && ! wc_rest_check_post_permissions($this->post_type, 'read')) {
            return new WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot view this resource.', 'woocommerce'), [ 'status' => rest_authorization_required_code() ]);
        }

        return parent::get_item_permissions_check($request);
    }

    /**
     * Check if a given request has access to update an item.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check($request)
    {
        $object = wc_get_order((int) $request['id']);

        if ((! $object || 0 === $object->get_id()) && ! wc_rest_check_post_permissions($this->post_type, 'read')) {
            return new WP_Error('woocommerce_rest_cannot_edit', __('Sorry, you are not allowed to edit this resource.', 'woocommerce'), [ 'status' => rest_authorization_required_code() ]);
        }

        return parent::update_item_permissions_check($request);
    }

    /**
     * Check if a given request has access to delete an item.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request)
    {
        $object = wc_get_order((int) $request['id']);

        if ((! $object || 0 === $object->get_id()) && ! wc_rest_check_post_permissions($this->post_type, 'read')) {
            return new WP_Error('woocommerce_rest_cannot_delete', __('Sorry, you are not allowed to delete this resource.', 'woocommerce'), [ 'status' => rest_authorization_required_code() ]);
        }

        return parent::delete_item_permissions_check($request);
    }

    /**
     * Get the Order's schema, conforming to JSON Schema.
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
                'id' => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'parent_id' => [
                    'description' => __('Parent order ID.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
                'status' => [
                    'description' => __('Order status.', 'woocommerce'),
                    'type'        => 'string',
                    'default'     => OrderStatus::PENDING,
                    'enum'        => $this->get_order_statuses(),
                    'context'     => [ 'view', 'edit' ],
                ],
                'order_key' => [
                    'description' => __('Order key.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'number' => [
                    'description' => __('Order number.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'currency' => [
                    'description' => __('Currency the order was created with, in ISO format.', 'woocommerce'),
                    'type'        => 'string',
                    'default'     => get_woocommerce_currency(),
                    'enum'        => array_keys(get_woocommerce_currencies()),
                    'context'     => [ 'view', 'edit' ],
                ],
                'version' => [
                    'description' => __('Version of WooCommerce which last updated the order.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'prices_include_tax' => [
                    'description' => __('True the prices included tax during checkout.', 'woocommerce'),
                    'type'        => 'boolean',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_created' => [
                    'description' => __('The date the order was created, as GMT.', 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_modified' => [
                    'description' => __('The date the order was last modified, as GMT.', 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'customer_id' => [
                    'description' => __('User ID who owns the order. 0 for guests.', 'woocommerce'),
                    'type'        => 'integer',
                    'default'     => 0,
                    'context'     => [ 'view', 'edit' ],
                ],
                'discount_total' => [
                    'description' => __('Total discount amount for the order.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'discount_tax' => [
                    'description' => __('Total discount tax amount for the order.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'shipping_total' => [
                    'description' => __('Total shipping amount for the order.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'shipping_tax' => [
                    'description' => __('Total shipping tax amount for the order.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'cart_tax' => [
                    'description' => __('Sum of line item taxes only.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'total' => [
                    'description' => __('Grand total.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'total_tax' => [
                    'description' => __('Sum of all taxes.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'billing' => [
                    'description' => __('Billing address.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'properties'  => [
                        'first_name' => [
                            'description' => __('First name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'last_name' => [
                            'description' => __('Last name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'company' => [
                            'description' => __('Company name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_1' => [
                            'description' => __('Address line 1.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_2' => [
                            'description' => __('Address line 2.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'city' => [
                            'description' => __('City name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'state' => [
                            'description' => __('ISO code or name of the state, province or district.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'postcode' => [
                            'description' => __('Postal code.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'country' => [
                            'description' => __('Country code in ISO 3166-1 alpha-2 format.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'email' => [
                            'description' => __('Email address.', 'woocommerce'),
                            'type'        => 'string',
                            'format'      => 'email',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'phone' => [
                            'description' => __('Phone number.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                    ],
                ],
                'shipping' => [
                    'description' => __('Shipping address.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'properties'  => [
                        'first_name' => [
                            'description' => __('First name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'last_name' => [
                            'description' => __('Last name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'company' => [
                            'description' => __('Company name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_1' => [
                            'description' => __('Address line 1.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_2' => [
                            'description' => __('Address line 2.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'city' => [
                            'description' => __('City name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'state' => [
                            'description' => __('ISO code or name of the state, province or district.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'postcode' => [
                            'description' => __('Postal code.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'country' => [
                            'description' => __('Country code in ISO 3166-1 alpha-2 format.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                    ],
                ],
                'payment_method' => [
                    'description' => __('Payment method ID.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'payment_method_title' => [
                    'description' => __('Payment method title.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'set_paid' => [
                    'description' => __('Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce'),
                    'type'        => 'boolean',
                    'default'     => false,
                    'context'     => [ 'edit' ],
                ],
                'transaction_id' => [
                    'description' => __('Unique transaction ID.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'customer_ip_address' => [
                    'description' => __("Customer's IP address.", 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'customer_user_agent' => [
                    'description' => __('User agent of the customer.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'created_via' => [
                    'description' => __('Shows where the order was created.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'customer_note' => [
                    'description' => __('Note left by customer during checkout.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'date_completed' => [
                    'description' => __("The date the order was completed, in the site's timezone.", 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_paid' => [
                    'description' => __("The date the order was paid, in the site's timezone.", 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'cart_hash' => [
                    'description' => __('MD5 hash of cart items to ensure orders are not modified.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'line_items' => [
                    'description' => __('Line items data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'name' => [
                                'description' => __('Product name.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'sku' => [
                                'description' => __('Product SKU.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'product_id' => [
                                'description' => __('Product ID.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'variation_id' => [
                                'description' => __('Variation ID, if applicable.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'quantity' => [
                                'description' => __('Quantity ordered.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'tax_class' => [
                                'description' => __('Tax class of product.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'price' => [
                                'description' => __('Product price.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'subtotal' => [
                                'description' => __('Line subtotal (before discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'subtotal_tax' => [
                                'description' => __('Line subtotal tax (before discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total' => [
                                'description' => __('Line total (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total_tax' => [
                                'description' => __('Line total tax (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'taxes' => [
                                'description' => __('Line taxes.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id' => [
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
                                        'subtotal' => [
                                            'description' => __('Tax subtotal.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                            'meta' => [
                                'description' => __('Line item meta data.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'key' => [
                                            'description' => __('Meta key.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                        'label' => [
                                            'description' => __('Meta label.', 'woocommerce'),
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
                        ],
                    ],
                ],
                'tax_lines' => [
                    'description' => __('Tax lines data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'rate_code' => [
                                'description' => __('Tax rate code.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'rate_id' => [
                                'description' => __('Tax rate ID.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'label' => [
                                'description' => __('Tax rate label.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'compound' => [
                                'description' => __('Show if is a compound tax rate.', 'woocommerce'),
                                'type'        => 'boolean',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'tax_total' => [
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
                        ],
                    ],
                ],
                'shipping_lines' => [
                    'description' => __('Shipping lines data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => [
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
                            'method_id' => [
                                'description' => __('Shipping method ID.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total' => [
                                'description' => __('Line total (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total_tax' => [
                                'description' => __('Line total tax (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'taxes' => [
                                'description' => __('Line taxes.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id' => [
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
                        ],
                    ],
                ],
                'fee_lines' => [
                    'description' => __('Fee lines data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'name' => [
                                'description' => __('Fee name.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'tax_class' => [
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
                            'total' => [
                                'description' => __('Line total (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'total_tax' => [
                                'description' => __('Line total tax (after discounts).', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'taxes' => [
                                'description' => __('Line taxes.', 'woocommerce'),
                                'type'        => 'array',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'items'       => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'id' => [
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
                                        'subtotal' => [
                                            'description' => __('Tax subtotal.', 'woocommerce'),
                                            'type'        => 'string',
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'coupon_lines' => [
                    'description' => __('Coupons line data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => [
                                'description' => __('Item ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'code' => [
                                'description' => __('Coupon code.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'discount' => [
                                'description' => __('Discount total.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'discount_tax' => [
                                'description' => __('Discount total tax.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                ],
                'refunds' => [
                    'description' => __('List of refunds.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id' => [
                                'description' => __('Refund ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'reason' => [
                                'description' => __('Refund reason.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'total' => [
                                'description' => __('Refund total.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
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
        $params = parent::get_collection_params();

        $params['status'] = [
            'default'           => 'any',
            'description'       => __('Limit result set to orders assigned a specific status.', 'woocommerce'),
            'type'              => 'string',
            'enum'              => array_merge([ 'any' ], $this->get_order_statuses()),
            'sanitize_callback' => 'sanitize_key',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['customer'] = [
            'description'       => __('Limit result set to orders assigned a specific customer.', 'woocommerce'),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['product'] = [
            'description'       => __('Limit result set to orders assigned a specific product.', 'woocommerce'),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['dp'] = [
            'default'           => wc_get_price_decimals(),
            'description'       => __('Number of decimal points to use in each resource.', 'woocommerce'),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }
}
