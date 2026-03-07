<?php

declare(strict_types=1);
/**
 * REST API Product Attribute Terms controller
 *
 * Handles requests to the products/attributes/<attribute_id>/terms endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST API Product Attribute Terms controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Terms_Controller
 */
class WC_REST_Product_Attribute_Terms_V1_Controller extends WC_REST_Terms_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'products/attributes/(?P<attribute_id>[\d]+)/terms';

    /**
     * Register the routes for terms.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
            'args' => [
                'attribute_id' => [
                    'description' => __('Unique identifier for the attribute of the terms.', 'woocommerce'),
                    'type'        => 'integer',
                ],
            ],
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
                'args'                => array_merge($this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE), [
                    'name' => [
                        'type'        => 'string',
                        'description' => __('Name for the resource.', 'woocommerce'),
                        'required'    => true,
                    ],
                ]),
            ],
            'schema' => [ $this, 'get_public_item_schema' ],
        ]
        );

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            'args' => [
                'id' => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                ],
                'attribute_id' => [
                    'description' => __('Unique identifier for the attribute of the terms.', 'woocommerce'),
                    'type'        => 'integer',
                ],
            ],
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => $this->get_item(...),
                'permission_callback' => $this->get_item_permissions_check(...),
                'args'                => [
                    'context'         => $this->get_context_param([ 'default' => 'view' ]),
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
            'schema' => [ $this, 'get_public_item_schema' ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/batch', [
            'args' => [
                'attribute_id' => [
                    'description' => __('Unique identifier for the attribute of the terms.', 'woocommerce'),
                    'type'        => 'integer',
                ],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => $this->batch_items(...),
                'permission_callback' => $this->batch_items_permissions_check(...),
                'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ],
            'schema' => $this->get_public_batch_schema(...),
        ]);
    }

    /**
     * Prepare a single product attribute term output for response.
     *
     * @param WP_Term $item Term object.
     * @param WP_REST_Request $request
     * @return WP_REST_Response $response
     */
    public function prepare_item_for_response($item, $request)
    {
        // Get term order.
        $menu_order = get_term_meta($item->term_id, 'order_' . $this->taxonomy, true);

        $data = [
            'id'          => (int) $item->term_id,
            'name'        => $item->name,
            'slug'        => $item->slug,
            'description' => $item->description,
            'menu_order'  => (int) $menu_order,
            'count'       => (int) $item->count,
        ];

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object($data, $request);
        $data    = $this->filter_response_by_context($data, $context);

        $response = rest_ensure_response($data);

        $response->add_links($this->prepare_links($item, $request));

        /**
         * Filter a term item returned from the API.
         *
         * Allows modification of the term data right before it is returned.
         *
         * @param WP_REST_Response  $response  The response object.
         * @param object            $item      The original term object.
         * @param WP_REST_Request   $request   Request used to generate the response.
         */
        return apply_filters("woocommerce_rest_prepare_{$this->taxonomy}", $response, $item, $request);
    }

    /**
     * Update term meta fields.
     *
     * @param WP_Term $term
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    protected function update_term_meta_fields($term, $request): bool
    {
        $id = (int) $term->term_id;

        update_term_meta($id, 'order_' . $this->taxonomy, $request['menu_order']);

        return true;
    }

    /**
     * Get the Attribute Term's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'product_attribute_term',
            'type'                 => 'object',
            'properties'           => [
                'id' => [
                    'description' => __('Unique identifier for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'name' => [
                    'description' => __('Term name.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'slug' => [
                    'description' => __('An alphanumeric identifier for the resource unique to its type.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'sanitize_title',
                    ],
                ],
                'description' => [
                    'description' => __('HTML description of the resource.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'arg_options' => [
                        'sanitize_callback' => 'wp_filter_post_kses',
                    ],
                ],
                'menu_order' => [
                    'description' => __('Menu order, used to custom sort the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                ],
                'count' => [
                    'description' => __('Number of published products for the resource.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }
}
