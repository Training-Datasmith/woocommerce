<?php

// phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * REST API Customers controller
 *
 * Handles route registration, permissions, CRUD operations, and schema definition.
 *
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractController;
use Automattic\WooCommerce\StoreApi\Utilities\Pagination;
use WC_Customer;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Customers Controller.
 */
class Controller extends AbstractController
{
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'customers';

    /**
     * Schema class for this route.
     *
     * @var CustomerSchema
     */
    protected $item_schema;

    /**
     * Collection query class.
     *
     * @var CollectionQuery
     */
    protected $collection_query;

    /**
     * Update utils class.
     *
     * @var UpdateUtils
     */
    protected $update_utils;

    /**
     * Initialize the controller.
     *
     * @param CustomerSchema  $item_schema Customer schema class.
     * @param CollectionQuery $collection_query Collection query class.
     * @param UpdateUtils     $update_utils Update utils class.
     * @internal
     */
    final public function init(CustomerSchema $item_schema, CollectionQuery $collection_query, UpdateUtils $update_utils): void
    {
        $this->item_schema      = $item_schema;
        $this->collection_query = $collection_query;
        $this->update_utils     = $update_utils;
    }

    /**
     * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
     * after running various hooks on the response.
     */
    protected function get_schema(): array
    {
        return $this->item_schema->get_item_schema();
    }

    /**
     * Get the collection args schema.
     */
    protected function get_query_schema(): array
    {
        return $this->collection_query->get_query_schema();
    }

    /**
     * Register the routes for customers.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'schema' => [ $this, 'get_public_item_schema' ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => $this->get_items(...),
                    'permission_callback' => $this->get_items_permissions_check(...),
                    'args'                => $this->get_collection_params(),
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => $this->create_item(...),
                    'permission_callback' => $this->create_item_permissions_check(...),
                    'args'                => array_merge(
                        $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
                        [
                            'email'    => [
                                'required'    => true,
                                'type'        => 'string',
                                'description' => __('New user email address.', 'woocommerce'),
                            ],
                            'username' => [
                                'required'    => 'no' === get_option('woocommerce_registration_generate_username', 'yes'),
                                'description' => __('New user username.', 'woocommerce'),
                                'type'        => 'string',
                            ],
                            'password' => [
                                'required'    => 'no' === get_option('woocommerce_registration_generate_password', 'no'),
                                'description' => __('New user password.', 'woocommerce'),
                                'type'        => 'string',
                            ],
                        ]
                    ),
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            [
                'schema' => [ $this, 'get_public_item_schema' ],
                'args'   => [
                    'id' => [
                        'description' => __('Unique identifier for the resource.', 'woocommerce'),
                        'type'        => 'integer',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => $this->get_item(...),
                    'permission_callback' => $this->get_item_permissions_check(...),
                    'args'                => [
                        'context' => $this->get_context_param([ 'default' => 'view' ]),
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => $this->update_item(...),
                    'permission_callback' => $this->update_item_permissions_check(...),
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => $this->delete_item(...),
                    'permission_callback' => $this->delete_item_permissions_check(...),
                    'args'                => [
                        'force' => [
                            'default'     => false,
                            'type'        => 'boolean',
                            'description' => __('Required to be true, as resource does not support trashing.', 'woocommerce'),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Get a single customer.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        $user = \Automattic\WooCommerce\Internal\Utilities\Users::get_user_in_current_site($request['id']);
        if (is_wp_error($user)) {
            return $this->get_route_error_by_code(self::INVALID_ID);
        }

        $customer = $this->prepare_item_for_response(new WC_Customer($user->ID), $request);

        return rest_ensure_response($customer);
    }

    /**
     * Prepare a single customer object for response.
     *
     * @param WC_Customer     $customer Customer object.
     * @param WP_REST_Request $request Request object.
     */
    protected function get_item_response($customer, WP_REST_Request $request): array
    {
        return $this->item_schema->get_item_response($customer, $request, $this->get_fields_for_response($request));
    }

    /**
     * Get collection of customers.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        $query_args = $this->collection_query->get_query_args($request);
        $results    = $this->collection_query->get_query_results($query_args, $request);
        $items      = [];

        foreach ($results['results'] as $customer) {
            $items[] = $this->prepare_response_for_collection($this->prepare_item_for_response($customer, $request));
        }

        $pagination_util = new Pagination();

        return $pagination_util->add_headers(rest_ensure_response($items), $request, $results['total'], $results['pages']);
    }

    /**
     * Create a single customer.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {
        if (! empty($request['id'])) {
            return $this->get_route_error_by_code(self::RESOURCE_EXISTS);
        }

        try {
            // Sets the username.
            $request['username'] = ! empty($request['username']) ? $request['username'] : '';

            // Sets the password.
            $request['password'] = ! empty($request['password']) ? $request['password'] : '';

            // Create customer.
            $customer = new WC_Customer();
            $customer->set_username($request['username']);
            $customer->set_password($request['password']);
            $customer->set_email($request['email']);

            $this->update_utils->update_customer_from_request($customer, $request, true);

            if (! $customer->get_id()) {
                return $this->get_route_error_by_code(self::CANNOT_CREATE);
            }

            $user_data = get_userdata($customer->get_id());
            $this->update_additional_fields_for_object($user_data, $request);

            /**
             * Fires after a customer is created via the REST API.
             *
             * @param WP_User         $user_data Data used to create the customer.
             * @param WP_REST_Request $request   Request object.
             * @since 10.2.0
             */
            do_action($this->get_hook_prefix() . 'created', $user_data, $request);

            $request->set_param('context', 'edit');
            $response = $this->prepare_item_for_response($customer, $request);
            $response->set_status(WP_Http::CREATED);
            $response->header('Location', rest_url(sprintf('/%s/%s/%d', $this->namespace, $this->rest_base, $customer->get_id())));

            return $response;
        } catch (\WC_REST_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        } catch (\Exception) {
            return $this->get_route_error_by_code(self::CANNOT_CREATE);
        }
    }

    /**
     * Update a single customer.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item($request)
    {
        $user = \Automattic\WooCommerce\Internal\Utilities\Users::get_user_in_current_site($request['id']);
        if (is_wp_error($user)) {
            return $this->get_route_error_by_code(self::INVALID_ID);
        }

        $customer = new WC_Customer($user->ID);

        if (! $customer->get_id()) {
            return $this->get_route_error_by_code(self::INVALID_ID);
        }

        try {
            $this->update_utils->update_customer_from_request($customer, $request, false);

            $user_data = get_userdata($customer->get_id());
            $this->update_additional_fields_for_object($user_data, $request);

            /**
             * Fires after a customer is updated via the REST API.
             *
             * @param WP_User         $user_data Data used to update the customer.
             * @param WP_REST_Request $request   Request object.
             * @since 10.2.0
             */
            do_action($this->get_hook_prefix() . 'updated', $user_data, $request);

            $request->set_param('context', 'edit');
            return $this->prepare_item_for_response($customer, $request);
        } catch (\WC_REST_Exception $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        } catch (\Exception) {
            return $this->get_route_error_by_code(self::CANNOT_UPDATE);
        }
    }

    /**
     * Delete a single customer.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item($request)
    {
        $id    = (int) $request['id'];
        $force = isset($request['force']) && (bool) $request['force'];

        // We don't support trashing for this type, error out.
        if (! $force) {
            return $this->get_route_error_by_code(self::TRASH_NOT_SUPPORTED);
        }

        $user_data = \Automattic\WooCommerce\Internal\Utilities\Users::get_user_in_current_site($id);
        if (is_wp_error($user_data)) {
            return $this->get_route_error_by_code(self::INVALID_ID);
        }

        $request->set_param('context', 'edit');
        $response = $this->prepare_item_for_response(new WC_Customer($id), $request);

        /** Include admin customer functions to get access to wp_delete_user() */
        require_once ABSPATH . 'wp-admin/includes/user.php';

        $customer = new WC_Customer($id);

        $result = $customer->delete();

        if (! $result) {
            return $this->get_route_error_by_code(self::CANNOT_DELETE);
        }

        /**
         * Fires after a customer is deleted via the REST API.
         *
         * @param WP_User          $user_data User data.
         * @param WP_REST_Response $response  The response returned from the API.
         * @param WP_REST_Request  $request   The request sent to the API.
         * @since 10.2.0
         */
        do_action($this->get_hook_prefix() . 'deleted', $user_data, $response, $request);

        return $response;
    }

    /**
     * Check if a given request has access to read items.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request)
    {
        if (! wc_rest_check_user_permissions('read')) {
            return $this->get_authentication_error_by_method($request->get_method());
        }
        return true;
    }

    /**
     * Check if a given request has access to read an item.
     *
     * @param  WP_REST_Request $request The request object.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request)
    {
        $user = \Automattic\WooCommerce\Internal\Utilities\Users::get_user_in_current_site($request['id']);

        if (is_wp_error($user)) {
            return $this->get_route_error_by_code(self::INVALID_ID);
        }

        if (! wc_rest_check_user_permissions('read', $user->ID)) {
            return $this->get_authentication_error_by_method($request->get_method());
        }
        return true;
    }

    /**
     * Check if a given request has access to create an item.
     *
     * @param  WP_REST_Request $request The request object.
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check($request)
    {
        if (! wc_rest_check_user_permissions('create')) {
            return $this->get_authentication_error_by_method($request->get_method());
        }
        return true;
    }

    /**
     * Check if a given request has access to update an item.
     *
     * @param  WP_REST_Request $request The request object.
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check($request)
    {
        $user = \Automattic\WooCommerce\Internal\Utilities\Users::get_user_in_current_site($request['id']);

        if (is_wp_error($user)) {
            return $this->get_route_error_by_code(self::INVALID_ID);
        }

        if (! wc_rest_check_user_permissions('edit', $user->ID)) {
            return $this->get_authentication_error_by_method($request->get_method());
        }

        // Check if user role is allowed to be updated.
        $allowed_roles = $this->get_allowed_roles();
        $customer      = new WC_Customer($user->ID);

        if ($customer && ! in_array($customer->get_role(), $allowed_roles, true)) {
            // Check against existing props to be compatible with clients that will send the entire user object.
            $non_editable_props = [ 'email', 'password' ];
            $customer_prop      = [ 'email' => $customer->get_email() ];
            foreach ($non_editable_props as $prop) {
                if (isset($request[ $prop ]) && ('password' === $prop || $request[ $prop ] !== $customer_prop[ $prop ])) {
                    return new WP_Error(
                        'woocommerce_rest_cannot_edit',
                        sprintf(
                            /* translators: 1s: name of the property (email, role), 2: Role of the user (administrator, customer). */
                            __('Sorry, %1$s cannot be updated via this endpoint for a user with role %2$s.', 'woocommerce'),
                            $prop,
                            $customer->get_role()
                        ),
                        [ 'status' => rest_authorization_required_code() ]
                    );
                }
            }
        }

        return true;
    }

    /**
     * Check if a given request has access to delete an item.
     *
     * @param  WP_REST_Request $request The request object.
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request)
    {
        $user = \Automattic\WooCommerce\Internal\Utilities\Users::get_user_in_current_site($request['id']);

        if (is_wp_error($user)) {
            return $this->get_route_error_by_code(self::INVALID_ID);
        }

        if (! wc_rest_check_user_permissions('delete', $user->ID)) {
            return $this->get_authentication_error_by_method($request->get_method());
        }

        $id = (int) $request['id'];

        $allowed_roles = $this->get_allowed_roles();
        $customer      = new WC_Customer($id);

        if (! in_array($customer->get_role(), $allowed_roles, true)) {
            return new WP_Error(
                'woocommerce_rest_cannot_delete',
                sprintf(
                    /* translators: 1: Role of the user (administrator, customer), 2: comma separated list of allowed roles. egs customer, subscriber */
                    __('Sorry, users with %1$s role cannot be deleted via this endpoint. Allowed roles: %2$s', 'woocommerce'),
                    $customer->get_role(),
                    implode(', ', $allowed_roles)
                ),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        return true;
    }

    /**
     * Returns list of allowed roles for the REST API.
     *
     * @return array $roles Allowed roles to be updated via the REST API.
     */
    private function get_allowed_roles(): array
    {
        /**
         * Filter the allowed roles for the REST API.
         *
         * Danger: Make sure that the roles listed here cannot manage the shop.
         *
         * @param array $roles Array of allowed roles.
         *
         * @since 9.5.2
         */
        return apply_filters('woocommerce_rest_customer_allowed_roles', [ 'customer', 'subscriber' ]);
    }
}
