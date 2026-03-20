<?php

/**
 * TaxSettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Tax\Schema;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WP_REST_Request;

defined('ABSPATH') || exit;

/**
 * TaxSettingsSchema class.
 */
class TaxSettingsSchema extends AbstractSchema
{
    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'tax_settings';

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
                'description' => __('Settings title.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'description' => [
                'description' => __('Settings description.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'values'      => [
                'description'          => __('Flat key-value mapping of all setting field values.', 'woocommerce'),
                'type'                 => 'object',
                'context'              => self::VIEW_EDIT_CONTEXT,
                'additionalProperties' => [
                    'description' => __('Setting field value.', 'woocommerce'),
                    'type'        => [ 'string', 'number', 'array', 'boolean' ],
                ],
            ],
            'groups'      => [
                'description'          => __('Collection of setting groups.', 'woocommerce'),
                'type'                 => 'object',
                'context'              => self::VIEW_EDIT_CONTEXT,
                'additionalProperties' => [
                    'type'        => 'object',
                    'description' => __('Settings group.', 'woocommerce'),
                    'properties'  => [
                        'title'       => [
                            'description' => __('Group title.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => self::VIEW_EDIT_CONTEXT,
                        ],
                        'description' => [
                            'description' => __('Group description.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => self::VIEW_EDIT_CONTEXT,
                        ],
                        'order'       => [
                            'description' => __('Display order for the group.', 'woocommerce'),
                            'type'        => 'integer',
                            'context'     => self::VIEW_EDIT_CONTEXT,
                            'readonly'    => true,
                        ],
                        'fields'      => [
                            'description' => __('Settings fields.', 'woocommerce'),
                            'type'        => 'array',
                            'context'     => self::VIEW_EDIT_CONTEXT,
                            'items'       => $this->get_field_schema(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the schema for individual setting fields.
     */
    private function get_field_schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'      => [
                    'description' => __('Setting field ID.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => self::VIEW_EDIT_CONTEXT,
                ],
                'label'   => [
                    'description' => __('Setting field label.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => self::VIEW_EDIT_CONTEXT,
                ],
                'type'    => [
                    'description' => __('Setting field type.', 'woocommerce'),
                    'type'        => 'string',
                    'enum'        => [ 'text', 'number', 'select', 'multiselect', 'checkbox', 'radio', 'textarea' ],
                    'context'     => self::VIEW_EDIT_CONTEXT,
                ],
                'options' => [
                    'description' => __('Available options for select/radio fields.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => self::VIEW_EDIT_CONTEXT,
                ],
                'desc'    => [
                    'description' => __('Description for the setting field.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => self::VIEW_EDIT_CONTEXT,
                ],
            ],
        ];
    }

    /**
     * Get tax settings data by transforming raw settings into REST API format.
     *
     * @param mixed           $item             Raw settings array.
     * @param WP_REST_Request $request          Request object.
     * @param array           $include_fields   Fields to include.
     */
    public function get_item_response($item, WP_REST_Request $request, array $include_fields = []): array
    {
        $raw_settings = $item;

        // Transform raw settings into grouped format based on title/sectionend markers.
        $groups           = [];
        $values           = [];
        $current_group    = null;
        $current_group_id = null;

        foreach ($raw_settings as $setting) {
            $setting_type = $setting['type'] ?? '';

            // Handle section titles - start of a new group.
            if ('title' === $setting_type) {
                $current_group_id = $setting['id'] ?? '';
                $current_group    = [
                    'title'       => $setting['title'] ?? '',
                    'description' => $setting['desc'] ?? '',
                    'order'       => isset($setting['order']) ? (int) $setting['order'] : 999,
                    'fields'      => [],
                ];
                continue;
            }

            // Handle section ends - save the current group.
            if ('sectionend' === $setting_type) {
                if ($current_group && $current_group_id) {
                    $groups[ $current_group_id ] = $current_group;
                }
                $current_group    = null;
                $current_group_id = null;
                continue;
            }

            // Skip special marker types.
            if (in_array($setting_type, [ 'title', 'sectionend', 'conflict_error', 'add_settings_slot' ], true)) {
                continue;
            }

            // Convert setting to field format.
            if (isset($setting['id']) && $current_group) {
                $field = $this->transform_setting_to_field($setting);
                if ($field) {
                    $current_group['fields'][] = $field;
                    // Add field value to the flat values array.
                    $raw_value              = get_option($field['id'], $setting['default'] ?? '');
                    $values[ $field['id'] ] = $this->validate_field_value($raw_value, $field['type']);
                }
            }
        }

        // Sort groups by their order if available.
        uasort(
            $groups,
            function (array $a, array $b): int {
                $a_order = $a['order'] ?? 999;
                $b_order = $b['order'] ?? 999;
                return $a_order - $b_order;
            }
        );

        $response = [
            'id'          => 'tax',
            'title'       => __('Taxes', 'woocommerce'),
            'description' => __('Manage your store’s tax setup.', 'woocommerce'),
            'values'      => $values,
            'groups'      => $groups,
        ];

        if (! empty($include_fields)) {
            return array_intersect_key($response, array_flip($include_fields));
        }

        return $response;
    }

    /**
     * Transform a WooCommerce setting into REST API field format.
     *
     * @param array $setting WooCommerce setting array.
     * @return mixed[] Transformed field or null if should be skipped.
     */
    private function transform_setting_to_field(array $setting): array
    {
        $setting_id   = $setting['id'] ?? '';
        $setting_type = $setting['type'] ?? 'text';

        $field = [
            'id'    => $setting_id,
            'label' => $setting['title'] ?? $setting_id,
            'type'  => $this->normalize_field_type($setting_type),
            'desc'  => $setting['desc'] ?? '',
        ];

        // Add options for select/radio fields.
        if (isset($setting['options']) && is_array($setting['options'])) {
            $field['options'] = $setting['options'];
        }

        return $field;
    }

    /**
     * Normalize WooCommerce field types to REST API field types.
     *
     * @param string $wc_type WooCommerce field type.
     * @return string Normalized field type.
     */
    private function normalize_field_type(string $wc_type): string
    {
        $type_map = [
            'single_select_country'  => 'select',
            'multi_select_countries' => 'multiselect',
            'radio'                  => 'radio',
        ];

        return $type_map[ $wc_type ] ?? $wc_type;
    }

    /**
     * Validate and sanitize field value based on its type.
     *
     * @param mixed  $value Field value.
     * @param string $type  Field type.
     * @return mixed Validated value.
     */
    private function validate_field_value($value, string $type): float|int|bool|array|string
    {
        switch ($type) {
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'checkbox':
                if (function_exists('wc_string_to_bool')) {
                    return wc_string_to_bool($value);
                }
                if (is_bool($value)) {
                    return $value;
                }
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'multiselect':
                return is_array($value) ? $value : [];
            case 'radio':
            case 'select':
            case 'text':
            case 'textarea':
            default:
                return is_string($value) ? $value : (string) $value;
        }
    }
}
