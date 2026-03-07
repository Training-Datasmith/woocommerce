<?php

/**
 * Shipping Method Schema.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZoneMethod;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

/**
 * Shipping Method Schema class.
 */
class ShippingMethodSchema extends AbstractSchema
{
    /**
     * The schema identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'shipping_method';

    /**
     * Return all properties for the item schema
     */
    public function get_item_schema_properties(): array
    {
        return [
            'instance_id' => [
                'description' => __('Shipping method instance ID.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'zone_id'     => [
                'description' => __('Shipping zone ID.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'required'    => true,
            ],
            'enabled'     => [
                'description' => __('Whether the shipping method is enabled.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => [ 'view', 'edit' ],
                'required'    => true,
            ],
            'order'       => [
                'description'       => __('Shipping method sort order.', 'woocommerce'),
                'type'              => 'integer',
                'context'           => [ 'view', 'edit' ],
                'sanitize_callback' => 'absint',
            ],
            'method_id'   => [
                'description' => __('Shipping method ID.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit' ],
                'required'    => true,
            ],
            'settings'    => [
                'description'          => __('Shipping method settings including title and configuration.', 'woocommerce'),
                'type'                 => 'object',
                'context'              => [ 'view', 'edit' ],
                'required'             => true,
                'properties'           => [
                    'title' => [
                        'description' => __('Shipping method title.', 'woocommerce'),
                        'type'        => 'string',
                        'context'     => [ 'view', 'edit' ],
                        'required'    => true,
                    ],
                ],
                'additionalProperties' => true,
            ],
        ];
    }

    /**
     * Get the item response for a shipping method.
     *
     * @param object          $method Shipping method instance.
     * @param WP_REST_Request $request Request object.
     * @param array           $include_fields Fields to include in the response.
     * @return array The item response.
     */
    public function get_item_response($method, WP_REST_Request $request, array $include_fields = []): array
    {
        if (isset($request['zone_id'])) {
            $zone_id = (int) $request['zone_id'];
        } else {
            $data_store = \WC_Data_Store::load('shipping-zone');
            $zone_id    = $data_store->get_zone_id_by_instance_id($method->instance_id);
        }

        return [
            'instance_id' => (int) $method->instance_id,
            'zone_id'     => (int) $zone_id,
            'enabled'     => wc_string_to_bool($method->enabled),
            'order'       => (int) $method->method_order,
            'method_id'   => $method->id,
            'settings'    => $this->get_method_settings($method),
        ];
    }

    /**
     * Get shipping method settings with title included.
     *
     * @param object $method Shipping method instance.
     * @return array Method settings including title.
     */
    protected function get_method_settings($method): array
    {
        $settings = [];

        // Get the method title (moved from root to settings per Ismael's feedback).
        $settings['title'] = $method->get_title();

        // Get common method settings.
        $common_fields = [ 'cost', 'min_amount', 'requires', 'class_cost', 'no_class_cost', 'tax_status' ];

        foreach ($common_fields as $field) {
            if (isset($method->$field)) {
                $settings[ $field ] = $method->$field;
            }
        }

        // Return all available settings for maximum flexibility.
        if (isset($method->instance_settings) && is_array($method->instance_settings)) {
            return array_merge($settings, $method->instance_settings);
        }

        return $settings;
    }
}
