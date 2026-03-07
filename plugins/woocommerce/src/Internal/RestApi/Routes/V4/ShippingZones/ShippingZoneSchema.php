<?php

/**
 * ShippingZoneSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\ShippingZones;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WC_Shipping_Zone;
use WP_REST_Request;

/**
 * ShippingZoneSchema class.
 */
class ShippingZoneSchema extends AbstractSchema
{
    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'shipping_zone';

    /**
     * Return all properties for the item schema.
     */
    public function get_item_schema_properties(): array
    {
        return [
            'id'        => [
                'description' => __('Unique identifier for the shipping zone.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'name'      => [
                'description'       => __('Shipping zone name.', 'woocommerce'),
                'type'              => 'string',
                'context'           => [ 'view', 'edit' ],
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'order'     => [
                'description' => __('Shipping zone order.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'default'     => 0,
            ],
            'locations' => [
                'description' => __('Array of locations for this zone. Can be empty array but must be explicitly provided.', 'woocommerce'),
                'type'        => 'array',
                'context'     => [ 'view', 'edit' ],
                'required'    => true,
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'code' => [
                            'description' => __('Shipping zone location code.', 'woocommerce'),
                            'type'        => 'string',
                        ],
                        'type' => [
                            'description' => __('Shipping zone location type.', 'woocommerce'),
                            'type'        => 'string',
                            'default'     => 'country',
                        ],
                        'name' => [
                            'description' => __('Shipping zone location name (readonly, auto-generated from code).', 'woocommerce'),
                            'type'        => 'string',
                            'readonly'    => true,
                        ],
                    ],
                ],
            ],
            'methods'   => [
                'description' => __('Shipping methods for this zone.', 'woocommerce'),
                'type'        => 'array',
                'readonly'    => true,
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'instance_id' => [
                            'description' => __('Shipping method instance ID.', 'woocommerce'),
                            'type'        => 'integer',
                        ],
                        'title'       => [
                            'description' => __('Shipping method title.', 'woocommerce'),
                            'type'        => 'string',
                        ],
                        'enabled'     => [
                            'description' => __('Whether the shipping method is enabled.', 'woocommerce'),
                            'type'        => 'boolean',
                        ],
                        'method_id'   => [
                            'description' => __('Shipping method ID (e.g., flat_rate, free_shipping).', 'woocommerce'),
                            'type'        => 'string',
                        ],
                        'settings'    => [
                            'description' => __('Raw shipping method settings for frontend processing.', 'woocommerce'),
                            'type'        => 'object',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the item response.
     *
     * @param WC_Shipping_Zone $zone WordPress representation of the zone.
     * @param WP_REST_Request  $request Request object.
     * @param array            $include_fields Fields to include in the response.
     * @return array The item response.
     */
    public function get_item_response($zone, WP_REST_Request $request, array $include_fields = []): array
    {
        return [
            'id'        => $zone->get_id(),
            'name'      => $zone->get_zone_name(),
            'order'     => $zone->get_zone_order(),
            'locations' => $this->get_formatted_zone_locations($zone),
            'methods'   => $this->get_formatted_zone_methods($zone),
        ];
    }

    /**
     * Get array of location objects for API response.
     *
     * @param WC_Shipping_Zone $zone Shipping zone object.
     * @return array Array of location objects with code, type, and name.
     */
    protected function get_formatted_zone_locations(WC_Shipping_Zone $zone): array
    {
        if (0 === $zone->get_id()) {
            return [];
        }

        $locations           = $zone->get_zone_locations();
        $formatted_locations = [];

        foreach ($locations as $location) {
            $formatted_locations[] = [
                'code' => $location->code ?? '',
                'type' => $location->type ?? 'country',
                'name' => $this->get_location_name($location),
            ];
        }

        return $formatted_locations;
    }

    /**
     * Get formatted methods for a zone.
     *
     * @param WC_Shipping_Zone $zone Shipping zone object.
     */
    protected function get_formatted_zone_methods($zone): array
    {
        $methods           = $zone->get_shipping_methods(false, 'json');
        $formatted_methods = [];

        foreach ($methods as $method) {
            $formatted_method = [
                'instance_id' => $method->instance_id,
                'title'       => $method->title,
                'enabled'     => 'yes' === $method->enabled,
                'method_id'   => $method->id,
                'settings'    => $this->get_method_settings($method),
            ];

            $formatted_methods[] = $formatted_method;
        }

        return $formatted_methods;
    }

    /**
     * Get raw method settings for frontend processing.
     *
     * @param object $method Shipping method object.
     */
    protected function get_method_settings($method): array
    {
        $settings = [];

        // Common settings that most methods have.
        $common_fields = [ 'cost', 'min_amount', 'requires', 'class_cost', 'no_class_cost' ];

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

    /**
     * Get location name from location object.
     *
     * @param object $location Location object.
     * @return string
     */
    protected function get_location_name($location)
    {
        switch ($location->type) {
            case 'continent':
                $continents = WC()->countries->get_continents();
                return isset($continents[ $location->code ]) ? $continents[ $location->code ]['name'] : $location->code;

            case 'country':
                $countries = WC()->countries->get_countries();
                return $countries[ $location->code ] ?? $location->code;

            case 'state':
            case 'country:state':
                $parts = explode(':', (string) $location->code);
                if (count($parts) === 2) {
                    $states = WC()->countries->get_states($parts[0]);
                    return $states[ $parts[1] ] ?? $location->code;
                }
                return $location->code;

            case 'postcode':

            default:
                return $location->code;
        }
    }
}
