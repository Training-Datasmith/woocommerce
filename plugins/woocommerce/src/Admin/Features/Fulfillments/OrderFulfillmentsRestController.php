<?php

/**
 * FulfillmentsAPISchema class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WC_Order;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * OrderFulfillmentsRestController class file.
 *
 * !> Note: This REST controller is only created for `WC_Order` type of entities, that allow
 * !> managing fulfillments only for admins. Regular users can only view their fulfillments.
 * !>
 * !> If you are using another entity type for your fulfillments, you should create a new controller.
 *
 * @package Automattic\WooCommerce\Admin\Features\Fulfillments
 */
class OrderFulfillmentsRestController extends RestApiControllerBase
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v3';

    /**
     * REST API base.
     *
     * @var string
     */
    protected $rest_base = '/orders/(?P<order_id>[\d]+)/fulfillments';

    /**
     * Get the WooCommerce REST API namespace for the class.
     */
    protected function get_rest_api_namespace(): string
    {
        return 'order_fulfillments';
    }

    /**
     * Register the routes for fulfillments.
     */
    public function register_routes(): void
    {
        // Register the route for getting and setting order fulfillments.
        register_rest_route(
            $this->route_namespace,
            $this->rest_base,
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'get_fulfillments'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_get_fulfillments(),
                    'schema'              => $this->get_schema_for_get_fulfillments(),
                ],
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'create_fulfillment'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_create_fulfillment(),
                    'schema'              => $this->get_schema_for_create_fulfillment(),
                ],
            ],
        );

        // Register the route for getting a specific fulfillment.
        register_rest_route(
            $this->route_namespace,
            $this->rest_base . '/(?P<fulfillment_id>[\d]+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'get_fulfillment'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_get_fulfillment(),
                    'schema'              => $this->get_schema_for_get_fulfillment(),
                ],
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'update_fulfillment'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_update_fulfillment(),
                    'schema'              => $this->get_schema_for_update_fulfillment(),
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'delete_fulfillment'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_delete_fulfillment(),
                    'schema'              => $this->get_schema_for_delete_fulfillment(),
                ],
            ],
        );

        // Register the route for fulfillment metadata.
        register_rest_route(
            $this->route_namespace,
            $this->rest_base . '/(?P<fulfillment_id>[\d]+)/metadata',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'get_fulfillment_meta'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_get_fulfillment_meta(),
                    'schema'              => $this->get_schema_for_get_fulfillment_meta(),
                ],
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'update_fulfillment_meta'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_update_fulfillment_meta(),
                    'schema'              => $this->get_schema_for_update_fulfillment_meta(),
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'delete_fulfillment_meta'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_delete_fulfillment_meta(),
                    'schema'              => $this->get_schema_for_delete_fulfillment_meta(),
                ],
            ],
        );

        // Register the route for tracking number lookup.
        register_rest_route(
            $this->route_namespace,
            $this->rest_base . '/lookup',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'get_tracking_number_details'),
                    'permission_callback' => $this->check_permission_for_fulfillments(...),
                    'args'                => $this->get_args_for_get_tracking_number_details(),
                    'schema'              => $this->get_schema_for_get_tracking_number_details(),
                ],
            ],
        );
    }

    /**
     * Permission check for REST API endpoints, given the request method.
     * For all fulfillments methods that have an order_id, we need to be sure the user has permission to view the order.
     * For all other methods, we check if the user is logged in as admin and has the required capability.
     *
     * @param WP_REST_Request $request The request for which the permission is checked.
     * @return bool|\WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
     *
     * @throws \WP_Error If the URL contains an order, but the order does not exist.
     */
    protected function check_permission_for_fulfillments(WP_REST_Request $request): \WP_Error|bool
    {
        // Fetch the order first if there's an order_id in the request.
        $order = null;
        if ($request->has_param('order_id')) {
            $order_id = (int) $request->get_param('order_id');
            $order    = wc_get_order($order_id);

            if (! $order) {
                return new \WP_Error(
                    'woocommerce_rest_order_invalid_id',
                    esc_html__('Invalid order ID.', 'woocommerce'),
                    [ 'status' => esc_attr(WP_Http::NOT_FOUND) ]
                );
            }
        }

        // Check if the user is logged in as admin, and has the required capability.
        // Admins who can manage WooCommerce can view all fulfillments.
        if (current_user_can('manage_woocommerce')) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
            return true;
        }

        // Check if the order exists, and if the current user is the owner of the order, and the request is a read request.
        // We allow this because we need to render the order fulfillments on the customer's order details and order tracking pages.
        // But they will be only able to view them, not edit.
        if (get_current_user_id() === $order->get_customer_id() && WP_REST_Server::READABLE === $request->get_method()) {
            return true;
        }

        // Return an error related to the request method.
        $error_information = $this->get_authentication_error_by_method($request->get_method());

        if (is_null($error_information)) {
            return false;
        }

        return new \WP_Error(
            $error_information['code'],
            $error_information['message'],
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Get the fulfillments for the order.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The fulfillments for the order, or an error if the request fails.
     */
    public function get_fulfillments(WP_REST_Request $request): WP_REST_Response
    {
        $order_id     = (int) $request->get_param('order_id');
        $fulfillments = [];

        // Fetch fulfillments for the order.
        try {
            /**
             * Fulfillments data store.
             *
             * @var \Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore $datastore
             */
            $datastore    = \WC_Data_Store::load('order-fulfillment');
            $fulfillments = $datastore->read_fulfillments(WC_Order::class, "$order_id");
        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        // Return the fulfillments.
        return new WP_REST_Response(
            array_map(
                fn (\Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment $fulfillment) => $fulfillment->get_raw_data(),
                $fulfillments
            ),
            WP_Http::OK
        );
    }

    /**
     * Create a new fulfillment with the given data for the order.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The created fulfillment, or an error if the request fails.
     */
    public function create_fulfillment(WP_REST_Request $request)
    {
        $order_id        = (int) $request->get_param('order_id');
        $notify_customer = (bool) $request->get_param('notify_customer');
        // Create a new fulfillment.
        try {
            $fulfillment = new Fulfillment();
            $fulfillment->set_props($request->get_json_params());
            $fulfillment->set_meta_data($request->get_json_params()['meta_data']);
            $fulfillment->set_entity_type(WC_Order::class);
            $fulfillment->set_entity_id("$order_id");

            $fulfillment->save();

            if ($fulfillment->get_is_fulfilled() && $notify_customer) {
                /**
                 * Trigger the fulfillment created notification on creating a fulfilled fulfillment.
                 *
                 * @since 10.1.0
                 */
                do_action('woocommerce_fulfillment_created_notification', $order_id, $fulfillment, wc_get_order($order_id));
            }
        } catch (ApiException $ex) {
            return $this->prepare_error_response(
                $ex->getErrorCode(),
                $ex->getMessage(),
                WP_Http::BAD_REQUEST
            );

        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        return new WP_REST_Response($fulfillment->get_raw_data(), WP_Http::CREATED);
    }

    /**
     * Get a specific fulfillment for the order.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The fulfillment for the order, or an error if the request fails.
     *
     * @throws \Exception If the fulfillment is not found or is deleted.
     */
    public function get_fulfillment(WP_REST_Request $request): WP_REST_Response
    {
        $order_id       = (int) $request->get_param('order_id');
        $fulfillment_id = (int) $request->get_param('fulfillment_id');

        // Fetch the fulfillment for the order.
        try {
            $fulfillment = new Fulfillment($fulfillment_id);
            $this->validate_fulfillment($fulfillment, $fulfillment_id, $order_id);
            if ($fulfillment->get_date_deleted()) {
                throw new \Exception(
                    esc_html__('Fulfillment not found.', 'woocommerce'),
                    WP_Http::NOT_FOUND
                );
            }
        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        return new WP_REST_Response(
            $fulfillment->get_raw_data(),
            WP_Http::OK
        );
    }

    /**
     * Update a specific fulfillment for the order.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The updated fulfillment, or an error if the request fails.
     */
    public function update_fulfillment(WP_REST_Request $request): WP_REST_Response
    {
        $order_id        = (int) $request->get_param('order_id');
        $fulfillment_id  = (int) $request->get_param('fulfillment_id');
        $notify_customer = (bool) $request->get_param('notify_customer');

        // Update the fulfillment for the order.
        try {
            $fulfillment    = new Fulfillment($fulfillment_id);
            $previous_state = $fulfillment->get_is_fulfilled();
            $this->validate_fulfillment($fulfillment, $fulfillment_id, $order_id);

            $fulfillment->set_props($request->get_json_params());
            $next_state = $fulfillment->get_is_fulfilled();

            if (isset($request->get_json_params()['meta_data']) && is_array($request->get_json_params()['meta_data'])) {
                // Update the meta data keys that exist in the request.
                foreach ($request->get_json_params()['meta_data'] as $meta) {
                    $fulfillment->update_meta_data($meta['key'], $meta['value'], $meta['id'] ?? 0);
                }

                // Remove the meta data keys that don't exist in the request, by matching their keys.
                $existing_meta_data = $fulfillment->get_meta_data();
                foreach ($existing_meta_data as $meta) {
                    if (! in_array($meta->key, array_column($request->get_json_params()['meta_data'], 'key'), true)) {
                        $fulfillment->delete_meta_data($meta->key);
                    }
                }
            }
            $fulfillment->save();
            $fulfillment->save_meta_data();

            if ($notify_customer) {
                if (! $previous_state && $next_state) {
                    /**
                     * Trigger the fulfillment created notification on fulfilling a fulfillment.
                     *
                     * @since 10.1.0
                     */
                    do_action('woocommerce_fulfillment_created_notification', $order_id, $fulfillment, wc_get_order($order_id));
                } elseif ($next_state) {
                    /**
                     * Trigger the fulfillment updated notification on updating a fulfillment.
                     *
                     * @since 10.1.0
                     */
                    do_action('woocommerce_fulfillment_updated_notification', $order_id, $fulfillment, wc_get_order($order_id));
                }
            }
        } catch (ApiException $ex) {
            return $this->prepare_error_response(
                $ex->getErrorCode(),
                $ex->getMessage(),
                WP_Http::BAD_REQUEST
            );
        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        return new WP_REST_Response(
            $fulfillment->get_raw_data(),
            WP_Http::OK
        );
    }

    /**
     * Delete a specific fulfillment for the order.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The deleted fulfillment, or an error if the request fails.
     */
    public function delete_fulfillment(WP_REST_Request $request)
    {
        $order_id        = (int) $request->get_param('order_id');
        $fulfillment_id  = (int) $request->get_param('fulfillment_id');
        $notify_customer = (bool) $request->get_param('notify_customer');

        // Delete the fulfillment for the order.
        try {
            $fulfillment = new Fulfillment($fulfillment_id);
            $this->validate_fulfillment($fulfillment, $fulfillment_id, $order_id);
            $fulfillment->delete();
        } catch (ApiException $ex) {
            return $this->prepare_error_response(
                $ex->getErrorCode(),
                $ex->getMessage(),
                WP_Http::BAD_REQUEST
            );
        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        if ($fulfillment->get_is_fulfilled() && $notify_customer) {
            /**
             * Trigger the fulfillment deleted notification.
             *
             * @since 10.1.0
             */
            do_action('woocommerce_fulfillment_deleted_notification', $order_id, $fulfillment, wc_get_order($order_id));
        }
        return new WP_REST_Response(
            [
                'message' => __('Fulfillment deleted successfully.', 'woocommerce'),
            ],
            WP_Http::OK
        );
    }

    /**
     * Get the metadata for a specific fulfillment.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The metadata for the fulfillment, or an error if the request fails.
     */
    public function get_fulfillment_meta(WP_REST_Request $request): WP_REST_Response
    {
        $order_id       = (int) $request->get_param('order_id');
        $fulfillment_id = (int) $request->get_param('fulfillment_id');

        // Fetch the metadata for the fulfillment.
        try {
            $fulfillment = new Fulfillment($fulfillment_id);
            $this->validate_fulfillment($fulfillment, $fulfillment_id, $order_id);
        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        return new WP_REST_Response(
            $fulfillment->get_raw_meta_data(),
            WP_Http::OK
        );
    }

    /**
     * Update the metadata for a specific fulfillment.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The updated metadata for the fulfillment, or an error if the request fails.
     */
    public function update_fulfillment_meta(WP_REST_Request $request): WP_REST_Response
    {
        $order_id       = (int) $request->get_param('order_id');
        $fulfillment_id = (int) $request->get_param('fulfillment_id');

        // Update the metadata for the fulfillment.
        try {
            $fulfillment = new Fulfillment($fulfillment_id);
            $this->validate_fulfillment($fulfillment, $fulfillment_id, $order_id);

            // Update the meta data keys that exist in the request.
            foreach ($request->get_json_params()['meta_data'] as $meta) {
                $fulfillment->update_meta_data($meta['key'], $meta['value'], $meta['id'] ?? 0);
            }

            // Remove the meta data keys that don't exist in the request, by matching their keys.
            $existing_meta_data = $fulfillment->get_meta_data();
            foreach ($existing_meta_data as $meta) {
                if (! in_array($meta->key, array_column($request->get_json_params()['meta_data'], 'key'), true)) {
                    $fulfillment->delete_meta_data($meta->key);
                }
            }
            $fulfillment->save();
        } catch (ApiException $ex) {
            return $this->prepare_error_response(
                $ex->getErrorCode(),
                $ex->getMessage(),
                WP_Http::BAD_REQUEST
            );
        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        return new WP_REST_Response(
            $fulfillment->get_raw_meta_data(),
            WP_Http::OK
        );
    }

    /**
     * Delete the metadata for a specific fulfillment.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The deleted metadata for the fulfillment, or an error if the request fails.
     */
    public function delete_fulfillment_meta(WP_REST_Request $request)
    {
        $order_id       = (int) $request->get_param('order_id');
        $fulfillment_id = (int) $request->get_param('fulfillment_id');

        // Delete the metadata for the fulfillment.
        try {
            $fulfillment = new Fulfillment($fulfillment_id);
            $this->validate_fulfillment($fulfillment, $fulfillment_id, $order_id);

            $meta_key = sanitize_text_field(wp_unslash((string) $request->get_param('meta_key')));
            $fulfillment->delete_meta_data($meta_key);
            $fulfillment->save();
        } catch (ApiException $ex) {
            return $this->prepare_error_response(
                $ex->getErrorCode(),
                $ex->getMessage(),
                WP_Http::BAD_REQUEST
            );
        } catch (\Throwable $e) {
            return $this->prepare_error_response(
                $e->getCode(),
                $e->getMessage(),
                WP_Http::BAD_REQUEST
            );
        }

        return new WP_REST_Response(
            $fulfillment->get_raw_meta_data(),
            WP_Http::OK
        );
    }

    /**
     * Get the tracking number details for a given tracking number, if possible.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_REST_Response The tracking number details, or an error if the request fails.
     */
    public function get_tracking_number_details(WP_REST_Request $request)
    {
        $order_id        = (int) $request->get_param('order_id');
        $tracking_number = sanitize_text_field($request->get_param('tracking_number'));

        if (empty($tracking_number)) {
            return $this->prepare_error_response(
                'woocommerce_rest_tracking_number_missing',
                __('Tracking number is required.', 'woocommerce'),
                [ 'status' => WP_Http::BAD_REQUEST ]
            );
        }

        if (! $order_id) {
            return $this->prepare_error_response(
                'woocommerce_rest_order_id_missing',
                __('Order ID is required.', 'woocommerce'),
                [ 'status' => WP_Http::BAD_REQUEST ]
            );
        }

        $order = wc_get_order($order_id);
        if (! $order || ! $order instanceof WC_Order) {
            return $this->prepare_error_response(
                'woocommerce_rest_order_invalid_id',
                __('Invalid order ID.', 'woocommerce'),
                [ 'status' => WP_Http::NOT_FOUND ]
            );
        }

        /**
         * Parse the tracking number with additional parameters.
         *
         * @since 10.1.0
         */
        $tracking_number_parse_result = apply_filters(
            'woocommerce_fulfillment_parse_tracking_number',
            $tracking_number,
            WC()->countries->get_base_country(),
            $order->get_shipping_country(),
        );

        return new WP_REST_Response($tracking_number_parse_result, WP_Http::OK);
    }

    /**
     * Get the arguments for the get order fulfillments endpoint.
     */
    private function get_args_for_get_fulfillments(): array
    {
        return [
            'order_id' => [
                'description' => __('Unique identifier for the order.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
        ];
    }

    /**
     * Get the schema for the get order fulfillments endpoint.
     */
    private function get_schema_for_get_fulfillments(): array
    {
        $schema          = $this->get_base_schema();
        $schema['title'] = __('Get fulfillments response.', 'woocommerce');
        $schema['type']  = 'array';
        $schema['items'] = [
            'type'       => 'object',
            'properties' => $this->get_read_schema_for_fulfillment(),
        ];
        return $schema;
    }

    /**
     * Get the arguments for the create fulfillment endpoint.
     */
    private function get_args_for_create_fulfillment(): array
    {
        return $this->get_write_args_for_fulfillment(true);
    }

    /**
     * Get the schema for the create fulfillment endpoint.
     */
    private function get_schema_for_create_fulfillment(): array
    {
        $schema               = $this->get_base_schema();
        $schema['title']      = __('Create fulfillment response.', 'woocommerce');
        $schema['properties'] = $this->get_read_schema_for_fulfillment();
        return $schema;
    }

    /**
     * Get the arguments for the get fulfillment endpoint.
     */
    private function get_args_for_get_fulfillment(): array
    {
        return [
            'order_id'       => [
                'description' => __('Unique identifier for the order.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'required'    => true,
            ],
            'fulfillment_id' => [
                'description' => __('Unique identifier for the fulfillment.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'required'    => true,
            ],
        ];
    }

    /**
     * Get the schema for the get fulfillment endpoint.
     */
    private function get_schema_for_get_fulfillment(): array
    {
        $schema               = $this->get_base_schema();
        $schema['title']      = __('Get fulfillment response.', 'woocommerce');
        $schema['properties'] = $this->get_read_schema_for_fulfillment();

        return $schema;
    }

    /**
     * Get the arguments for the update fulfillment endpoint.
     */
    private function get_args_for_update_fulfillment(): array
    {
        return $this->get_write_args_for_fulfillment(false);
    }

    /**
     * Get the schema for the update fulfillment endpoint.
     */
    private function get_schema_for_update_fulfillment(): array
    {
        $schema               = $this->get_base_schema();
        $schema['title']      = __('Update fulfillment response.', 'woocommerce');
        $schema['type']       = 'object';
        $schema['properties'] = $this->get_read_schema_for_fulfillment();

        return $schema;
    }

    /**
     * Get the arguments for the delete fulfillment endpoint.
     */
    private function get_args_for_delete_fulfillment(): array
    {
        return [
            'order_id'        => [
                'description' => __('Unique identifier for the order.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'fulfillment_id'  => [
                'description' => __('Unique identifier for the fulfillment.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'notify_customer' => [
                'description' => __('Whether to notify the customer about the fulfillment update.', 'woocommerce'),
                'type'        => 'boolean',
                'default'     => false,
                'required'    => false,
                'context'     => [ 'view', 'edit' ],
            ],
        ];
    }

    /**
     * Get the schema for the delete fulfillment endpoint.
     */
    private function get_schema_for_delete_fulfillment(): array
    {
        $schema               = $this->get_base_schema();
        $schema['title']      = __('Delete fulfillment response.', 'woocommerce');
        $schema['properties'] = [
            'message' => [
                'description' => __('The response message.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
            ],
        ];

        return $schema;
    }

    /**
     * Get the arguments for the get fulfillment meta endpoint.
     */
    private function get_args_for_get_fulfillment_meta(): array
    {
        return [
            'order_id'       => [
                'description' => __('Unique identifier for the order.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'fulfillment_id' => [
                'description' => __('Unique identifier for the fulfillment.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
        ];
    }

    /**
     * Get the schema for the get fulfillment meta endpoint.
     */
    private function get_schema_for_get_fulfillment_meta(): array
    {
        $schema          = $this->get_base_schema();
        $schema['title'] = __('Get fulfillment meta data response.', 'woocommerce');
        $schema['type']  = 'array';
        $schema['items'] = [
            'description' => __('The meta data object.', 'woocommerce'),
            'type'        => 'object',
            'properties'  => $this->get_schema_for_meta_data(),
        ];

        return $schema;
    }

    /**
     * Get the arguments for the update fulfillment meta endpoint.
     */
    private function get_args_for_update_fulfillment_meta(): array
    {
        return [
            'order_id'       => [
                'description' => __('Unique identifier for the order.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'fulfillment_id' => [
                'description' => __('Unique identifier for the fulfillment.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'meta_data'      => [
                'description' => __('The meta data array.', 'woocommerce'),
                'type'        => 'array',
                'required'    => true,
                'items'       => [
                    'description' => __('The meta data object.', 'woocommerce'),
                    'type'        => 'object',
                    'properties'  => $this->get_schema_for_meta_data(),
                ],
            ],
        ];
    }

    /**
     * Get the schema for the update fulfillment meta endpoint.
     */
    private function get_schema_for_update_fulfillment_meta(): array
    {
        $schema          = $this->get_base_schema();
        $schema['title'] = __('Update fulfillment meta data response.', 'woocommerce');
        $schema['type']  = 'array';
        $schema['items'] = [
            'description' => __('The meta data object.', 'woocommerce'),
            'type'        => 'object',
            'properties'  => $this->get_schema_for_meta_data(),
        ];

        return $schema;
    }

    /**
     * Get the arguments for the delete fulfillment meta endpoint.
     */
    private function get_args_for_delete_fulfillment_meta(): array
    {
        return [
            'order_id'       => [
                'description' => __('Unique identifier for the order.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'fulfillment_id' => [
                'description' => __('Unique identifier for the fulfillment.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'meta_key'       => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'description' => __('The meta key to delete.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
            ],
        ];
    }

    /**
     * Get the schema for the delete fulfillment meta endpoint.
     */
    private function get_schema_for_delete_fulfillment_meta(): array
    {
        $schema          = $this->get_base_schema();
        $schema['title'] = __('Delete fulfillment meta data response.', 'woocommerce');
        $schema['type']  = 'array';
        $schema['items'] = [
            'description' => __('The meta data object.', 'woocommerce'),
            'type'        => 'object',
            'properties'  => $this->get_schema_for_meta_data(),
        ];

        return $schema;
    }

    /**
     * Get the arguments for the get tracking number details endpoint.
     */
    private function get_args_for_get_tracking_number_details(): array
    {
        return [
            'order_id'        => [
                'description' => __('Unique identifier for the order.', 'woocommerce'),
                'type'        => 'integer',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'tracking_number' => [
                'description' => __('The tracking number to look up.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
        ];
    }

    /**
     * Get the schema for the get tracking number details endpoint.
     */
    private function get_schema_for_get_tracking_number_details(): array
    {
        $schema               = $this->get_base_schema();
        $schema['title']      = __('The tracking number details response.', 'woocommerce');
        $schema['properties'] = [
            'tracking_number'   => [
                'description' => __('The tracking number.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
            ],
            'shipping_provider' => [
                'description' => __('The shipping provider.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
            ],
            'tracking_url'      => [
                'description' => __('The tracking URL.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
            ],
            'possibilities'     => [
                'description' => __('Ambiguous shipping providers list.', 'woocommerce'),
                'type'        => 'array',
                'required'    => false,
                'items'       => [
                    'type' => 'string',
                ],
            ],
        ];
        return $schema;
    }

    /**
     * Get the base schema for the fulfillment with a read context.
     */
    private function get_read_schema_for_fulfillment(): array
    {
        return [
            'id'           => [
                'description' => __('Unique identifier for the fulfillment.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'entity_type'  => [
                'description' => __('The type of entity for which the fulfillment is created.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'entity_id'    => [
                'description' => __('Unique identifier for the entity.', 'woocommerce'),
                'type'        => 'string',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'status'       => [
                'description' => __('The status of the fulfillment.', 'woocommerce'),
                'type'        => 'string',
                'default'     => 'unfulfilled',
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'is_fulfilled' => [
                'description' => __('Whether the fulfillment is fulfilled.', 'woocommerce'),
                'type'        => 'boolean',
                'default'     => false,
                'required'    => true,
                'context'     => [ 'view', 'edit' ],
            ],
            'date_updated' => [
                'description' => __('The date the fulfillment was last updated.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'required'    => true,
            ],
            'date_deleted' => [
                'description' => __('The date the fulfillment was deleted.', 'woocommerce'),
                'anyOf'       => [
                    [
                        'type' => 'string',
                    ],
                    [
                        'type' => 'null',
                    ],
                ],
                'default'     => null,
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'required'    => true,
            ],
            'meta_data'    => [
                'description' => __('Meta data for the fulfillment.', 'woocommerce'),
                'type'        => 'array',
                'required'    => true,
                'items'       => $this->get_schema_for_meta_data(),
            ],
        ];
    }

    /**
     * Get the base args for the fulfillment with a write context.
     *
     * @param bool $is_create Whether the args list is for a create request.
     */
    private function get_write_args_for_fulfillment(bool $is_create = false): array
    {
        return array_merge(
            ! $is_create ? [
                'fulfillment_id' => [
                    'description' => __('Unique identifier for the fulfillment.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ] : [],
            [
                'status'          => [
                    'description' => __('The status of the fulfillment.', 'woocommerce'),
                    'type'        => 'string',
                    'default'     => 'unfulfilled',
                    'required'    => false,
                    'context'     => [ 'view', 'edit' ],
                ],
                'is_fulfilled'    => [
                    'description' => __('Whether the fulfillment is fulfilled.', 'woocommerce'),
                    'type'        => 'boolean',
                    'default'     => false,
                    'required'    => false,
                    'context'     => [ 'view', 'edit' ],
                ],
                'meta_data'       => [
                    'description' => __('Meta data for the fulfillment.', 'woocommerce'),
                    'type'        => 'array',
                    'required'    => true,
                    'schema'      => $this->get_schema_for_meta_data(),
                ],
                'notify_customer' => [
                    'description' => __('Whether to notify the customer about the fulfillment update.', 'woocommerce'),
                    'type'        => 'boolean',
                    'default'     => false,
                    'required'    => false,
                    'context'     => [ 'view', 'edit' ],
                ],
            ]
        );
    }

    /**
     * Get the schema for the meta data.
     */
    private function get_schema_for_meta_data(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'    => [
                    'description' => __('The unique identifier for the meta data. Set `0` for new records.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'key'   => [
                    'description' => __('The key of the meta data.', 'woocommerce'),
                    'type'        => 'string',
                    'required'    => true,
                    'context'     => [ 'view', 'edit' ],
                ],
                'value' => [
                    'description' => __('The value of the meta data.', 'woocommerce'),
                    'type'        => 'string',
                    'required'    => true,
                    'context'     => [ 'view', 'edit' ],
                ],
            ],
            'required'   => true,
            'context'    => [ 'view', 'edit' ],
            'readonly'   => true,
        ];
    }

    /**
     * Prepare an error response.
     *
     * @param string $code The error code.
     * @param string $message The error message.
     * @param int    $status The HTTP status code.
     *
     * @return WP_REST_Response The error response.
     */
    private function prepare_error_response(int|string $code, $message, $status): WP_REST_Response
    {
        return new WP_REST_Response(
            [
                'code'    => $code,
                'message' => $message,
                'data'    => [ 'status' => $status ],
            ],
            $status
        );
    }

    /**
     * Validate the fulfillment.
     *
     * @param Fulfillment $fulfillment The fulfillment object.
     * @param int         $fulfillment_id The fulfillment ID.
     * @param int         $order_id The order ID.
     *
     * @throws \Exception If the fulfillment ID is invalid.
     */
    private function validate_fulfillment(Fulfillment $fulfillment, int $fulfillment_id, int $order_id): void
    {
        if ($fulfillment->get_id() !== $fulfillment_id || $fulfillment->get_entity_type() !== WC_Order::class || $fulfillment->get_entity_id() !== "$order_id") {
            throw new \Exception(esc_html__('Invalid fulfillment ID.', 'woocommerce'));
        }
    }
}
