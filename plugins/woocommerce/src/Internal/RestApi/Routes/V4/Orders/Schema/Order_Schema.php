<?php

/**
 * OrderSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Order;
use WP_REST_Request;

/**
 * OrderSchema class.
 */
class OrderSchema extends AbstractSchema
{
    use CogsAwareTrait;

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'order';

    /**
     * The order item schema.
     */
    private ?\Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderItemSchema $order_item_schema = null;

    /**
     * The order coupon schema.
     */
    private ?\Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderCouponSchema $order_coupon_schema = null;

    /**
     * The order fee schema.
     */
    private ?\Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderFeeSchema $order_fee_schema = null;

    /**
     * The order tax schema.
     */
    private ?\Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderTaxSchema $order_tax_schema = null;

    /**
     * The order shipping schema.
     */
    private ?\Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderShippingSchema $order_shipping_schema = null;

    /**
     * Initialize the schema.
     *
     * @internal
     * @param OrderItemSchema     $order_item_schema The order item schema.
     * @param OrderCouponSchema   $order_coupon_schema The order coupon schema.
     * @param OrderFeeSchema      $order_fee_schema The order fee schema.
     * @param OrderTaxSchema      $order_tax_schema The order tax schema.
     * @param OrderShippingSchema $order_shipping_schema The order shipping schema.
     */
    final public function init(OrderItemSchema $order_item_schema, OrderCouponSchema $order_coupon_schema, OrderFeeSchema $order_fee_schema, OrderTaxSchema $order_tax_schema, OrderShippingSchema $order_shipping_schema): void
    {
        $this->order_item_schema     = $order_item_schema;
        $this->order_coupon_schema   = $order_coupon_schema;
        $this->order_fee_schema      = $order_fee_schema;
        $this->order_tax_schema      = $order_tax_schema;
        $this->order_shipping_schema = $order_shipping_schema;
    }

    /**
     * Return all properties for the item schema.
     *
     * Note that context determines under which context data should be visible. For example, edit would be the context
     * used when getting records with the intent of editing them. embed context allows the data to be visible when the
     * item is being embedded in another response.
     */
    public function get_item_schema_properties(): array
    {
        $schema = [
            'id'                   => [
                'description' => __('Unique identifier for the resource.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'parent_id'            => [
                'description' => __('Parent order ID.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            ],
            'number'               => [
                'description' => __('Order number.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'order_key'            => [
                'description' => __('Order key.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'created_via'          => [
                'description' => __('Shows where the order was created.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            ],
            'version'              => [
                'description' => __('Version of WooCommerce which last updated the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'status'               => [
                'description' => __('Order status.', 'woocommerce'),
                'type'        => 'string',
                'default'     => OrderStatus::PENDING,
                'enum'        => array_map(OrderUtil::class . '::remove_status_prefix', array_merge([ OrderStatus::AUTO_DRAFT ], array_keys(wc_get_order_statuses()))),
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            ],
            'currency'             => [
                'description' => __('Currency the order was created with, in ISO format.', 'woocommerce'),
                'type'        => 'string',
                'default'     => get_woocommerce_currency(),
                'enum'        => array_keys(get_woocommerce_currencies()),
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'currency_symbol'      => [
                'description' => __('Currency symbol for the currency which can be used to format returned prices.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_created'         => [
                'description' => __("The date the order was created, in the site's timezone.", 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_created_gmt'     => [
                'description' => __('The date the order was created, as GMT.', 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_modified'        => [
                'description' => __("The date the order was last modified, in the site's timezone.", 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_modified_gmt'    => [
                'description' => __('The date the order was last modified, as GMT.', 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'discount_total'       => [
                'description' => __('Total discount amount for the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'discount_tax'         => [
                'description' => __('Total discount tax amount for the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'shipping_total'       => [
                'description' => __('Total shipping amount for the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'shipping_tax'         => [
                'description' => __('Total shipping tax amount for the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'cart_tax'             => [
                'description' => __('Sum of line item taxes only.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'total'                => [
                'description' => __('Grand total.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'total_tax'            => [
                'description' => __('Sum of all taxes.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'refund_total'         => [
                'description' => __('Total refund amount for the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'refund_tax'           => [
                'description' => __('Total refund tax amount for the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'prices_include_tax'   => [
                'description' => __('True the prices included tax during checkout.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'customer_id'          => [
                'description' => __('User ID who owns the order. 0 for guests.', 'woocommerce'),
                'type'        => 'integer',
                'default'     => 0,
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            ],
            'customer_ip_address'  => [
                'description' => __("Customer's IP address.", 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'customer_user_agent'  => [
                'description' => __('User agent of the customer.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'customer_note'        => [
                'description' => __('Note left by customer during checkout.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            ],
            'billing'              => [
                'description' => __('Billing address.', 'woocommerce'),
                'type'        => 'object',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'properties'  => [
                    'first_name' => [
                        'description' => __('First name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'last_name'  => [
                        'description' => __('Last name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'company'    => [
                        'description' => __('Company name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'address_1'  => [
                        'description' => __('Address line 1', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'address_2'  => [
                        'description' => __('Address line 2', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'city'       => [
                        'description' => __('City name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'state'      => [
                        'description' => __('ISO code or name of the state, province or district.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'postcode'   => [
                        'description' => __('Postal code.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'country'    => [
                        'description' => __('Country code in ISO 3166-1 alpha-2 format.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'email'      => [
                        'description' => __('Email address.', 'woocommerce'),
                        'type'        => [ 'string', 'null' ],
                        'format'      => 'email',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'phone'      => [
                        'description' => __('Phone number.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                ],
            ],
            'shipping'             => [
                'description' => __('Shipping address.', 'woocommerce'),
                'type'        => 'object',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'properties'  => [
                    'first_name' => [
                        'description' => __('First name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'last_name'  => [
                        'description' => __('Last name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'company'    => [
                        'description' => __('Company name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'address_1'  => [
                        'description' => __('Address line 1', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'address_2'  => [
                        'description' => __('Address line 2', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'city'       => [
                        'description' => __('City name.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'state'      => [
                        'description' => __('ISO code or name of the state, province or district.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'postcode'   => [
                        'description' => __('Postal code.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'country'    => [
                        'description' => __('Country code in ISO 3166-1 alpha-2 format.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                    'phone'      => [
                        'description' => __('Phone number.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                    ],
                ],
            ],
            'payment_method'       => [
                'description' => __('Payment method ID.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            ],
            'payment_method_title' => [
                'description' => __('Payment method title.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'arg_options' => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
            'transaction_id'       => [
                'description' => __('Unique transaction ID.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            ],
            'date_paid'            => [
                'description' => __("The date the order was paid, in the site's timezone.", 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_paid_gmt'        => [
                'description' => __('The date the order was paid, as GMT.', 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_completed'       => [
                'description' => __("The date the order was completed, in the site's timezone.", 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_completed_gmt'   => [
                'description' => __('The date the order was completed, as GMT.', 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'cart_hash'            => [
                'description' => __('MD5 hash of cart items to ensure orders are not modified.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'meta_data'            => [
                'description' => __('Meta data.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'id'    => [
                            'description' => __('Meta ID.', 'woocommerce'),
                            'type'        => 'integer',
                            'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                            'readonly'    => true,
                        ],
                        'key'   => [
                            'description' => __('Meta key.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                        ],
                        'value' => [
                            'description' => __('Meta value.', 'woocommerce'),
                            'type'        => [ 'null', 'object', 'string', 'number', 'boolean', 'integer', 'array' ],
                            'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                        ],
                    ],
                ],
            ],
            'line_items'           => [
                'description' => __('A list of line items (products) within this order.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'items'       => [
                    'type'       => 'object',
                    'properties' => $this->order_item_schema->get_item_schema_properties(),
                ],
            ],
            'tax_lines'            => [
                'description' => __('Tax lines data.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
                'items'       => [
                    'type'       => 'object',
                    'properties' => $this->order_tax_schema->get_item_schema_properties(),
                ],
            ],
            'shipping_lines'       => [
                'description' => __('Shipping lines data.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'items'       => [
                    'type'       => 'object',
                    'properties' => $this->order_shipping_schema->get_item_schema_properties(),
                ],
            ],
            'fee_lines'            => [
                'description' => __('Fee lines data.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'items'       => [
                    'type'       => 'object',
                    'properties' => $this->order_fee_schema->get_item_schema_properties(),
                ],
            ],
            'coupon_lines'         => [
                'description' => __('Coupons line data.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'items'       => [
                    'type'       => 'object',
                    'properties' => $this->order_coupon_schema->get_item_schema_properties(),
                ],
            ],
            'payment_url'          => [
                'description' => __('Order payment URL.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'is_editable'          => [
                'description' => __('Whether an order can be edited.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'needs_payment'        => [
                'description' => __('Whether an order needs payment, based on status and order total.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'needs_processing'     => [
                'description' => __('Whether an order needs processing before it can be completed.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'fulfillment_status'   => [
                'description' => __('The fulfillment status of the order.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
        ];

        if ($this->cogs_is_enabled()) {
            return self::add_cogs_related_schema($schema);
        }

        return $schema;
    }

    /**
     * Add the Cost of Goods Sold related fields to the schema.
     *
     * @param array $schema The original schema.
     * @return array The updated schema.
     */
    private static function add_cogs_related_schema(array $schema): array
    {
        $schema['cost_of_goods_sold'] = [
            'description' => __('Cost of Goods Sold data.', 'woocommerce'),
            'type'        => 'object',
            'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
            'properties'  => [
                'total_value' => [
                    'description' => __('Total value of the Cost of Goods Sold for the order.', 'woocommerce'),
                    'type'        => 'number',
                    'readonly'    => true,
                    'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                ],
            ],
        ];
        return $schema;
    }

    /**
     * Get an item response.
     *
     * @param WC_Order        $order Order instance.
     * @param WP_REST_Request $request Request object.
     * @param array           $include_fields Fields to include in the response.
     */
    public function get_item_response($order, WP_REST_Request $request, array $include_fields = []): array
    {
        $dp   = is_null($request['num_decimals']) ? wc_get_price_decimals() : absint($request['num_decimals']);
        $data = [
            'id'                   => $order->get_id(),
            'parent_id'            => $order->get_parent_id(),
            'number'               => $order->get_order_number(),
            'order_key'            => $order->get_order_key(),
            'created_via'          => $order->get_created_via(),
            'version'              => $order->get_version(),
            'status'               => OrderUtil::remove_status_prefix($order->get_status()),
            'currency'             => $order->get_currency(),
            'currency_symbol'      => html_entity_decode(get_woocommerce_currency_symbol($order->get_currency()), ENT_QUOTES),
            'date_created'         => wc_rest_prepare_date_response($order->get_date_created(), false),
            'date_created_gmt'     => wc_rest_prepare_date_response($order->get_date_created()),
            'date_modified'        => wc_rest_prepare_date_response($order->get_date_modified(), false),
            'date_modified_gmt'    => wc_rest_prepare_date_response($order->get_date_modified()),
            'discount_total'       => wc_format_decimal($order->get_discount_total(), $dp),
            'discount_tax'         => wc_format_decimal($order->get_discount_tax(), $dp),
            'shipping_total'       => wc_format_decimal($order->get_shipping_total(), $dp),
            'shipping_tax'         => wc_format_decimal($order->get_shipping_tax(), $dp),
            'cart_tax'             => wc_format_decimal($order->get_cart_tax(), $dp),
            'total'                => wc_format_decimal($order->get_total(), $dp),
            'total_tax'            => wc_format_decimal($order->get_total_tax(), $dp),
            'prices_include_tax'   => $order->get_prices_include_tax(),
            'customer_id'          => $order->get_customer_id(),
            'customer_ip_address'  => $order->get_customer_ip_address(),
            'customer_user_agent'  => $order->get_customer_user_agent(),
            'customer_note'        => $order->get_customer_note(),
            'billing'              => [
                'first_name' => $order->get_billing_first_name(),
                'last_name'  => $order->get_billing_last_name(),
                'company'    => $order->get_billing_company(),
                'address_1'  => $order->get_billing_address_1(),
                'address_2'  => $order->get_billing_address_2(),
                'city'       => $order->get_billing_city(),
                'state'      => $order->get_billing_state(),
                'postcode'   => $order->get_billing_postcode(),
                'country'    => $order->get_billing_country(),
                'email'      => $order->get_billing_email(),
                'phone'      => $order->get_billing_phone(),
            ],
            'shipping'             => [
                'first_name' => $order->get_shipping_first_name(),
                'last_name'  => $order->get_shipping_last_name(),
                'company'    => $order->get_shipping_company(),
                'address_1'  => $order->get_shipping_address_1(),
                'address_2'  => $order->get_shipping_address_2(),
                'city'       => $order->get_shipping_city(),
                'state'      => $order->get_shipping_state(),
                'postcode'   => $order->get_shipping_postcode(),
                'country'    => $order->get_shipping_country(),
                'phone'      => $order->get_shipping_phone(),
            ],
            'payment_method'       => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'transaction_id'       => $order->get_transaction_id(),
            'date_paid'            => wc_rest_prepare_date_response($order->get_date_paid(), false),
            'date_paid_gmt'        => wc_rest_prepare_date_response($order->get_date_paid()),
            'date_completed'       => wc_rest_prepare_date_response($order->get_date_completed(), false),
            'date_completed_gmt'   => wc_rest_prepare_date_response($order->get_date_completed()),
            'cart_hash'            => $order->get_cart_hash(),
            'payment_url'          => $order->get_checkout_payment_url(),
            'is_editable'          => $order->is_editable(),
            'needs_payment'        => $order->needs_payment(),
            'needs_processing'     => $order->needs_processing(),
            'fulfillment_status'   => FulfillmentUtils::get_order_fulfillment_status($order),
        ];

        if (in_array('refund_total', $include_fields, true)) {
            $data['refund_total'] = wc_format_decimal($order->get_total_refunded(), $dp);
        }

        if (in_array('refund_tax', $include_fields, true)) {
            $data['refund_tax'] = wc_format_decimal($order->get_total_tax_refunded(), $dp);
        }

        if (in_array('line_items', $include_fields, true)) {
            $line_items         = $order->get_items('line_item');
            $data['line_items'] = [];
            foreach ($line_items as $line_item) {
                $data['line_items'][] = $this->order_item_schema->get_item_response($line_item, $request);
            }
        }

        if (in_array('shipping_lines', $include_fields, true)) {
            $line_items             = $order->get_items('shipping');
            $data['shipping_lines'] = [];
            foreach ($line_items as $line_item) {
                $data['shipping_lines'][] = $this->order_shipping_schema->get_item_response($line_item, $request);
            }
        }

        if (in_array('coupon_lines', $include_fields, true)) {
            $line_items           = $order->get_items('coupon');
            $data['coupon_lines'] = [];
            foreach ($line_items as $line_item) {
                $data['coupon_lines'][] = $this->order_coupon_schema->get_item_response($line_item, $request);
            }
        }

        if (in_array('fee_lines', $include_fields, true)) {
            $line_items        = $order->get_items('fee');
            $data['fee_lines'] = [];
            foreach ($line_items as $line_item) {
                $data['fee_lines'][] = $this->order_fee_schema->get_item_response($line_item, $request);
            }
        }

        if (in_array('tax_lines', $include_fields, true)) {
            $line_items        = $order->get_items('tax');
            $data['tax_lines'] = [];
            foreach ($line_items as $line_item) {
                $data['tax_lines'][] = $this->order_tax_schema->get_item_response($line_item, $request);
            }
        }

        if (in_array('meta_data', $include_fields, true)) {
            $filtered_meta_data = $this->filter_internal_meta_keys($order->get_meta_data());
            $data['meta_data']  = [];
            foreach ($filtered_meta_data as $meta_item) {
                $data['meta_data'][] = [
                    'id'    => $meta_item->id,
                    'key'   => $meta_item->key,
                    'value' => $meta_item->value,
                ];
            }
        }

        // Add COGS data.
        if ($this->cogs_is_enabled() && in_array('cost_of_goods_sold', $include_fields, true)) {
            $data['cost_of_goods_sold']['total_value'] = $order->get_cogs_total_value();
        }

        return array_intersect_key($data, array_flip($include_fields));
    }

    /**
     * With HPOS, few internal meta keys such as _billing_address_index, _shipping_address_index are not considered internal anymore (since most internal keys were flattened into dedicated columns).
     *
     * This function helps in filtering out any remaining internal meta keys with HPOS is enabled.
     *
     * @param array $meta_data Order meta data.
     * @return array Filtered order meta data.
     */
    protected function filter_internal_meta_keys($meta_data)
    {
        if (! OrderUtil::custom_orders_table_usage_is_enabled()) {
            return $meta_data;
        }
        $cpt_hidden_keys = (new \WC_Order_Data_Store_CPT())->get_internal_meta_keys();
        $meta_data       = array_filter(
            $meta_data,
            fn ($meta) => ! in_array($meta->key, $cpt_hidden_keys, true)
        );
        return array_values($meta_data);
    }
}
