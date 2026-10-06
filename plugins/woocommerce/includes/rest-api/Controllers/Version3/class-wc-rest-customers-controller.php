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
 * @extends WC_REST_Customers_V2_Controller
 */
class WC_REST_Customers_Controller extends WC_REST_Customers_V2_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v3';

    /**
     * Get formatted item data.
     *
     * @param WC_Data $object WC_Data instance.
     *
     * @since  3.0.0
     * @return array
     */
    protected function get_formatted_item_data($object): array    {
        return $this->get_formatted_item_data_core($object);
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
                        'phone'      => [
                            'description' => __('Phone number.', 'woocommerce'),
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
