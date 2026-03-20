<?php

/**
 * AbstractPaymentGatewaySettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\PaymentGateways\Schema;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WC_Payment_Gateway;
use WP_Error;
use WP_REST_Request;

/**
 * AbstractPaymentGatewaySettingsSchema class.
 *
 * Base class for payment gateway settings schemas in the REST API.
 *
 * The `settings` property is an object where keys are arbitrary setting IDs
 * and values are setting configuration objects with the following structure:
 *
 * - id (string, readonly): A unique identifier for the setting
 * - label (string, readonly): A human readable label for the setting used in interfaces
 * - description (string, readonly): A human readable description for the setting used in interfaces
 * - type (string, readonly): Type of setting (text, email, number, color, password, textarea, select, multiselect, radio, image_width, checkbox)
 * - value (string): Setting value
 * - default (string, readonly): Default value for the setting
 * - tip (string, readonly): Additional help text shown to the user about the setting
 * - placeholder (string, readonly): Placeholder text to be displayed in text inputs
 * - options (object, optional): Available options for select/multiselect type settings
 */
abstract class AbstractPaymentGatewaySettingsSchema extends AbstractSchema
{
    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'payment_gateway_settings';

    /**
     * Return all properties for the item schema.
     *
     * Note that context determines under which context data should be visible. For example, edit would be the context
     * used when getting records with the intent of editing them. embed context allows the data to be visible when the
     * item is being embedded in another response.
     */
    public function get_item_schema_properties(): array
    {
        return [
            'id'                 => [
                'description' => __('Payment gateway ID.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'title'              => [
                'description' => __('Payment gateway title on checkout.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
            ],
            'description'        => [
                'description' => __('Payment gateway description on checkout.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
            ],
            'order'              => [
                'description' => __('Payment gateway sort order.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'arg_options' => [
                    'sanitize_callback' => 'absint',
                ],
            ],
            'enabled'            => [
                'description' => __('Payment gateway enabled status.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => self::VIEW_EDIT_CONTEXT,
            ],
            'method_title'       => [
                'description' => __('Payment gateway method title.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'method_description' => [
                'description' => __('Payment gateway method description.', 'woocommerce'),
                'type'        => 'string',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
            ],
            'method_supports'    => [
                'description' => __('Supported features for this payment gateway.', 'woocommerce'),
                'type'        => 'array',
                'context'     => self::VIEW_EDIT_CONTEXT,
                'readonly'    => true,
                'items'       => [
                    'type' => 'string',
                ],
            ],
            'values'             => [
                'description'          => __('Flat key-value mapping of all setting field values.', 'woocommerce'),
                'type'                 => 'object',
                'context'              => [ 'view', 'edit' ],
                'additionalProperties' => [
                    'description' => __('Setting field value.', 'woocommerce'),
                    'type'        => [ 'string', 'number', 'array', 'boolean' ],
                ],
            ],
            'groups'             => [
                'description'          => __('Collection of setting groups.', 'woocommerce'),
                'type'                 => 'object',
                'context'              => [ 'view', 'edit' ],
                'additionalProperties' => [
                    'type'        => 'object',
                    'description' => __('Settings group.', 'woocommerce'),
                    'properties'  => [
                        'title'       => [
                            'description' => __('Group title.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'description' => [
                            'description' => __('Group description.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'order'       => [
                            'description' => __('Display order for the group.', 'woocommerce'),
                            'type'        => 'integer',
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'fields'      => [
                            'description' => __('Settings fields.', 'woocommerce'),
                            'type'        => 'array',
                            'context'     => [ 'view', 'edit' ],
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
                    'context'     => [ 'view', 'edit' ],
                ],
                'label'   => [
                    'description' => __('Setting field label.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
                'type'    => [
                    'description' => __('Setting field type.', 'woocommerce'),
                    'type'        => 'string',
                    'enum'        => [ 'text', 'number', 'select', 'multiselect', 'checkbox' ],
                    'context'     => [ 'view', 'edit' ],
                ],
                'options' => [
                    'description' => __('Available options for select/multiselect fields.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                ],
                'desc'    => [
                    'description' => __('Description for the setting field.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                ],
            ],
        ];
    }

    /**
     * Get flat key-value mapping of all setting values.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     */
    private function get_values(WC_Payment_Gateway $gateway): array
    {
        $values = [];
        $gateway->init_form_fields();

        foreach ($gateway->form_fields as $id => $field) {
            $field_type = $field['type'] ?? '';

            // Skip non-data fields.
            if (in_array($field_type, [ 'title', 'sectionend' ], true)) {
                continue;
            }

            // Get value from gateway settings.
            $values[ $id ] = $gateway->settings[ $id ] ?? ($field['default'] ?? '');
        }

        // Add special fields for this gateway.
        $special_fields = $this->get_special_field_values($gateway);

        return array_merge($values, $special_fields);
    }

    /**
     * Get values for gateway-specific special fields.
     *
     * Override this method in gateway-specific schema classes to provide special field values.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     */
    protected function get_special_field_values(WC_Payment_Gateway $gateway): array
    {
        return [];
    }

    /**
     * Get grouped settings structure with field metadata.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     */
    private function get_groups(WC_Payment_Gateway $gateway): array
    {
        // Check if gateway has custom grouping.
        $custom_groups = $this->get_custom_groups_for_gateway($gateway);
        if (! empty($custom_groups)) {
            return $custom_groups;
        }

        // Default: single group with all fields.
        return $this->get_default_group($gateway);
    }

    /**
     * Get custom groups for specific gateways.
     *
     * Override this method in gateway-specific schema classes to provide custom groupings.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     */
    protected function get_custom_groups_for_gateway(WC_Payment_Gateway $gateway): array
    {
        return [];
    }

    /**
     * Get default single group with all gateway fields.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     */
    private function get_default_group(WC_Payment_Gateway $gateway): array
    {
        $gateway->init_form_fields();

        $group = [
            'title'       => __('Settings', 'woocommerce'),
            'description' => '',
            'order'       => 1,
            'fields'      => [],
        ];

        // Add standard top-level fields first.
        $group['fields'][] = [
            'id'    => 'enabled',
            'label' => __('Enable/Disable', 'woocommerce'),
            'type'  => 'checkbox',
            'desc'  => __('Enable this payment gateway', 'woocommerce'),
        ];

        $group['fields'][] = [
            'id'    => 'title',
            'label' => __('Title', 'woocommerce'),
            'type'  => 'text',
            'desc'  => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        ];

        $group['fields'][] = [
            'id'    => 'description',
            'label' => __('Description', 'woocommerce'),
            'type'  => 'text',
            'desc'  => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        ];

        $group['fields'][] = [
            'id'    => 'order',
            'label' => __('Order', 'woocommerce'),
            'type'  => 'number',
            'desc'  => __('Determines the display order of payment gateways during checkout.', 'woocommerce'),
        ];

        foreach ($gateway->form_fields as $id => $field) {
            $field_type = $field['type'] ?? '';
            // Skip non-data fields, top-level fields (already added above), and special fields.
            if (in_array($field_type, [ 'title', 'sectionend' ], true)) {
                continue;
            }
            if (in_array($id, [ 'enabled', 'description', 'title' ], true)) {
                continue;
            }
            if ($this->is_special_field($id)) {
                continue;
            }

            $group['fields'][] = $this->transform_field_to_schema($id, $field);
        }

        // Add special fields.
        $special_fields  = $this->get_special_field_schemas($gateway);
        $group['fields'] = array_merge($group['fields'], $special_fields);

        if (empty($group['fields'])) {
            return [];
        }

        return [ 'settings' => $group ];
    }

    /**
     * Get field schemas for gateway-specific special fields.
     *
     * Override this method in gateway-specific schema classes to provide special field schemas.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     */
    protected function get_special_field_schemas(WC_Payment_Gateway $gateway): array
    {
        return [];
    }

    /**
     * Transform WooCommerce field definition to API field schema.
     *
     * @param string             $id      Field ID.
     * @param array              $field   Field definition.
     */
    private function transform_field_to_schema(string $id, array $field): array
    {
        $field_type = $field['type'] ?? 'text';

        $schema_field = [
            'id'    => $id,
            'label' => $field['title'] ?? $field['label'] ?? '',
            'type'  => $this->normalize_field_type($field_type),
            'desc'  => $field['description'] ?? '',
        ];

        // For checkbox fields, use the 'label' field as description if no explicit description exists.
        if ('checkbox' === $field_type && empty($schema_field['desc']) && ! empty($field['label'])) {
            $schema_field['desc'] = $field['label'];
        }

        // Add options for select/multiselect fields.
        if (in_array($schema_field['type'], [ 'select', 'multiselect' ], true)) {
            if (! empty($field['options'])) {
                $schema_field['options'] = $field['options'];
            } else {
                // Generate options dynamically for specific fields.
                $schema_field['options'] = $this->get_field_options($id);
            }
        }

        return $schema_field;
    }

    /**
     * Get options for specific gateway fields.
     *
     * Override this method in gateway-specific schema classes to provide
     * dynamic options for select/multiselect fields.
     *
     * @param string $field_id Field ID.
     * @return array Field options.
     */
    protected function get_field_options(string $field_id): array
    {
        return [];
    }

    /**
     * Normalize WooCommerce field types to standard REST API types.
     *
     * @param string $wc_type WooCommerce field type.
     */
    private function normalize_field_type(string $wc_type): string
    {
        $type_map = [
            'email'       => 'text',
            'password'    => 'text',
            'textarea'    => 'text',
            'safe_text'   => 'text',
            'color'       => 'text',
            'image_width' => 'text',
            'radio'       => 'select',
        ];

        return $type_map[ $wc_type ] ?? $wc_type;
    }

    /**
     * Return settings associated with this payment gateway.
     *
     * Note: Some gateways may conditionally populate the 'options' array for select/multiselect fields
     * based on context (e.g., only when accessing settings pages) for performance reasons.
     * For example, the COD gateway's `enable_for_methods` field loads shipping method options only
     * when `is_accessing_settings()` returns true. This means the options array may be empty when
     * accessed via the REST API, even though the field type is multiselect.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     */
    public function get_settings(WC_Payment_Gateway $gateway): array
    {
        $settings = [];
        $gateway->init_form_fields();
        foreach ($gateway->form_fields as $id => $field) {
            // Make sure we at least have a title and type.
            if (empty($field['title'])) {
                continue;
            }
            if (empty($field['type'])) {
                continue;
            }
            // Ignore 'enabled' and 'description' which get included elsewhere.
            if (in_array($id, [ 'enabled', 'description' ], true)) {
                continue;
            }

            $data = [
                'id'          => $id,
                'label'       => empty($field['label']) ? $field['title'] : $field['label'],
                'description' => empty($field['description']) ? '' : $field['description'],
                'type'        => $field['type'],
                'value'       => empty($gateway->settings[ $id ]) ? '' : $gateway->settings[ $id ],
                'default'     => empty($field['default']) ? '' : $field['default'],
                'tip'         => empty($field['description']) ? '' : $field['description'],
                'placeholder' => empty($field['placeholder']) ? '' : $field['placeholder'],
            ];
            if (! empty($field['options'])) {
                $data['options'] = $field['options'];
            }
            $settings[ $id ] = $data;
        }
        return $settings;
    }

    /**
     * Get the item response.
     *
     * @param WC_Payment_Gateway $gateway Payment gateway object.
     * @param WP_REST_Request    $request Request object.
     * @param array              $include_fields Fields to include in the response.
     * @return array The item response.
     */
    public function get_item_response($gateway, WP_REST_Request $request, array $include_fields = []): array
    {
        $order = (array) get_option('woocommerce_gateway_order');
        return [
            'id'                 => $gateway->id,
            'title'              => $gateway->title,
            'description'        => $gateway->description,
            'order'              => $order[ $gateway->id ] ?? '',
            'enabled'            => ('yes' === $gateway->enabled),
            'method_title'       => $gateway->get_method_title(),
            'method_description' => $gateway->get_method_description(),
            'method_supports'    => $gateway->supports,
            'values'             => $this->get_values($gateway),
            'groups'             => $this->get_groups($gateway),
        ];
    }

    /**
     * Check if a field is a special field.
     *
     * Override this method in gateway-specific schema classes to identify special fields.
     *
     * @param string $field_id Field ID.
     */
    public function is_special_field(string $field_id): bool
    {
        return false;
    }

    /**
     * Validate and sanitize standard gateway settings.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     * @param array              $values  Values to validate and sanitize.
     * @return array|WP_Error Validated settings or error.
     */
    public function validate_and_sanitize_settings(WC_Payment_Gateway $gateway, array $values)
    {
        $gateway->init_form_fields();
        $validated = [];

        foreach ($values as $key => $value) {
            // Security: only allow valid form fields.
            if (! isset($gateway->form_fields[ $key ])) {
                continue;
            }

            $field      = $gateway->form_fields[ $key ];
            $field_type = $field['type'] ?? 'text';

            // Sanitize by type.
            $sanitized = $this->sanitize_field_value($field_type, $value);

            // Validate.
            $validation = $this->validate_field_value($key, $sanitized, $field);
            if (is_wp_error($validation)) {
                return $validation;
            }

            $validated[ $key ] = $sanitized;
        }

        return $validated;
    }

    /**
     * Sanitize field value based on type.
     *
     * @param string $type  Field type.
     * @param mixed  $value Field value.
     * @return mixed Sanitized value.
     */
    private function sanitize_field_value(string $type, $value)
    {
        switch ($type) {
            case 'checkbox':
                return wc_bool_to_string($value);

            case 'number':
                if (! is_numeric($value)) {
                    return '';
                }
                $int_value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
                return $int_value ?? floatval($value);

            case 'multiselect':
                if (is_array($value)) {
                    return array_map(sanitize_text_field(...), $value);
                }
                return is_string($value) ? [ sanitize_text_field($value) ] : [];

            case 'textarea':
                return sanitize_textarea_field($value);

            case 'email':
                return sanitize_email($value);

            case 'password':
            case 'color':

            case 'text':
            case 'safe_text':
            case 'select':
            case 'radio':
            case 'image_width':
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Validate field value.
     *
     * @param string             $key     Field key.
     * @param mixed              $value   Sanitized value.
     * @param array              $field   Field definition.
     * @return true|WP_Error True if valid, WP_Error otherwise.
     */
    private function validate_field_value(string $key, $value, array $field): \WP_Error|true
    {
        $field_type = $this->normalize_field_type($field['type'] ?? 'text');

        // Validate select/radio options.
        if (in_array($field_type, [ 'select', 'radio' ], true) && ! empty($field['options'])) {
            if (! array_key_exists($value, $field['options']) && '' !== $value) {
                return new WP_Error(
                    'rest_invalid_param',
                    sprintf(
                        /* translators: 1: field key, 2: valid options */
                        __('Invalid value for %1$s. Valid options: %2$s', 'woocommerce'),
                        $key,
                        implode(', ', array_keys($field['options']))
                    ),
                    [ 'status' => 400 ]
                );
            }
        }

        // Validate multiselect options.
        if ('multiselect' === $field_type && ! empty($field['options'])) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (! array_key_exists($v, $field['options'])) {
                        return new WP_Error(
                            'rest_invalid_param',
                            sprintf(
                                /* translators: 1: field key, 2: invalid value */
                                __('Invalid option "%2$s" for %1$s.', 'woocommerce'),
                                $key,
                                $v
                            ),
                            [ 'status' => 400 ]
                        );
                    }
                }
            }
        }

        // Add more validations as needed.

        return true;
    }

    /**
     * Validate and sanitize special fields.
     *
     * Override this method in gateway-specific schema classes to provide custom validation.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     * @param array              $values  Special field values.
     * @return array|WP_Error Validated values or error.
     */
    public function validate_and_sanitize_special_fields(WC_Payment_Gateway $gateway, array $values)
    {
        return [];
    }

    /**
     * Update special fields in database.
     *
     * Override this method in gateway-specific schema classes to provide custom update logic.
     *
     * @param WC_Payment_Gateway $gateway Gateway instance.
     * @param array              $values  Validated special field values.
     */
    public function update_special_fields(WC_Payment_Gateway $gateway, array $values): void
    {
        // Base implementation does nothing.
    }
}
