<?php

declare(strict_types=1);
/**
 * REST API Setting Options controller
 *
 * Handles requests to the /settings/$group/$setting endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

defined('ABSPATH') || exit;

/**
 * REST API Setting Options controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Setting_Options_V2_Controller
 */
class WC_REST_Setting_Options_Controller extends WC_REST_Setting_Options_V2_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v3';

    /**
     * Get setting data.
     *
     * @param string $group_id Group ID.
     * @param string $setting_id Setting ID.
     * @return stdClass|WP_Error
     */
    public function get_setting($group_id, $setting_id)
    {
        $setting = parent::get_setting($group_id, $setting_id);
        if (is_wp_error($setting)) {
            return $setting;
        }
        $setting['group_id'] = $group_id;
        return $setting;
    }

    /**
     * Callback for allowed keys for each setting response.
     *
     * @param  string $key Key to check.
     */
    public function allowed_setting_keys($key): bool
    {
        return in_array(
            $key,
            [
                'id',
                'group_id',
                'label',
                'description',
                'default',
                'tip',
                'placeholder',
                'type',
                'options',
                'value',
                'option_key',
            ],
            true
        );
    }

    /**
     * Get all settings in a group.
     *
     * @param string $group_id Group ID.
     */
    public function get_group_settings($group_id): \WP_Error|array
    {
        if (empty($group_id)) {
            return new WP_Error('rest_setting_setting_group_invalid', __('Invalid setting group.', 'woocommerce'), [ 'status' => 404 ]);
        }

        $settings = apply_filters('woocommerce_settings-' . $group_id, []); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

        if (empty($settings)) {
            return new WP_Error('rest_setting_setting_group_invalid', __('Invalid setting group.', 'woocommerce'), [ 'status' => 404 ]);
        }

        $this->prime_options_cache_for_settings($settings);

        $filtered_settings = [];
        foreach ($settings as $setting) {
            $option_key = $setting['option_key'];
            $setting    = $this->filter_setting($setting);
            $default    = $setting['default'] ?? '';

            if (in_array($setting['type'] ?? '', [ 'title', 'sectionend' ], true)) {
                $filtered_settings[] = $setting;
                continue;
            }

            // Get the option value.
            if (is_array($option_key)) {
                $option           = get_option($option_key[0]);
                $setting['value'] = $option[ $option_key[1] ] ?? $default;
            } else {
                $admin_setting_value = WC_Admin_Settings::get_option($option_key, $default);
                $setting['value']    = $admin_setting_value;
            }

            if ('multi_select_countries' === $setting['type']) {
                $setting['options'] = WC()->countries->get_countries();
                $setting['type']    = 'multiselect';
            } elseif ('single_select_country' === $setting['type']) {
                $setting['type']    = 'select';
                $setting['options'] = $this->get_countries_and_states();
            } elseif ($setting['type'] === 'single_select_page' || $setting['type'] === 'single_select_page_with_search') {
                $pages   = get_pages(
                    [
                        'sort_column'  => 'menu_order',
                        'sort_order'   => 'ASC',
                        'hierarchical' => 0,
                    ]
                );
                $options = [];
                foreach ($pages as $page) {
                    $options[ $page->ID ] = ! empty($page->post_title) ? $page->post_title : '#' . $page->ID;
                }
                $setting['type']    = 'select';
                $setting['options'] = $options;
            }

            $filtered_settings[] = $setting;
        }

        return $filtered_settings;
    }

    /**
     * Returns a list of countries and states for use in the base location setting.
     *
     * @since  3.0.7
     * @return array Array of states and countries.
     */
    private function get_countries_and_states(): array
    {
        $countries = WC()->countries->get_countries();
        if (! $countries) {
            return [];
        }
        $output = [];
        foreach ($countries as $key => $value) {
            $states = WC()->countries->get_states($key);

            if ($states) {
                foreach ($states as $state_key => $state_value) {
                    $output[ $key . ':' . $state_key ] = $value . ' - ' . $state_value;
                }
            } else {
                $output[ $key ] = $value;
            }
        }
        return $output;
    }

    /**
     * Get the settings schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'setting',
            'type'       => 'object',
            'properties' => [
                'id'          => [
                    'description' => __('A unique identifier for the setting.', 'woocommerce'),
                    'type'        => 'string',
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'group_id'    => [
                    'description' => __('An identifier for the group this setting belongs to.', 'woocommerce'),
                    'type'        => 'string',
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_title',
                    ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'label'       => [
                    'description' => __('A human readable label for the setting used in interfaces.', 'woocommerce'),
                    'type'        => 'string',
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'description' => [
                    'description' => __('A human readable description for the setting used in interfaces.', 'woocommerce'),
                    'type'        => 'string',
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'value'       => [
                    'description' => __('Setting value.', 'woocommerce'),
                    'type'        => 'mixed',
                    'context'     => [ 'view', 'edit' ],
                ],
                'default'     => [
                    'description' => __('Default value for the setting.', 'woocommerce'),
                    'type'        => 'mixed',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'tip'         => [
                    'description' => __('Additional help text shown to the user about the setting.', 'woocommerce'),
                    'type'        => 'string',
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'placeholder' => [
                    'description' => __('Placeholder text to be displayed in text inputs.', 'woocommerce'),
                    'type'        => 'string',
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'type'        => [
                    'description' => __('Type of setting.', 'woocommerce'),
                    'type'        => 'string',
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'context'     => [ 'view', 'edit' ],
                    'enum'        => [ 'text', 'email', 'number', 'color', 'password', 'textarea', 'select', 'multiselect', 'radio', 'image_width', 'checkbox' ],
                    'readonly'    => true,
                ],
                'options'     => [
                    'description' => __('Array of options (key value pairs) for inputs such as select, multiselect, and radio buttons.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }
}
