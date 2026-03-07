<?php

/**
 * RefundSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareTrait;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderFeeSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderItemSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderShippingSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Orders\Schema\OrderTaxSchema;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WP_REST_Request;

/**
 * RefundSchema class.
 */
class RefundSchema extends AbstractSchema
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
     * @param OrderFeeSchema      $order_fee_schema The order fee schema.
     * @param OrderTaxSchema      $order_tax_schema The order tax schema.
     * @param OrderShippingSchema $order_shipping_schema The order shipping schema.
     */
    final public function init(OrderItemSchema $order_item_schema, OrderFeeSchema $order_fee_schema, OrderTaxSchema $order_tax_schema, OrderShippingSchema $order_shipping_schema): void
    {
        $this->order_item_schema     = $order_item_schema;
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
            'id'               => [
                'description' => __('Unique identifier for the refund.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'order_id'         => [
                'description'       => __('The ID of the order that was refunded.', 'woocommerce'),
                'type'              => 'integer',
                'context'           => self::VIEW_EDIT_EMBED_CONTEXT,
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
            'amount'           => [
                'description'       => __('Amount that was refunded. This is calculated from the line items if not provided.', 'woocommerce'),
                'type'              => 'number',
                'context'           => self::VIEW_EDIT_EMBED_CONTEXT,
                'default'           => 0,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'rest_validate_request_arg',
            ],
            'reason'           => [
                'description'       => __('Reason for the refund.', 'woocommerce'),
                'type'              => 'string',
                'context'           => self::VIEW_EDIT_EMBED_CONTEXT,
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'currency'         => [
                'description' => __('Currency the refund was created with, in ISO format.', 'woocommerce'),
                'type'        => 'string',
                'default'     => get_woocommerce_currency(),
                'enum'        => array_keys(get_woocommerce_currencies()),
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'currency_symbol'  => [
                'description' => __('Currency symbol for the currency which can be used to format returned prices.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_created'     => [
                'description' => __("The date the refund was created, in the site's timezone.", 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'date_created_gmt' => [
                'description' => __('The date the refund was created, as GMT.', 'woocommerce'),
                'type'        => 'string',
                'format'      => 'date-time',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'refunded_by'      => [
                'description' => __('User who created the refund.', 'woocommerce'),
                'type'        => 'object',
                'properties'  => [
                    'id'           => [
                        'description' => __('User ID of user who created the refund.', 'woocommerce'),
                        'type'        => 'integer',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                        'readonly'    => true,
                    ],
                    'display_name' => [
                        'description' => __('Display name of the user who created the refund.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                        'readonly'    => true,
                    ],
                    'avatar_url'   => [
                        'description' => __('Avatar URL of the user who created the refund.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                        'readonly'    => true,
                        'format'      => 'uri',
                    ],
                ],
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'refunded_payment' => [
                'description' => __('If the payment was refunded via the API.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
            ],
            'meta_data'        => [
                'description' => __('Meta data.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'readonly'    => true,
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
            'line_items'       => [
                'description' => __('Refunded line items. This can include products, fees, and shipping lines, combined into a single array.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                'default'     => [],
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'id'           => [
                            'description' => __('ID of the refund line item. This is not the ID of the original line item.', 'woocommerce'),
                            'type'        => 'integer',
                            'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                            'readonly'    => true,
                        ],
                        'line_item_id' => [
                            'description'       => __('ID of the original line item.', 'woocommerce'),
                            'type'              => 'integer',
                            'context'           => self::VIEW_EDIT_EMBED_CONTEXT,
                            'required'          => true,
                            'sanitize_callback' => 'absint',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                        'quantity'     => [
                            'description'       => __('Quantity refunded.', 'woocommerce'),
                            'type'              => 'integer',
                            'context'           => self::VIEW_EDIT_EMBED_CONTEXT,
                            'default'           => 0,
                            'sanitize_callback' => 'wc_stock_amount',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                        'refund_total' => [
                            'description'       => __('Total refunded for this item.', 'woocommerce'),
                            'type'              => 'number',
                            'context'           => self::VIEW_EDIT_EMBED_CONTEXT,
                            'default'           => 0,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                        'refund_tax'   => [
                            'description' => __('Optional: Taxes refunded for this item. If not provided, tax will be automatically extracted from refund_total using the order\'s tax rates.', 'woocommerce'),
                            'type'        => 'array',
                            'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                            'default'     => [],
                            'items'       => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'           => [
                                        'description' => __('Tax ID.', 'woocommerce'),
                                        'type'        => 'integer',
                                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                                        'required'    => true,
                                        'sanitize_callback' => 'absint',
                                        'validate_callback' => 'rest_validate_request_arg',
                                    ],
                                    'refund_total' => [
                                        'description' => __('Amount refunded for this tax.', 'woocommerce'),
                                        'type'        => 'number',
                                        'context'     => self::VIEW_EDIT_EMBED_CONTEXT,
                                        'required'    => true,
                                        'sanitize_callback' => 'sanitize_text_field',
                                        'validate_callback' => 'rest_validate_request_arg',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
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
            'readonly'    => true,
            'properties'  => [
                'total_value' => [
                    'description' => __('Total value of the Cost of Goods Sold for the refund.', 'woocommerce'),
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
     * @param WC_Order_Refund $refund Refund instance.
     * @param WP_REST_Request $request Request object.
     * @param array           $include_fields Fields to include in the response.
     */
    public function get_item_response($refund, WP_REST_Request $request, array $include_fields = []): array
    {
        $dp   = is_null($request['num_decimals']) ? wc_get_price_decimals() : absint($request['num_decimals']);
        $data = [
            'id'               => $refund->get_id(),
            'order_id'         => $refund->get_parent_id(),
            'currency'         => $refund->get_currency(),
            'currency_symbol'  => html_entity_decode(get_woocommerce_currency_symbol($refund->get_currency()), ENT_QUOTES),
            'date_created'     => wc_rest_prepare_date_response($refund->get_date_created(), false),
            'date_created_gmt' => wc_rest_prepare_date_response($refund->get_date_created()),
            'amount'           => wc_format_decimal($refund->get_amount(), $dp),
            'reason'           => $refund->get_reason(),
            'refunded_payment' => $refund->get_refunded_payment(),
        ];

        if (in_array('refunded_by', $include_fields, true)) {
            $refunded_user = new \WP_User($refund->get_refunded_by());
            if ($refunded_user->exists()) {
                $data['refunded_by'] = [
                    'id'           => $refunded_user->ID,
                    'display_name' => $refunded_user->display_name,
                    'avatar_url'   => get_avatar_url($refunded_user),
                ];
            } else {
                $data['refunded_by'] = null;
            }
        }

        if (in_array('line_items', $include_fields, true)) {
            $data['line_items'] = array_merge(
                $this->get_line_items_response($refund->get_items('line_item'), $request),
                $this->get_line_items_response($refund->get_items('fee'), $request),
                $this->get_line_items_response($refund->get_items('shipping'), $request),
            );
        }

        if (in_array('meta_data', $include_fields, true)) {
            $filtered_meta_data = $this->filter_internal_meta_keys($refund->get_meta_data());
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
            $data['cost_of_goods_sold']['total_value'] = $refund->get_cogs_total_value();
        }

        return array_intersect_key($data, array_flip($include_fields));
    }

    /**
     * Standardize the line items response.
     *
     * @param array           $line_items Line items.
     * @param WP_REST_Request $request Request object.
     */
    protected function get_line_items_response($line_items, WP_REST_Request $request): array
    {
        $line_items_response = [];
        foreach ($line_items as $line_item) {
            $line_items_response[] = $this->prepare_line_item($line_item, $request);
        }
        return $line_items_response;
    }

    /**
     * Standardize the line item response.
     *
     * @param WC_Order_Item   $line_item Line item instance.
     * @param WP_REST_Request $request Request object.
     */
    protected function prepare_line_item($line_item, WP_REST_Request $request): array
    {
        $dp           = is_null($request['num_decimals']) ? wc_get_price_decimals() : absint($request['num_decimals']);
        $tax_response = [];
        $taxes        = $line_item->get_taxes();
        foreach ($taxes['total'] ?? [] as $tax_rate_id => $tax) {
            $tax_response[] = [
                'id'           => absint($tax_rate_id),
                'refund_total' => wc_format_decimal(abs((float) $tax), $dp),
            ];
        }
        return [
            'id'           => absint($line_item->get_id()),
            'line_item_id' => absint($line_item->get_meta('_refunded_item_id')),
            'quantity'     => wc_stock_amount(abs((float) $line_item->get_quantity())),
            'refund_total' => wc_format_decimal(abs((float) $line_item->get_total()), $dp),
            'refund_tax'   => $tax_response,
        ];
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
