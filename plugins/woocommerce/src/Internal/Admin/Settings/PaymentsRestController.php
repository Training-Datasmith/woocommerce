<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for the REST endpoints to service the Payments settings page.
 *
 * @internal
 */
class PaymentsRestController extends RestApiControllerBase
{
    /**
     * The root namespace for the JSON REST API endpoints.
     */
    protected string $route_namespace = 'wc-admin';

    /**
     * Route base.
     */
    protected string $rest_base = 'settings/payments';

    /**
     * The payments settings page service.
     */
    private Payments $payments;

    /**
     * Get the WooCommerce REST API namespace for the class.
     */
    protected function get_rest_api_namespace(): string
    {
        return 'wc-admin-settings-payments';
    }

    /**
     * Register the REST API endpoints handled by this controller.
     *
     * @param bool $override Whether to override the existing routes. Useful for testing.
     */
    public function register_routes(bool $override = false): void
    {
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/country',
            [
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'set_country'),
                    'validation_callback' => 'rest_validate_request_arg',
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('The ISO3166 alpha-2 country code to save for the current user.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => true,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/providers',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'get_providers'),
                    'validation_callback' => 'rest_validate_request_arg',
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to WooCommerce\'s base location country.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                    ],
                ],
                'schema' => $this->get_schema_for_get_payment_providers(...),
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/providers/order',
            [
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'update_providers_order'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'order_map' => [
                            'description'       => esc_html__('A map of provider ID to integer values representing the sort order.', 'woocommerce'),
                            'type'              => 'object',
                            'required'          => true,
                            'validate_callback' => $this->check_providers_order_map_arg(...),
                            'sanitize_callback' => $this->sanitize_providers_order_arg(...),
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/suggestion/(?P<id>[\w\d\-]+)/attach',
            [
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'attach_payment_extension_suggestion'),
                    'permission_callback' => $this->check_permissions(...),
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/suggestion/(?P<id>[\w\d\-]+)/hide',
            [
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'hide_payment_extension_suggestion'),
                    'permission_callback' => $this->check_permissions(...),
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/suggestion/(?P<suggestion_id>[\w\d\-]+)/incentive/(?P<incentive_id>[\w\d\-]+)/dismiss',
            [
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'dismiss_payment_extension_suggestion_incentive'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'context'      => [
                            'description'       => esc_html__('The context ID for which to dismiss the incentive. If not provided, will dismiss the incentive for all contexts.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_key',
                        ],
                        'do_not_track' => [
                            'description'       => esc_html__('If true, the incentive dismissal will be ignored by tracking.', 'woocommerce'),
                            'type'              => 'boolean',
                            'required'          => false,
                            'default'           => false,
                            'sanitize_callback' => 'rest_sanitize_boolean',
                        ],
                    ],
                ],
            ],
            $override
        );
    }

    /**
     * Initialize the class instance.
     *
     * @param Payments $payments The payments settings page service.
     *
     * @internal
     */
    final public function init(Payments $payments): void
    {
        $this->payments = $payments;
    }

    /**
     * Get the payment providers for the given location.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_Error|WP_REST_Response
     */
    protected function get_providers(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        try {
            $providers = $this->payments->get_payment_providers($location);
        } catch (Exception $e) {
            return new WP_Error('woocommerce_rest_payment_providers_error', $e->getMessage(), [ 'status' => 500 ]);
        }

        try {
            $suggestions = $this->get_extension_suggestions($location);
        } catch (Exception $e) {
            return new WP_Error('woocommerce_rest_payment_providers_error', $e->getMessage(), [ 'status' => 500 ]);
        }

        // Separate the offline PMs from the main providers list.
        $offline_payment_providers = array_values(
            array_filter(
                $providers,
                fn (array $provider): bool => PaymentsProviders::TYPE_OFFLINE_PM === $provider['_type']
            )
        );
        $providers                 = array_values(
            array_filter(
                $providers,
                fn (array $provider): bool => PaymentsProviders::TYPE_OFFLINE_PM !== $provider['_type']
            )
        );

        $response = [
            'providers'               => $providers,
            'offline_payment_methods' => $offline_payment_providers,
            'suggestions'             => $suggestions,
            'suggestion_categories'   => $this->payments->get_payment_extension_suggestion_categories(),
        ];

        return rest_ensure_response($this->prepare_payment_providers_response($response));
    }

    /**
     * Set the country for the payment providers.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    protected function set_country(WP_REST_Request $request)
    {
        $location = $request->get_param('location');

        $result = $this->payments->set_country($location);

        return rest_ensure_response([ 'success' => $result ]);
    }

    /**
     * Update the payment providers order.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    protected function update_providers_order(WP_REST_Request $request)
    {
        $order_map = $request->get_param('order_map');

        $result = $this->payments->update_payment_providers_order_map($order_map);

        return rest_ensure_response([ 'success' => $result ]);
    }

    /**
     * Attach a payment extension suggestion.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    protected function attach_payment_extension_suggestion(WP_REST_Request $request)
    {
        $suggestion_id = $request->get_param('id');

        try {
            $result = $this->payments->attach_payment_extension_suggestion($suggestion_id);
        } catch (Exception $e) {
            return new WP_Error('woocommerce_rest_payment_extension_suggestion_error', $e->getMessage(), [ 'status' => 400 ]);
        }

        return rest_ensure_response([ 'success' => $result ]);
    }

    /**
     * Hide a payment extension suggestion.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    protected function hide_payment_extension_suggestion(WP_REST_Request $request)
    {
        $suggestion_id = $request->get_param('id');

        try {
            $result = $this->payments->hide_payment_extension_suggestion($suggestion_id);
        } catch (Exception $e) {
            return new WP_Error('woocommerce_rest_payment_extension_suggestion_error', $e->getMessage(), [ 'status' => 400 ]);
        }

        return rest_ensure_response([ 'success' => $result ]);
    }

    /**
     * Dismiss a payment extension suggestion incentive.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response
     */
    protected function dismiss_payment_extension_suggestion_incentive(WP_REST_Request $request)
    {
        $suggestion_id = $request->get_param('suggestion_id');
        $incentive_id  = $request->get_param('incentive_id');
        $context       = $request->get_param('context') ?? 'all';
        $do_not_track  = $request->get_param('do_not_track') ?? false;

        try {
            $result = $this->payments->dismiss_extension_suggestion_incentive($suggestion_id, $incentive_id, $context, $do_not_track);
        } catch (Exception $e) {
            return new WP_Error('woocommerce_rest_payment_extension_suggestion_incentive_error', $e->getMessage(), [ 'status' => 400 ]);
        }

        return rest_ensure_response([ 'success' => $result ]);
    }

    /**
     * Get the payment extension suggestions (other) for the given location.
     *
     * @param string $location The location for which the suggestions are being fetched.
     *
     * @return array[]   The payment extension suggestions for the given location,
     *                   excluding the ones part of the main providers list.
     * @throws Exception If there are malformed or invalid suggestions.
     */
    private function get_extension_suggestions(string $location): array
    {
        // If the requesting user can't install plugins, we don't suggest any extensions.
        if (! current_user_can('install_plugins')) {
            return [];
        }

        $suggestions = $this->payments->get_payment_extension_suggestions($location);

        return $suggestions['other'] ?? [];
    }

    /**
     * General permissions check for payments settings REST API endpoint.
     *
     * @param WP_REST_Request $request The request for which the permission is checked.
     * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
     */
    private function check_permissions(WP_REST_Request $request): bool|\WP_Error
    {
        $context = 'read';
        if ('POST' === $request->get_method()) {
            $context = 'edit';
        } elseif ('DELETE' === $request->get_method()) {
            $context = 'delete';
        }

        if (wc_rest_check_manager_permissions('payment_gateways', $context)) {
            return true;
        }

        $error_information = $this->get_authentication_error_by_method($request->get_method());
        if (is_null($error_information)) {
            return false;
        }

        return new WP_Error(
            $error_information['code'],
            $error_information['message'],
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Validate the location argument.
     *
     * @param mixed           $value   Value of the argument.
     * @param WP_REST_Request $request The current request object.
     *
     * @return WP_Error|true True if the location argument is valid, otherwise a WP_Error object.
     */
    private function check_location_arg($value, WP_REST_Request $request): \WP_Error|true
    {
        // If the 'location' argument is not a string return an error.
        if (! is_string($value)) {
            return new WP_Error('rest_invalid_param', esc_html__('The location argument must be a string.', 'woocommerce'), [ 'status' => 400 ]);
        }

        // Get the registered attributes for this endpoint request.
        $attributes = $request->get_attributes();

        // Grab the location param schema.
        $args = $attributes['args']['location'];

        // If the location param doesn't match the regex pattern then we should return an error as well.
        if (! preg_match('/^' . $args['pattern'] . '$/', $value)) {
            return new WP_Error('rest_invalid_param', esc_html__('The location argument must be a valid ISO3166 alpha-2 country code.', 'woocommerce'), [ 'status' => 400 ]);
        }

        return true;
    }

    /**
     * Validate the providers order map argument.
     *
     * @param mixed $value Value of the argument.
     *
     * @return WP_Error|true True if the providers order map argument is valid, otherwise a WP_Error object.
     */
    private function check_providers_order_map_arg($value): \WP_Error|true
    {
        if (! is_array($value)) {
            return new WP_Error('rest_invalid_param', esc_html__('The ordering argument must be an object.', 'woocommerce'), [ 'status' => 400 ]);
        }

        foreach ($value as $provider_id => $order) {
            if (! is_string($provider_id) || ! is_numeric($order)) {
                return new WP_Error('rest_invalid_param', esc_html__('The ordering argument must be an object with provider IDs as keys and numeric values as values.', 'woocommerce'), [ 'status' => 400 ]);
            }

            if ($this->sanitize_provider_id($provider_id) !== $provider_id) {
                return new WP_Error('rest_invalid_param', esc_html__('The provider ID must be a string with only ASCII letters, digits, underscores, and dashes.', 'woocommerce'), [ 'status' => 400 ]);
            }

            if (false === filter_var($order, FILTER_VALIDATE_INT)) {
                return new WP_Error('rest_invalid_param', esc_html__('The order value must be an integer.', 'woocommerce'), [ 'status' => 400 ]);
            }
        }

        return true;
    }

    /**
     * Sanitize the providers ordering argument.
     *
     * @param array $value Value of the argument.
     */
    private function sanitize_providers_order_arg(array $value): array
    {
        // Sanitize the ordering object to ensure that the order values are integers and the provider IDs are safe strings.
        foreach ($value as $provider_id => $order) {
            $id           = $this->sanitize_provider_id($provider_id);
            $value[ $id ] = intval($order);
        }

        return $value;
    }

    /**
     * Sanitize a provider ID.
     *
     * This method ensures that the provider ID is a safe string by removing any unwanted characters.
     * It strips all HTML tags, removes accents, percent-encoded characters, and HTML entities,
     * and allows only lowercase and uppercase letters, digits, underscores, and dashes.
     *
     * @param string $provider_id The provider ID to sanitize.
     *
     * @return string The sanitized provider ID.
     */
    private function sanitize_provider_id(string $provider_id): string
    {
        $provider_id = wp_strip_all_tags($provider_id);
        $provider_id = remove_accents($provider_id);
        // Remove percent-encoded characters.
        $provider_id = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $provider_id);
        // Remove HTML entities.
        $provider_id = preg_replace('/&.+?;/', '', $provider_id);

        // Only lowercase and uppercase ASCII letters, digits, underscores, and dashes are allowed.
        $provider_id = preg_replace('|[^a-z0-9_\-]|i', '', $provider_id);

        return $provider_id;
    }

    /**
     * Prepare the response for the GET payment providers request.
     *
     * @param array $response The response to prepare.
     *
     * @return array The prepared response.
     */
    private function prepare_payment_providers_response(array $response): array
    {
        $response = $this->prepare_payment_providers_response_recursive($response, $this->get_schema_for_get_payment_providers());

        $response['providers']   = $this->add_provider_links($response['providers']);
        $response['suggestions'] = $this->add_suggestion_links($response['suggestions']);

        return $response;
    }

    /**
     * Recursively prepare the response items for the GET payment providers request.
     *
     * @param mixed $response_item The response item to prepare.
     * @param array $schema        The schema to use for preparing the response.
     *
     * @return mixed The prepared response item.
     */
    private function prepare_payment_providers_response_recursive($response_item, array $schema)
    {
        if (is_null($response_item)) {
            return null;
        }

        if (! array_key_exists('properties', $schema) ||
            ! is_array($schema['properties'])) {

            // Filter out null values for loosely defined schema types.
            if (is_array($response_item)) {
                return ArrayUtil::filter_null_values_recursive($response_item);
            }
            return $response_item;
        }

        $prepared_response = [];
        foreach ($schema['properties'] as $key => $property_schema) {
            if (is_array($response_item) && array_key_exists($key, $response_item)) {
                if (is_array($property_schema) && array_key_exists('properties', $property_schema)) {
                    $prepared_response[ $key ] = $this->prepare_payment_providers_response_recursive($response_item[ $key ], $property_schema);
                } elseif (is_array($property_schema) && array_key_exists('items', $property_schema)) {
                    $prepared_response[ $key ] = array_map(
                        fn ($item): mixed => $this->prepare_payment_providers_response_recursive($item, $property_schema['items']),
                        $response_item[ $key ]
                    );
                } else {
                    $prepared_response[ $key ] = $response_item[ $key ];
                }
            }
        }

        // Ensure the order is the same as in the schema.
        $prepared_response = array_merge(array_fill_keys(array_keys($schema['properties']), null), $prepared_response);

        // Remove any null values from the response.
        return ArrayUtil::filter_null_values_recursive($prepared_response);
    }

    /**
     * Add links to providers list items.
     *
     * @param array $providers The providers list.
     *
     * @return array The providers list with added links.
     */
    private function add_provider_links(array $providers): array
    {
        foreach ($providers as $key => $provider) {
            if (empty($provider['_links'])) {
                $providers[ $key ]['_links'] = [];
            }

            // If this is a suggestion, add dedicated links.
            if (! empty($provider['_type']) &&
                PaymentsProviders::TYPE_SUGGESTION === $provider['_type'] &&
                ! empty($provider['_suggestion_id'])
            ) {
                $providers[ $key ]['_links']['attach'] = [
                    'href' => rest_url(sprintf('/%s/%s/suggestion/%s/attach', $this->route_namespace, $this->rest_base, $provider['_suggestion_id'])),
                ];
                $providers[ $key ]['_links']['hide']   = [
                    'href' => rest_url(sprintf('/%s/%s/suggestion/%s/hide', $this->route_namespace, $this->rest_base, $provider['_suggestion_id'])),
                ];
            }

            // If we have an incentive, add a link to dismiss it.
            if (! empty($provider['_incentive']) && ! empty($provider['_suggestion_id'])) {
                if (empty($provider['_incentive']['_links'])) {
                    $providers[ $key ]['_incentive']['_links'] = [];
                }

                $providers[ $key ]['_incentive']['_links']['dismiss'] = [
                    'href' => rest_url(sprintf('/%s/%s/suggestion/%s/incentive/%s/dismiss', $this->route_namespace, $this->rest_base, $provider['_suggestion_id'], $provider['_incentive']['id'])),
                ];
            }
        }

        return $providers;
    }

    /**
     * Add links to suggestions list items.
     *
     * @param array $suggestions The suggestions list.
     *
     * @return array The suggestions list with added links.
     */
    private function add_suggestion_links(array $suggestions): array
    {
        foreach ($suggestions as $key => $suggestion) {
            if (empty($suggestion['id'])) {
                continue;
            }

            if (empty($suggestion['_links'])) {
                $suggestions[ $key ]['_links'] = [];
            }

            $suggestions[ $key ]['_links']['attach'] = [
                'href' => rest_url(sprintf('/%s/%s/suggestion/%s/attach', $this->route_namespace, $this->rest_base, $suggestion['id'])),
            ];
            $suggestions[ $key ]['_links']['hide']   = [
                'href' => rest_url(sprintf('/%s/%s/suggestion/%s/hide', $this->route_namespace, $this->rest_base, $suggestion['id'])),
            ];
        }

        return $suggestions;
    }

    /**
     * Get the schema for the GET payment providers request.
     *
     * @return array[]
     */
    private function get_schema_for_get_payment_providers(): array
    {
        $schema               = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'WooCommerce Settings Payments providers for the given location.',
            'type'    => 'object',
        ];
        $schema['properties'] = [
            'providers'               => [
                'type'        => 'array',
                'description' => esc_html__('The ordered providers list. This includes registered payment gateways, suggestions, and offline payment methods group entry. The individual offline payment methods are separate.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'items'       => $this->get_schema_for_payment_provider(),
            ],
            'offline_payment_methods' => [
                'type'        => 'array',
                'description' => esc_html__('The ordered offline payment methods providers list.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'items'       => $this->get_schema_for_payment_provider(),
            ],
            'suggestions'             => [
                'type'        => 'array',
                'description' => esc_html__('The list of suggestions, excluding the ones part of the providers list.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'items'       => $this->get_schema_for_suggestion(),
            ],
            'suggestion_categories'   => [
                'type'        => 'array',
                'description' => esc_html__('The suggestion categories.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'items'       => [
                    'type'        => 'object',
                    'description' => esc_html__('A suggestion category.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'properties'  => [
                        'id'          => [
                            'type'        => 'string',
                            'description' => esc_html__('The unique identifier for the category.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        '_priority'   => [
                            'type'        => 'integer',
                            'description' => esc_html__('The priority of the category.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'title'       => [
                            'type'        => 'string',
                            'description' => esc_html__('The title of the category.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'description' => [
                            'type'        => 'string',
                            'description' => esc_html__('The description of the category.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],

                    ],
                ],
            ],
        ];

        return $schema;
    }

    /**
     * Get the schema for a payment provider.
     *
     * @return array The schema for a payment provider.
     */
    private function get_schema_for_payment_provider(): array
    {
        return [
            'type'        => 'object',
            'description' => esc_html__('A payment provider in the context of the main Payments Settings page list.', 'woocommerce'),
            'properties'  => [
                'id'             => [
                    'type'        => 'string',
                    'description' => esc_html__('The unique identifier for the provider.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                '_order'         => [
                    'type'        => 'integer',
                    'description' => esc_html__('The sort order of the provider.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                '_type'          => [
                    'type'        => 'string',
                    'description' => esc_html__('The type of payment provider. Use this to differentiate between the various items in the list and determine their intended use.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'title'          => [
                    'type'        => 'string',
                    'description' => esc_html__('The title of the provider.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'description'    => [
                    'type'        => 'string',
                    'description' => esc_html__('The description of the provider.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'supports'       => [
                    'description' => esc_html__('Supported features for this provider.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type' => 'string',
                    ],
                ],
                'plugin'         => [
                    'type'        => 'object',
                    'description' => esc_html__('The corresponding plugin details of the provider.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'properties'  => [
                        '_type'  => [
                            'type'        => 'string',
                            'enum'        => [
                                PaymentsProviders::EXTENSION_TYPE_WPORG,
                                PaymentsProviders::EXTENSION_TYPE_MU_PLUGIN,
                                PaymentsProviders::EXTENSION_TYPE_THEME,
                                PaymentsProviders::EXTENSION_TYPE_UNKNOWN,
                            ],
                            'description' => esc_html__('The type of the containing entity. Generally this is a regular plugin but it can also be a non-standard entity like a theme or a must-user plugin.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'slug'   => [
                            'type'        => 'string',
                            'description' => esc_html__('The slug of the containing entity.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'file'   => [
                            'type'        => 'string',
                            'description' => esc_html__('The plugin main file. This is a relative path to the plugins directory.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'status' => [
                            'type'        => 'string',
                            'enum'        => [
                                PaymentsProviders::EXTENSION_NOT_INSTALLED,
                                PaymentsProviders::EXTENSION_INSTALLED,
                                PaymentsProviders::EXTENSION_ACTIVE,
                            ],
                            'description' => esc_html__('The status of the containing entity.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                    ],
                ],
                'image'          => [
                    'type'        => 'string',
                    'description' => esc_html__('The URL of the provider image.', 'woocommerce'),
                    'readonly'    => true,
                ],
                'icon'           => [
                    'type'        => 'string',
                    'description' => esc_html__('The URL of the provider icon (square aspect ratio - 72px by 72px).', 'woocommerce'),
                    'readonly'    => true,
                ],
                'links'          => [
                    'type'        => 'array',
                    'description' => esc_html__('Links for the provider.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            '_type' => [
                                'type'        => 'string',
                                'description' => esc_html__('The type of the link.', 'woocommerce'),
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'url'   => [
                                'type'        => 'string',
                                'description' => esc_html__('The URL of the link.', 'woocommerce'),
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                ],
                'state'          => [
                    'type'        => 'object',
                    'description' => esc_html__('The general state of the provider with regards to it\'s payments processing.', 'woocommerce'),
                    'properties'  => [
                        'enabled'           => [
                            'type'        => 'boolean',
                            'description' => esc_html__('Whether the provider is enabled for use on checkout.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'account_connected' => [
                            'type'        => 'boolean',
                            'description' => esc_html__('Whether the provider has a payments processing account connected.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'needs_setup'       => [
                            'type'        => 'boolean',
                            'description' => esc_html__('Whether the provider needs setup.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'test_mode'         => [
                            'type'        => 'boolean',
                            'description' => esc_html__('Whether the provider is in test mode for payments processing.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'dev_mode'          => [
                            'type'        => 'boolean',
                            'description' => esc_html__('Whether the provider is in dev mode. Having this true usually leads to forcing test payments. ', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                    ],
                ],
                'management'     => [
                    'type'        => 'object',
                    'description' => esc_html__('The management details of the provider.', 'woocommerce'),
                    'properties'  => [
                        '_links' => [
                            'type'       => 'object',
                            'context'    => [ 'view', 'edit' ],
                            'readonly'   => true,
                            'properties' => [
                                'settings' => [
                                    'type'        => 'object',
                                    'description' => esc_html__('The link to the settings page for the payment gateway.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                    'properties'  => [
                                        'href' => [
                                            'type'        => 'string',
                                            'description' => esc_html__('The URL to the settings page for the payment gateway.', 'woocommerce'),
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'onboarding'     => [
                    'type'        => 'object',
                    'description' => esc_html__('Onboarding-related details for the provider.', 'woocommerce'),
                    'properties'  => [
                        'type'                        => [
                            'type'        => 'string',
                            'description' => esc_html__('The type of onboarding process the provider supports.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'state'                       => [
                            'type'        => 'object',
                            'description' => esc_html__('The state of the onboarding process.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'messages'                    => [
                            'type'                 => 'object',
                            'description'          => esc_html__('Various messages to possibly show the user.', 'woocommerce'),
                            'context'              => [ 'view', 'edit' ],
                            'readonly'             => true,
                            'additionalProperties' => [
                                'type'        => 'string',
                                'description' => esc_html__('Message to show the user.', 'woocommerce'),
                                'readonly'    => true,
                            ],
                        ],
                        'steps'                       => [
                            'type'        => 'array',
                            'description' => esc_html__('The onboarding steps in case this provider supports native in-context onboarding.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        '_links'                      => [
                            'type'       => 'object',
                            'context'    => [ 'view', 'edit' ],
                            'readonly'   => true,
                            'properties' => [
                                'preload'              => [
                                    'type'        => 'object',
                                    'description' => esc_html__('The onboarding preload link for the payment gateway.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                    'properties'  => [
                                        'href' => [
                                            'type'        => 'string',
                                            'description' => esc_html__('The URL to do onboarding preload for the payment gateway.', 'woocommerce'),
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                                'onboard'              => [
                                    'type'        => 'object',
                                    'description' => esc_html__('The start/continue onboarding link for the payment gateway.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                    'properties'  => [
                                        'href' => [
                                            'type'        => 'string',
                                            'description' => esc_html__('The URL to start/continue onboarding for the payment gateway.', 'woocommerce'),
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                                'disable_test_account' => [
                                    'type'        => 'object',
                                    'description' => esc_html__('The link to disable the test account for the payment gateway.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                    'properties'  => [
                                        'href' => [
                                            'type'        => 'string',
                                            'description' => esc_html__('The URL to POST to disable the test account for the payment gateway.', 'woocommerce'),
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                                'reset'                => [
                                    'type'        => 'object',
                                    'description' => esc_html__('The link to reset the provider state/account and restart the onboarding.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                    'properties'  => [
                                        'href' => [
                                            'type'        => 'string',
                                            'description' => esc_html__('The URL to POST to for resetting the provider onboarding.', 'woocommerce'),
                                            'context'     => [ 'view', 'edit' ],
                                            'readonly'    => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'recommended_payment_methods' => [
                            'type'        => 'array',
                            'description' => esc_html__('The list of recommended payment methods details for the payment gateway.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'items'       => [
                                'type'        => 'object',
                                'description' => esc_html__('The details for a recommended payment method.', 'woocommerce'),
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'properties'  => [
                                    'id'          => [
                                        'type'        => 'string',
                                        'description' => esc_html__('The unique identifier for the payment method.', 'woocommerce'),
                                        'context'     => [ 'view', 'edit' ],
                                        'readonly'    => true,
                                    ],
                                    '_order'      => [
                                        'type'        => 'integer',
                                        'description' => esc_html__('The sort order of the payment method.', 'woocommerce'),
                                        'context'     => [ 'view', 'edit' ],
                                        'readonly'    => true,
                                    ],
                                    'enabled'     => [
                                        'type'        => 'boolean',
                                        'description' => esc_html__('Whether the payment method should be recommended as enabled or not.', 'woocommerce'),
                                        'context'     => [ 'view', 'edit' ],
                                        'readonly'    => true,
                                    ],
                                    'required'    => [
                                        'type'        => 'boolean',
                                        'description' => esc_html__('Whether the payment method should be required (and force-enabled) or not.', 'woocommerce'),
                                        'context'     => [ 'view', 'edit' ],
                                        'readonly'    => true,
                                    ],
                                    'title'       => [
                                        'type'        => 'string',
                                        'description' => esc_html__('The title of the payment method. Does not include HTML tags.', 'woocommerce'),
                                        'context'     => [ 'view', 'edit' ],
                                        'readonly'    => true,
                                    ],
                                    'description' => [
                                        'type'        => 'string',
                                        'description' => esc_html__('The description of the payment method. It can contain basic HTML.', 'woocommerce'),
                                        'context'     => [ 'view', 'edit' ],
                                        'readonly'    => true,
                                    ],
                                    'icon'        => [
                                        'type'        => 'string',
                                        'description' => esc_html__('The URL of the payment method icon or a base64-encoded SVG image.', 'woocommerce'),
                                        'context'     => [ 'view', 'edit' ],
                                        'readonly'    => true,
                                    ],
                                ],
                            ],
                        ],
                        'context'                     => [
                            'type'        => 'object',
                            'description' => esc_html__('Various contextual data for the onboarding process to use.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                    ],
                ],
                'tags'           => [
                    'type'        => 'array',
                    'description' => esc_html__('The tags associated with the provider.', 'woocommerce'),
                    'uniqueItems' => true,
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'        => 'string',
                        'description' => esc_html__('Tag associated with the provider.', 'woocommerce'),
                        'readonly'    => true,
                    ],
                ],
                '_suggestion_id' => [
                    'type'        => 'string',
                    'description' => esc_html__('The suggestion ID matching this provider.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                '_incentive'     => $this->get_schema_for_incentive(),
                '_links'         => [
                    'type'       => 'object',
                    'context'    => [ 'view', 'edit' ],
                    'readonly'   => true,
                    'properties' => [
                        'attach' => [
                            'type'        => 'object',
                            'description' => esc_html__('The link to mark the suggestion as attached. This should be called when an extension is installed.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'properties'  => [
                                'href' => [
                                    'type'        => 'string',
                                    'description' => esc_html__('The URL to attach the suggestion.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                        'hide'   => [
                            'type'        => 'object',
                            'description' => esc_html__('The link to hide the suggestion.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'properties'  => [
                                'href' => [
                                    'type'        => 'string',
                                    'description' => esc_html__('The URL to hide the suggestion.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the schema for a suggestion.
     *
     * @return array The schema for a suggestion.
     */
    private function get_schema_for_suggestion(): array
    {
        return [
            'type'        => 'object',
            'description' => esc_html__('A suggestion with full details.', 'woocommerce'),
            'context'     => [ 'view', 'edit' ],
            'readonly'    => true,
            'properties'  => [
                'id'          => [
                    'type'        => 'string',
                    'description' => esc_html__('The unique identifier for the suggestion.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                '_priority'   => [
                    'type'        => 'integer',
                    'description' => esc_html__('The priority of the suggestion.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                '_type'       => [
                    'type'        => 'string',
                    'description' => esc_html__('The type of the suggestion.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'title'       => [
                    'type'        => 'string',
                    'description' => esc_html__('The title of the suggestion.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'description' => [
                    'type'        => 'string',
                    'description' => esc_html__('The description of the suggestion.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'plugin'      => [
                    'type'       => 'object',
                    'context'    => [ 'view', 'edit' ],
                    'readonly'   => true,
                    'properties' => [
                        '_type'  => [
                            'type'        => 'string',
                            'enum'        => [ PaymentsProviders::EXTENSION_TYPE_WPORG ],
                            'description' => esc_html__('The type of the plugin.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'slug'   => [
                            'type'        => 'string',
                            'description' => esc_html__('The slug of the plugin.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'status' => [
                            'type'        => 'string',
                            'enum'        => [
                                PaymentsProviders::EXTENSION_NOT_INSTALLED,
                                PaymentsProviders::EXTENSION_INSTALLED,
                                PaymentsProviders::EXTENSION_ACTIVE,
                            ],
                            'description' => esc_html__('The status of the plugin.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                    ],
                ],
                'image'       => [
                    'type'        => 'string',
                    'description' => esc_html__('The URL of the image.', 'woocommerce'),
                    'readonly'    => true,
                ],
                'icon'        => [
                    'type'        => 'string',
                    'description' => esc_html__('The URL of the icon (square aspect ratio).', 'woocommerce'),
                    'readonly'    => true,
                ],
                'links'       => [
                    'type'     => 'array',
                    'context'  => [ 'view', 'edit' ],
                    'readonly' => true,
                    'items'    => [
                        'type'       => 'object',
                        'properties' => [
                            '_type' => [
                                'type'        => 'string',
                                'description' => esc_html__('The type of the link.', 'woocommerce'),
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'url'   => [
                                'type'        => 'string',
                                'description' => esc_html__('The URL of the link.', 'woocommerce'),
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                        ],
                    ],
                ],
                '_incentive'  => $this->get_schema_for_incentive(),
                'tags'        => [
                    'description' => esc_html__('The tags associated with the suggestion.', 'woocommerce'),
                    'type'        => 'array',
                    'uniqueItems' => true,
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'        => 'string',
                        'description' => esc_html__('The tags associated with the suggestion.', 'woocommerce'),
                        'readonly'    => true,
                    ],
                ],
                'category'    => [
                    'type'        => 'string',
                    'description' => esc_html__('The category of the suggestion.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                '_links'      => [
                    'type'       => 'object',
                    'context'    => [ 'view', 'edit' ],
                    'readonly'   => true,
                    'properties' => [
                        'attach' => [
                            'type'        => 'object',
                            'description' => esc_html__('The link to mark the suggestion as attached. This should be called when an extension is installed.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'properties'  => [
                                'href' => [
                                    'type'        => 'string',
                                    'description' => esc_html__('The URL to attach the suggestion.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                        'hide'   => [
                            'type'        => 'object',
                            'description' => esc_html__('The link to hide the suggestion.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'properties'  => [
                                'href' => [
                                    'type'        => 'string',
                                    'description' => esc_html__('The URL to hide the suggestion.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the schema for an incentive.
     *
     * @return array The incentive schema.
     */
    private function get_schema_for_incentive(): array
    {
        return [
            'type'        => 'object',
            'description' => esc_html__('The active incentive for the provider.', 'woocommerce'),
            'context'     => [ 'view', 'edit' ],
            'readonly'    => true,
            'properties'  => [
                'id'                => [
                    'type'        => 'string',
                    'description' => esc_html__('The incentive unique ID. This ID needs to be used for incentive dismissals.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'promo_id'          => [
                    'type'        => 'string',
                    'description' => esc_html__('The incentive promo ID. This ID need to be fed into the onboarding flow.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'title'             => [
                    'type'        => 'string',
                    'description' => esc_html__('The incentive title. It can contain stylistic HTML.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'description'       => [
                    'type'        => 'string',
                    'description' => esc_html__('The incentive description. It can contain stylistic HTML.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'short_description' => [
                    'type'        => 'string',
                    'description' => esc_html__('The short description of the incentive. It can contain stylistic HTML.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'cta_label'         => [
                    'type'        => 'string',
                    'description' => esc_html__('The call to action label for the incentive.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'tc_url'            => [
                    'type'        => 'string',
                    'description' => esc_html__('The URL to the terms and conditions for the incentive.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'badge'             => [
                    'type'        => 'string',
                    'description' => esc_html__('The badge label for the incentive.', 'woocommerce'),
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                '_dismissals'       => [
                    'type'        => 'array',
                    'description' => esc_html__('The dismissals list for the incentive. Each dismissal entry includes a context and a timestamp. The `all` entry means the incentive was dismissed for all contexts.', 'woocommerce'),
                    'uniqueItems' => true,
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'context'   => [
                                'type'        => 'string',
                                'description' => esc_html__('Context ID in which the incentive was dismissed.', 'woocommerce'),
                                'readonly'    => true,
                            ],
                            'timestamp' => [
                                'type'        => 'integer',
                                'description' => esc_html__('Unix timestamp representing when the incentive was dismissed.', 'woocommerce'),
                                'readonly'    => true,
                            ],
                        ],
                    ],
                ],
                '_links'            => [
                    'type'       => 'object',
                    'context'    => [ 'view', 'edit' ],
                    'readonly'   => true,
                    'properties' => [
                        'dismiss' => [
                            'type'        => 'object',
                            'description' => esc_html__('The link to dismiss the incentive.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'properties'  => [
                                'href' => [
                                    'type'        => 'string',
                                    'description' => esc_html__('The URL to dismiss the incentive.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                        'onboard' => [
                            'type'        => 'object',
                            'description' => esc_html__('The start/continue onboarding link for the payment gateway.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'properties'  => [
                                'href' => [
                                    'type'        => 'string',
                                    'description' => esc_html__('The URL to start/continue onboarding for the payment gateway.', 'woocommerce'),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
