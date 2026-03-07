<?php

declare(strict_types=1);
/**
 * REST API Onboarding Profile Controller
 *
 * Handles requests to /onboarding/profile
 */

namespace Automattic\WooCommerce\Admin\API;

defined('ABSPATH') || exit;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;
use WC_REST_Data_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Onboarding Plugins controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class OnboardingPlugins extends WC_REST_Data_Controller
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
    protected $rest_base = 'onboarding/plugins';

    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/install-and-activate-async',
            [
                [
                    'methods'             => 'POST',
                    'callback'            => $this->install_and_activate_async(...),
                    'permission_callback' => $this->can_install_and_activate_plugins(...),
                    'args'                => [
                        'plugins' => [
                            'description'       => 'A list of plugins to install',
                            'type'              => 'array',
                            'items'             => 'string',
                            'sanitize_callback' => fn ($value) => array_map(
                                fn ($value) => sanitize_text_field($value),
                                $value
                            ),
                            'required'          => true,
                        ],
                        'source'  => [
                            'description'       => 'The source of the request',
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                            'required'          => false,
                        ],
                    ],
                ],
                'schema' => $this->get_install_async_schema(...),
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/install-and-activate',
            [
                [
                    'methods'             => 'POST',
                    'callback'            => $this->install_and_activate(...),
                    'permission_callback' => $this->can_install_and_activate_plugins(...),

                ],
                'schema' => $this->get_install_activate_schema(...),
            ]
        );
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/scheduled-installs/(?P<job_id>\w+)',
            [
                [
                    'methods'             => 'GET',
                    'callback'            => $this->get_scheduled_installs(...),
                    'permission_callback' => $this->can_install_plugins(...),
                ],
                'schema' => $this->get_install_async_schema(...),
            ]
        );

        // This is an experimental endpoint and is subject to change in the future.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/jetpack-authorization-url',
            [
                [
                    'methods'             => 'GET',
                    'callback'            => $this->get_jetpack_authorization_url(...),
                    'permission_callback' => $this->can_install_plugins(...),
                    'args'                => [
                        'redirect_url' => [
                            'description'       => 'The URL to redirect to after authorization',
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                            'required'          => true,
                        ],
                        'from'         => [
                            'description'       => 'from value for the jetpack authorization page',
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                            'required'          => false,
                            'default'           => 'woocommerce-onboarding',
                        ],
                    ],
                ],
            ]
        );
        add_action('woocommerce_plugins_install_error', $this->log_plugins_install_error(...), 10, 4);
        add_action('woocommerce_plugins_install_api_error', $this->log_plugins_install_api_error(...), 10, 2);
    }

    /**
     * Install and activate a plugin.
     *
     * @param WP_REST_Request $request WP Request object.
     *
     * @return WP_REST_Response
     */
    public function install_and_activate(WP_REST_Request $request)
    {
        $response             = [];
        $response['install']  = PluginsHelper::install_plugins($request->get_param('plugins'));
        $response['activate'] = PluginsHelper::activate_plugins($response['install']['installed']);

        return new WP_REST_Response($response);
    }

    /**
     * Queue plugin install request.
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return array
     */
    public function install_and_activate_async(WP_REST_Request $request)
    {
        $plugins = $request->get_param('plugins');
        $source  = $request->get_param('source');
        $job_id  = uniqid();

        WC()->queue()->add('woocommerce_plugins_install_and_activate_async_callback', [ $plugins, $job_id, $source ]);

        $plugin_status = [];
        foreach ($plugins as $plugin) {
            $plugin_status[ $plugin ] = [
                'status' => 'pending',
                'errors' => [],
            ];
        }

        return [
            'job_id'  => $job_id,
            'status'  => 'pending',
            'plugins' => $plugin_status,
        ];
    }

    /**
     * Returns current status of given job.
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return array|WP_REST_Response
     */
    public function get_scheduled_installs(WP_REST_Request $request)
    {
        $job_id = $request->get_param('job_id');

        $actions = WC()->queue()->search(
            [
                'hook'    => 'woocommerce_plugins_install_and_activate_async_callback',
                'search'  => $job_id,
                'orderby' => 'date',
                'order'   => 'DESC',
            ]
        );

        $actions = array_filter(
            PluginsHelper::get_action_data($actions),
            fn (array $action) => $action['job_id'] === $job_id
        );

        if (empty($actions)) {
            return new WP_REST_Response(null, 404);
        }

        $response = [
            'job_id' => $actions[0]['job_id'],
            'status' => $actions[0]['status'],
        ];

        $option = get_option('woocommerce_onboarding_plugins_install_and_activate_async_' . $job_id);
        if (isset($option['plugins'])) {
            $response['plugins'] = $option['plugins'];
        }

        return $response;
    }

    /**
     * Return Jetpack authorization URL.
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * @return array
     */
    public function get_jetpack_authorization_url(WP_REST_Request $request)
    {
        return JetpackConnection::get_authorization_url(
            $request->get_param('redirect_url'),
            $request->get_param('from')
        );
    }

    /**
     * Check whether the current user has permission to install plugins
     *
     * @return WP_Error|boolean
     */
    public function can_install_plugins()
    {
        if (! current_user_can('install_plugins')) {
            return new WP_Error(
                'woocommerce_rest_cannot_update',
                __('Sorry, you cannot manage plugins.', 'woocommerce'),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Check whether the current user has permission to install and activate plugins
     *
     * @return WP_Error|boolean
     */
    public function can_install_and_activate_plugins()
    {
        if (! current_user_can('install_plugins') || ! current_user_can('activate_plugins')) {
            return new WP_Error(
                'woocommerce_rest_cannot_update',
                __('Sorry, you cannot manage plugins.', 'woocommerce'),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * JSON Schema for both install-async and scheduled-installs endpoints.
     *
     * @return array
     */
    public function get_install_async_schema()
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'Install Async Schema',
            'type'       => 'object',
            'properties' => [
                'type'       => 'object',
                'properties' => [
                    'job_id' => 'integer',
                    'status' => [
                        'type' => 'string',
                        'enum' => [ 'pending', 'complete', 'failed' ],
                    ],
                ],
            ],
        ];
    }

    /**
     * JSON Schema for install-and-activate endpoint.
     *
     * @return array
     */
    public function get_install_activate_schema()
    {
        $error_schema = [
            'type'              => 'object',
            'patternProperties' => [
                '^.*$' => [
                    'type' => 'string',
                ],
            ],
            'items'             => [
                'type' => 'string',
            ],
        ];

        $install_schema = [
            'type'       => 'object',
            'properties' => [
                'installed' => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'results'   => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'errors'    => [
                    'type'       => 'object',
                    'properties' => [
                        'errors'     => $error_schema,
                        'error_data' => $error_schema,
                    ],
                ],
            ],
        ];

        $activate_schema = [
            'type'       => 'object',
            'properties' => [
                'activated' => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'active'    => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ],
                'errors'    => [
                    'type'       => 'object',
                    'properties' => [
                        'errors'     => $error_schema,
                        'error_data' => $error_schema,
                    ],
                ],
            ],
        ];

        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'Install and Activate Schema',
            'type'       => 'object',
            'properties' => [
                'type'       => 'object',
                'properties' => [
                    'install'  => $install_schema,
                    'activate' => $activate_schema,
                ],
            ],
        ];
    }

    public function log_plugins_install_error($slug, $api, $result, $upgrader): void
    {
        $properties = [
            'error_message'         => sprintf(
                /* translators: %s: plugin slug (example: woocommerce-services) */
                __(
                    'The requested plugin `%s` could not be installed.',
                    'woocommerce'
                ),
                $slug
            ),
            'type'                  => 'plugin_info_api_error',
            'slug'                  => $slug,
            'api_version'           => $api->version,
            'api_download_link'     => $api->download_link,
            'upgrader_skin_message' => implode(',', $upgrader->skin->get_upgrade_messages()),
            'result'                => is_wp_error($result) ? $result->get_error_message() : 'null',
        ];
        wc_admin_record_tracks_event('coreprofiler_install_plugin_error', $properties);
    }

    public function log_plugins_install_api_error($slug, $api): void
    {
        $properties = [
            'error_message'     => sprintf(
                // translators: %s: plugin slug (example: woocommerce-services).
                __(
                    'The requested plugin `%s` could not be installed. Plugin API call failed.',
                    'woocommerce'
                ),
                $slug
            ),
            'type'              => 'plugin_install_error',
            'api_error_message' => $api->get_error_message(),
            'slug'              => $slug,
        ];
        wc_admin_record_tracks_event('coreprofiler_install_plugin_error', $properties);
    }
}
