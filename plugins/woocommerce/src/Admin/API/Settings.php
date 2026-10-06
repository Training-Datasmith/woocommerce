<?php

/**
 * REST API Settings Controller
 *
 * Handles requests to save Settings.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\Settings\Init;
use WC_Admin_Settings;

defined('ABSPATH') || exit;

/**
 * Settings Controller.
 *
 * @extends WC_REST_Data_Controller
 */
class Settings extends \WC_REST_Data_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-admin';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'legacy-settings';

    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => $this->save_settings(...),
                    'permission_callback' => $this->save_items_permissions_check(...),
                    'args'                => [
                        'tab'     => [
                            'description' => __('Settings tab.', 'woocommerce'),
                            'type'        => 'string',
                            'default'     => 'general',
                        ],
                        'section' => [
                            'description' => __('Settings section.', 'woocommerce'),
                            'type'        => 'string',
                            'default'     => '',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Check if a given request has access to update settings.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function save_items_permissions_check($request)
    {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Save settings.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function save_settings($request)
    {
        global $current_section, $current_tab;

        // Verify nonce.
        if (! check_ajax_referer('wp_rest', false, false)) {
            return new \WP_Error(
                'woocommerce_settings_invalid_nonce',
                __('Invalid nonce.', 'woocommerce'),
                [ 'status' => 403 ]
            );
        }

        $params = $request->get_params();

        try {
            // Get current tab/section and set global variables.
            $current_tab     = empty($params['tab']) ? 'general' : sanitize_title(wp_unslash($params['tab'])); // WPCS: input var okay, CSRF ok.
            $current_section = empty($params['section']) ? '' : sanitize_title(wp_unslash($params['section'])); // WPCS: input var okay, CSRF ok.

            $filter_name = '' === $current_section ?
            "woocommerce_save_settings_{$current_tab}" :
            "woocommerce_save_settings_{$current_tab}_{$current_section}";

            /**
             * Filters whether to save settings.
             *
             * @since 3.7.0
             *
             * @param bool $save Whether to save settings.
             */
            if (apply_filters($filter_name, ! empty($_POST['save']))) { // WPCS: input var okay, CSRF ok.
                WC_Admin_Settings::save();
            }

            $setting_pages = \WC_Admin_Settings::get_settings_pages();

            // Reinitialize all setting pages in case behavior is dependent on saved values.
            foreach ($setting_pages as $key => $setting_page) {
                $class_name            = $setting_page::class;
                $setting_pages[ $key ] = new $class_name();
            }

            $data = Init::get_page_data([], $setting_pages);

            return new \WP_REST_Response(
                [
                    'status' => 'success',
                    'data'   => $data,
                ]
            );
        } catch (\Exception $e) {
            return new \WP_Error(
                'woocommerce_settings_save_error',
                // translators: %s: error message.
                sprintf(__('Failed to save settings: %s', 'woocommerce'), $e->getMessage()),
                [ 'status' => 500 ]
            );
        }
    }

    /**
     * Get the schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function save_items_schema()
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'options',
            'type'       => 'object',
            'properties' => [
                'options' => [
                    'type'        => 'array',
                    'description' => __('Array of options with associated values.', 'woocommerce'),
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'tab'     => [
                    'type'        => 'string',
                    'description' => __('Settings tab.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'default'     => 'general',
                ],
                'section' => [
                    'type'        => 'string',
                    'description' => __('Settings section.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'default'     => '',
                ],
            ],
        ];
    }
}
