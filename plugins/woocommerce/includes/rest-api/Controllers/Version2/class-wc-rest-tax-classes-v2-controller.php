<?php

declare(strict_types=1);
/**
 * REST API Tax Classes controller
 *
 * Handles requests to the /taxes/classes endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined('ABSPATH') || exit;

/**
 * REST API Tax Classes controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Tax_Classes_V1_Controller
 */
class WC_REST_Tax_Classes_V2_Controller extends WC_REST_Tax_Classes_V1_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v2';

    /**
     * Register the routes for tax classes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
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
                    'args'                => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<slug>\w[\w\s\-]*)',
            [
                'args' => [
                    'slug' => [
                        'description' => __('Unique slug for the resource.', 'woocommerce'),
                        'type'        => 'string',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => $this->get_item(...),
                    'permission_callback' => $this->get_items_permissions_check(...),
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
            ]
        );
    }

    /**
     * Get one tax class.
     *
     * @param WP_REST_Request $request Request object.
     * @return array
     */
    public function get_item($request)
    {
        if ('standard' === $request['slug']) {
            $tax_class = [
                'slug' => 'standard',
                'name' => __('Standard rate', 'woocommerce'),
            ];
        } else {
            $tax_class = WC_Tax::get_tax_class_by('slug', sanitize_title($request['slug']));
        }

        if (! $tax_class) {
            return new WP_Error('woocommerce_rest_tax_class_invalid_slug', __('Invalid slug.', 'woocommerce'), [ 'status' => 404 ]);
        }

        $data   = [];
        $class  = $this->prepare_item_for_response($tax_class, $request);
        $class  = $this->prepare_response_for_collection($class);
        $data[] = $class;

        return rest_ensure_response($data);
    }
}
