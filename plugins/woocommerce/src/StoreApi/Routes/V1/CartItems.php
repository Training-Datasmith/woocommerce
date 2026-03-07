<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * CartItems class.
 */
class CartItems extends AbstractCartRoute
{
    /**
     * The route identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'cart-items';

    /**
     * The routes schema.
     *
     * @var string
     */
    public const SCHEMA_TYPE = 'cart-item';

    /**
     * Get the path of this REST route.
     *
     * @return string
     */
    public function get_path()
    {
        return self::get_path_regex();
    }

    /**
     * Get the path of this rest route.
     */
    public static function get_path_regex(): string
    {
        return '/cart/items';
    }

    /**
     * Get method arguments for this REST route.
     *
     * @return array An array of endpoints.
     */
    public function get_args(): array
    {
        return [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => $this->get_response(...),
                'permission_callback' => '__return_true',
                'args'                => [
                    'context' => $this->get_context_param([ 'default' => 'view' ]),
                ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => $this->get_response(...),
                'permission_callback' => '__return_true',
                'args'                => $this->schema->get_endpoint_args_for_item_schema(\WP_REST_Server::CREATABLE),
            ],
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => $this->get_response(...),
                'permission_callback' => '__return_true',
            ],
            'schema'      => $this->schema->get_public_item_schema(...),
            'allow_batch' => [ 'v1' => true ],
        ];
    }

    /**
     * Get a collection of cart items.
     *
     * @throws RouteException On error.
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    protected function get_route_response(\WP_REST_Request $request)
    {
        $cart_items = $this->cart_controller->get_cart_items();
        $items      = [];

        foreach ($cart_items as $cart_item) {
            $data    = $this->prepare_item_for_response($cart_item, $request);
            $items[] = $this->prepare_response_for_collection($data);
        }

        return rest_ensure_response($items);
    }

    /**
     * Creates one item from the collection.
     *
     * @throws RouteException On error.
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    protected function get_route_post_response(\WP_REST_Request $request)
    {
        // Do not allow key to be specified during creation.
        if (! empty($request['key'])) {
            throw new RouteException('woocommerce_rest_cart_item_exists', __('Cannot create an existing cart item.', 'woocommerce'), 400);
        }

        $result = $this->cart_controller->add_to_cart(
            [
                'id'        => $request['id'],
                'quantity'  => $request['quantity'],
                'variation' => $request['variation'],
            ]
        );

        $response = rest_ensure_response($this->prepare_item_for_response($this->cart_controller->get_cart_item($result), $request));
        $response->set_status(201);
        return $response;
    }

    /**
     * Deletes all items in the cart.
     *
     * @throws RouteException On error.
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    protected function get_route_delete_response(\WP_REST_Request $request)
    {
        $this->cart_controller->empty_cart();
        return new \WP_REST_Response([], 200);
    }

    /**
     * Prepare links for the request.
     *
     * @param array            $cart_item Object to prepare.
     * @param \WP_REST_Request $request Request object.
     */
    protected function prepare_links($cart_item, $request): array
    {
        $base  = $this->get_namespace() . $this->get_path();
        return [
            'self'       => [
                'href' => rest_url(trailingslashit($base) . $cart_item['key']),
            ],
            'collection' => [
                'href' => rest_url($base),
            ],
        ];
    }
}
