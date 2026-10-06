<?php

declare(strict_types=1);
/**
 * REST API Customers controller
 *
 * Handles requests to the /customers endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined('ABSPATH') || exit;

/**
 * REST API Customers controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Customers_V1_Controller
 */
class WC_REST_Customers_V2_Controller extends WC_REST_Customers_V1_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v2';

    /**
     * Get formatted item data.
     *
     * @since  3.0.0
     * @param  WC_Data $object WC_Data instance.
     * @return array
     */
    protected function get_formatted_item_data($object): array    {
        $formatted_data                 = $this->get_formatted_item_data_core($object);
        $formatted_data['orders_count'] = $object->get_order_count();
        $formatted_data['total_spent']  = $object->get_total_spent();
        return $formatted_data;
    }

    /**
     * Get formatted item data, not including orders count nor total spent.
     * This method is needed because v3 API doesn't return those two fields.
     *
     * @internal This method could disappear or have its name or signature changed in future releases.
     *
     * @param  WC_Data $object WC_Data instance.
     * @return array
     */
    protected function get_formatted_item_data_core($object)
    {
        $data        = $object->get_data();
        $format_date = [ 'date_created', 'date_modified' ];

        // Format date values.
        foreach ($format_date as $key) {
            // Date created is stored UTC, date modified is stored WP local time.
            $datetime              = 'date_created' === $key && is_subclass_of($data[ $key ], 'DateTime') ? get_date_from_gmt(gmdate('Y-m-d H:i:s', $data[ $key ]->getTimestamp())) : $data[ $key ];
            $data[ $key ]          = wc_rest_prepare_date_response($datetime, false);
            $data[ $key . '_gmt' ] = wc_rest_prepare_date_response($datetime);
        }

        $formatted_data = [
            'id'                 => $object->get_id(),
            'date_created'       => $data['date_created'],
            'date_created_gmt'   => $data['date_created_gmt'],
            'date_modified'      => $data['date_modified'],
            'date_modified_gmt'  => $data['date_modified_gmt'],
            'email'              => $data['email'],
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'role'               => $data['role'],
            'username'           => $data['username'],
            'billing'            => $data['billing'],
            'shipping'           => $data['shipping'],
            'is_paying_customer' => $data['is_paying_customer'],
            'avatar_url'         => $object->get_avatar_url(),
        ];

        if (wc_current_user_has_role('administrator')) {
            $formatted_data['meta_data'] = array_values(
                array_filter(
                    $data['meta_data'],
                    fn ($meta) => ! is_protected_meta($meta->key, 'user')
                )
            );
        }

        return $formatted_data;
    }

    /**
     * Prepare a single customer output for response.
     *
     * @param  WP_User         $user_data User object.
     * @param  WP_REST_Request $request   Request object.
     * @return WP_REST_Response $response  Response data.
     */
    public function prepare_item_for_response($user_data, $request)
    {
        $customer = new WC_Customer($user_data->ID);
        $data     = $this->get_formatted_item_data($customer);
        $context  = ! empty($request['context']) ? $request['context'] : 'view';
        $data     = $this->add_additional_fields_to_object($data, $request);
        $data     = $this->filter_response_by_context($data, $context);
        $response = rest_ensure_response($data);
        $response->add_links($this->prepare_links($user_data));

        //phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
        /**
         * Filter customer data returned from the REST API.
         *
         * @param WP_REST_Response $response   The response object.
         * @param WP_User          $user_data  User object used to create response.
         * @param WP_REST_Request  $request    Request object.
         */
        return apply_filters('woocommerce_rest_prepare_customer', $response, $user_data, $request);
        //phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment
    }

    /**
     * Update customer meta fields.
     *
     * @param WC_Customer     $customer Customer data.
     * @param WP_REST_Request $request  Request data.
     */
    protected function update_customer_meta_fields($customer, $request)
    {
        parent::update_customer_meta_fields($customer, $request);

        // Meta data.
        if (isset($request['meta_data'])) {
            if (is_array($request['meta_data'])) {
                foreach ($request['meta_data'] as $meta) {
                    if (is_protected_meta($meta['key'], 'user')) { // bypass internal keys.
                        continue;
                    }
                    $customer->update_meta_data($meta['key'], $meta['value'], $meta['id'] ?? '');
                }
            }
        }
    }

    /**
     * Get the Customer's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'customer',
            'type'       => 'object',
            'properties' => [
                'id'                 => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_created'       => [
                    'description' => __("The date the customer was created, in the site's timezone.", 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_created_gmt'   => [
                    'description' => __('The date the customer was created, as GMT.', 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_modified'      => [
                    'description' => __("The date the customer was last modified, in the site's timezone.", 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'date_modified_gmt'  => [
                    'description' => __('The date the customer was last modified, as GMT.', 'woocommerce'),
                    'type'        => 'date-time',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'email'              => [
                    'description' => __('The email address for the customer.', 'woocommerce'),
                    'type'        => 'string',
                    'format'      => 'email',
                    'context'     => [ 'view', 'edit' ],
                ],
                'first_name'         => [
                    'description' => __('Customer first name.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'last_name'          => [
                    'description' => __('Customer last name.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'role'               => [
                    'description' => __('Customer role.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'username'           => [
                    'description' => __('Customer login name.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_user',
                    ],
                ],
                'password'           => [
                    'description' => __('Customer password.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'edit' ],
                ],
                'billing'            => [
                    'description' => __('List of billing address data.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'properties'  => [
                        'first_name' => [
                            'description' => __('First name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'last_name'  => [
                            'description' => __('Last name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'company'    => [
                            'description' => __('Company name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_1'  => [
                            'description' => __('Address line 1', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_2'  => [
                            'description' => __('Address line 2', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'city'       => [
                            'description' => __('City name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'state'      => [
                            'description' => __('ISO code or name of the state, province or district.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'postcode'   => [
                            'description' => __('Postal code.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'country'    => [
                            'description' => __('ISO code of the country.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'email'      => [
                            'description' => __('Email address.', 'woocommerce'),
                            'type'        => 'string',
                            'format'      => 'email',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'phone'      => [
                            'description' => __('Phone number.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                    ],
                ],
                'shipping'           => [
                    'description' => __('List of shipping address data.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'properties'  => [
                        'first_name' => [
                            'description' => __('First name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'last_name'  => [
                            'description' => __('Last name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'company'    => [
                            'description' => __('Company name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_1'  => [
                            'description' => __('Address line 1', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'address_2'  => [
                            'description' => __('Address line 2', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'city'       => [
                            'description' => __('City name.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'state'      => [
                            'description' => __('ISO code or name of the state, province or district.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'postcode'   => [
                            'description' => __('Postal code.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                        'country'    => [
                            'description' => __('ISO code of the country.', 'woocommerce'),
                            'type'        => 'string',
                            'context'     => [ 'view', 'edit' ],
                        ],
                    ],
                ],
                'is_paying_customer' => [
                    'description' => __('Is the customer a paying customer?', 'woocommerce'),
                    'type'        => 'bool',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'orders_count'       => [
                    'description' => __('Quantity of orders made by the customer.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'total_spent'        => [
                    'description' => __('Total amount spent.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'avatar_url'         => [
                    'description' => __('Avatar URL.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'meta_data'          => [
                    'description' => __('Meta data.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'id'    => [
                                'description' => __('Meta ID.', 'woocommerce'),
                                'type'        => 'integer',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'key'   => [
                                'description' => __('Meta key.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                            ],
                            'value' => [
                                'description' => __('Meta value.', 'woocommerce'),
                                'type'        => 'mixed',
                                'context'     => [ 'view', 'edit' ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }
}
