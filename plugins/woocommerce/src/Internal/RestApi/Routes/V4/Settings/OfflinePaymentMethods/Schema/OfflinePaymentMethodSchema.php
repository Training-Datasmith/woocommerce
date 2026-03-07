<?php

/**
 * OfflinePaymentMethodSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\OfflinePaymentMethods\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

defined('ABSPATH') || exit;

/**
 * OfflinePaymentMethodSchema class.
 */
class OfflinePaymentMethodSchema extends AbstractSchema
{
    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'offline_payment_method';

    /**
     * Return all properties for the item schema.
     */
    public function get_item_schema_properties(): array
    {
        return [
            'id'          => [
                'description' => __('Unique identifier for the settings group.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'title'       => [
                'description' => __('Title of the settings group.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'description' => [
                'description' => __('Description of the settings group.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'values'      => [
                'description'          => __('Current enabled state for all payment methods.', 'woocommerce'),
                'type'                 => 'object',
                'context'              => self::VIEW_EDIT_CONTEXT,
                'readonly'             => true,
                'additionalProperties' => [
                    'type' => 'boolean',
                ],
            ],
            'groups'      => [
                'description' => __('Grouped settings for offline payment methods.', 'woocommerce'),
                'type'        => 'object',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
                'properties'  => [
                    'payment_methods' => [
                        'description'          => __('Available offline payment methods.', 'woocommerce'),
                        'type'                 => 'object',
                        'context'              => self::VIEW_EDIT_CONTEXT,
                        'readonly'             => true,
                        'additionalProperties' => [
                            'type'       => 'object',
                            'properties' => [
                                'id'          => [
                                    'description' => __('Unique identifier for the payment method.', 'woocommerce'),
                                    'type'        => 'string',
                                    'context'     => self::VIEW_EDIT_CONTEXT,
                                ],
                                '_order'      => [
                                    'description' => __('Sort order for the payment method.', 'woocommerce'),
                                    'type'        => 'integer',
                                    'context'     => self::VIEW_EDIT_CONTEXT,
                                ],
                                'title'       => [
                                    'description' => __('Title of the payment method.', 'woocommerce'),
                                    'type'        => 'string',
                                    'context'     => self::VIEW_EDIT_CONTEXT,
                                ],
                                'description' => [
                                    'description' => __('Description of the payment method.', 'woocommerce'),
                                    'type'        => 'string',
                                    'context'     => self::VIEW_EDIT_CONTEXT,
                                ],
                                'icon'        => [
                                    'description' => __('Icon URL for the payment method.', 'woocommerce'),
                                    'type'        => 'string',
                                    'format'      => 'uri',
                                    'context'     => self::VIEW_EDIT_CONTEXT,
                                ],
                                'state'       => [
                                    'description'          => __('Current state configuration of the payment method.', 'woocommerce'),
                                    'type'                 => 'object',
                                    'context'              => self::VIEW_EDIT_CONTEXT,
                                    'additionalProperties' => [
                                        'type' => 'boolean',
                                    ],
                                ],
                                'management'  => [
                                    'description'          => __('Management options for the payment method.', 'woocommerce'),
                                    'type'                 => 'object',
                                    'context'              => self::VIEW_EDIT_CONTEXT,
                                    'properties'           => [
                                        '_links' => [
                                            'description' => __('Management links for the payment method.', 'woocommerce'),
                                            'type'        => 'object',
                                            'context'     => self::VIEW_EDIT_CONTEXT,
                                            'additionalProperties' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'href' => [
                                                        'description' => __('URL for the management link.', 'woocommerce'),
                                                        'type'        => 'string',
                                                        'format'      => 'uri',
                                                        'context'     => self::VIEW_EDIT_CONTEXT,
                                                    ],
                                                ],
                                                'additionalProperties' => false,
                                            ],
                                        ],
                                    ],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the item response.
     *
     * @param mixed           $item Payment method data array.
     * @param WP_REST_Request $request Request object.
     * @param array           $include_fields Fields to include in the response.
     * @return array The item response.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) $request is unused; filtering handled by REST server.
     */
    public function get_item_response($item, WP_REST_Request $request, array $include_fields = []): array
    {
        $response = (array) $item;

        if (! empty($include_fields)) {
            return array_intersect_key($response, array_flip($include_fields));
        }

        return $response;
    }
}
