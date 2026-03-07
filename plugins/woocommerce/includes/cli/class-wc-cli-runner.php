<?php

declare(strict_types=1);
/**
 * WP_CLI_Runner class file.
 *
 * @package WooCommerce\CLI
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * WC API to WC CLI Bridge.
 *
 * Hooks into the REST API, figures out which endpoints come from WC,
 * and registers them as CLI commands.
 *
 * Forked from wp-cli/restful (by Daniel Bachhuber, released under the MIT license https://opensource.org/licenses/MIT).
 * https://github.com/wp-cli/restful
 *
 * @version 3.0.0
 * @package WooCommerce
 */
class WC_CLI_Runner
{
    /**
     * Endpoints to disable (meaning they will not be available as CLI commands).
     * Some of these can either be done via WP already, or are offered with
     * some other changes (like tools).
     */
    private static array $disabled_endpoints = [
        'settings',
        'settings/(?P<group_id>[\w-]+)',
        'settings/(?P<group_id>[\w-]+)/batch',
        'settings/(?P<group_id>[\w-]+)/(?P<id>[\w-]+)',
        'system_status',
        'system_status/tools',
        'system_status/tools/(?P<id>[\w-]+)',
        'reports',
        'reports/sales',
        'reports/top_sellers',
    ];

    /**
     * The version of the REST API we should target to
     * generate commands.
     */
    private static string $target_rest_version = 'v2';

    /**
     * Register's all endpoints as commands once WP and WC have all loaded.
     */
    public static function after_wp_load(): void
    {
        global $wp_rest_server;
        $wp_rest_server = new WP_REST_Server();
        do_action('rest_api_init', $wp_rest_server);

        $request = new WP_REST_Request('GET', '/');
        $request->set_param('context', 'help');
        $response      = $wp_rest_server->dispatch($request);
        $response_data = $response->get_data();
        if (empty($response_data)) {
            return;
        }

        // Loop through all of our endpoints and register any valid WC endpoints.
        foreach ($response_data['routes'] as $route => $route_data) {
            // Only register endpoints for WC and our target version.
            if (substr((string) $route, 0, 4 + strlen(self::$target_rest_version)) !== '/wc/' . self::$target_rest_version) {
                continue;
            }

            // Only register endpoints with schemas.
            if (empty($route_data['schema']['title'])) {
                /* translators: %s: Route to a given WC-API endpoint */
                WP_CLI::debug(sprintf(__('No schema title found for %s, skipping REST command registration.', 'woocommerce'), $route), 'wc');
                continue;
            }
            // Ignore batch endpoints.
            if ('batch' === $route_data['schema']['title']) {
                continue;
            }
            // Disable specific endpoints.
            $route_pieces   = explode('/', (string) $route);
            $endpoint_piece = str_replace('/wc/' . $route_pieces[2] . '/', '', $route);
            if (in_array($endpoint_piece, self::$disabled_endpoints, true)) {
                continue;
            }

            self::register_route_commands(new WC_CLI_REST_Command($route_data['schema']['title'], $route, $route_data['schema']), $route, $route_data);
        }
    }

    /**
     * Generates command information and tells WP CLI about all
     * commands available from a route.
     *
     * @param WC_CLI_REST_Command $rest_command WC-API command.
     * @param string              $route Path to route endpoint.
     * @param array               $route_data Command data.
     * @param array               $command_args WP-CLI command arguments.
     */
    private static function register_route_commands(\WC_CLI_REST_Command $rest_command, $route, array $route_data, array $command_args = []): void
    {
        // Define IDs that we are looking for in the routes (in addition to id)
        // so that we can pass it to the rest command, and use it here to generate documentation.
        $supported_ids = [
            'product_id'   => __('Product ID.', 'woocommerce'),
            'customer_id'  => __('Customer ID.', 'woocommerce'),
            'order_id'     => __('Order ID.', 'woocommerce'),
            'refund_id'    => __('Refund ID.', 'woocommerce'),
            'attribute_id' => __('Attribute ID.', 'woocommerce'),
            'zone_id'      => __('Zone ID.', 'woocommerce'),
            'instance_id'  => __('Instance ID.', 'woocommerce'),
            'id'           => __('The ID for the resource.', 'woocommerce'),
            'slug'         => __('The slug for the resource.', 'woocommerce'),
        ];
        $rest_command->set_supported_ids($supported_ids);
        $positional_args = array_keys($supported_ids);
        $parent             = "wc {$route_data['schema']['title']}";
        $supported_commands = [];

        // Get a list of supported commands for each route.
        foreach ($route_data['endpoints'] as $endpoint) {
            preg_match_all('#\([^\)]+\)#', $route, $matches);
            $resource_id   = ! empty($matches[0]) ? array_pop($matches[0]) : null;
            $trimmed_route = rtrim($route);
            $is_singular   = substr($trimmed_route, - strlen($resource_id ?? '')) === $resource_id;

            // List a collection.
            if ([ 'GET' ] === $endpoint['methods'] && ! $is_singular) {
                $supported_commands['list'] = ! empty($endpoint['args']) ? $endpoint['args'] : [];
            }
            // Create a specific resource.
            if ([ 'POST' ] === $endpoint['methods'] && ! $is_singular) {
                $supported_commands['create'] = ! empty($endpoint['args']) ? $endpoint['args'] : [];
            }
            // Get a specific resource.
            if ([ 'GET' ] === $endpoint['methods'] && $is_singular) {
                $supported_commands['get'] = ! empty($endpoint['args']) ? $endpoint['args'] : [];
            }
            // Update a specific resource.
            if (in_array('POST', $endpoint['methods'], true) && $is_singular) {
                $supported_commands['update'] = ! empty($endpoint['args']) ? $endpoint['args'] : [];
            }
            // Delete a specific resource.
            if ([ 'DELETE' ] === $endpoint['methods'] && $is_singular) {
                $supported_commands['delete'] = ! empty($endpoint['args']) ? $endpoint['args'] : [];
            }
        }

        foreach ($supported_commands as $command => $endpoint_args) {
            $synopsis = [];
            $arg_regs = [];
            $ids      = [];

            foreach ($supported_ids as $id_name => $id_desc) {
                if (str_contains($route, '<' . $id_name . '>')) {
                    $synopsis[] = [
                        'name'        => $id_name,
                        'type'        => 'positional',
                        'description' => $id_desc,
                        'optional'    => false,
                    ];
                    $ids[]      = $id_name;
                }
            }

            foreach ($endpoint_args as $name => $args) {
                if (! in_array($name, $positional_args, true) || !str_contains($route, '<' . $id_name . '>')) {
                    $arg_regs[] = [
                        'name'        => $name,
                        'type'        => 'assoc',
                        'description' => ! empty($args['description']) ? $args['description'] : '',
                        'optional'    => empty($args['required']),
                    ];
                }
            }

            foreach ($arg_regs as $arg_reg) {
                $synopsis[] = $arg_reg;
            }

            if (in_array($command, [ 'list', 'get' ], true)) {
                $synopsis[] = [
                    'name'        => 'fields',
                    'type'        => 'assoc',
                    'description' => __('Limit response to specific fields. Defaults to all fields.', 'woocommerce'),
                    'optional'    => true,
                ];
                $synopsis[] = [
                    'name'        => 'field',
                    'type'        => 'assoc',
                    'description' => __('Get the value of an individual field.', 'woocommerce'),
                    'optional'    => true,
                ];
                $synopsis[] = [
                    'name'        => 'format',
                    'type'        => 'assoc',
                    'description' => __('Render response in a particular format.', 'woocommerce'),
                    'optional'    => true,
                    'default'     => 'table',
                    'options'     => [
                        'table',
                        'json',
                        'csv',
                        'ids',
                        'yaml',
                        'count',
                        'headers',
                        'body',
                        'envelope',
                    ],
                ];
            }

            if (in_array($command, [ 'create', 'update', 'delete' ], true)) {
                $synopsis[] = [
                    'name'        => 'porcelain',
                    'type'        => 'flag',
                    'description' => __('Output just the id when the operation is successful.', 'woocommerce'),
                    'optional'    => true,
                ];
            }

            $methods = [
                'list'   => 'list_items',
                'create' => 'create_item',
                'delete' => 'delete_item',
                'get'    => 'get_item',
                'update' => 'update_item',
            ];

            $before_invoke = null;
            if (empty($command_args['when']) && \WP_CLI::get_config('debug')) {
                $before_invoke = function (): void {
                    wc_maybe_define_constant('SAVEQUERIES', true);
                };
            }

            WP_CLI::add_command(
                "{$parent} {$command}",
                [ $rest_command, $methods[ $command ] ],
                [
                    'synopsis'      => $synopsis,
                    'when'          => ! empty($command_args['when']) ? $command_args['when'] : '',
                    'before_invoke' => $before_invoke,
                ]
            );
        }
    }
}
