<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema;
use Automattic\WooCommerce\StoreApi\Utilities\OrderAuthorizationTrait;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;

/**
 * Order class.
 */
class Order extends AbstractRoute
{
    use OrderAuthorizationTrait;

    /**
     * The route identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'order';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const SCHEMA_TYPE = 'order';

    /**
     * Order controller class instance.
     */
    protected \Automattic\WooCommerce\StoreApi\Utilities\OrderController $order_controller;

    /**
     * Constructor.
     *
     * @param SchemaController $schema_controller Schema Controller instance.
     * @param AbstractSchema   $schema Schema class for this route.
     */
    public function __construct(SchemaController $schema_controller, AbstractSchema $schema)
    {
        parent::__construct($schema_controller, $schema);
        $this->order_controller = new OrderController();
    }

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
        return '/order/(?P<id>[\d]+)';
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
                'permission_callback' => $this->is_authorized(...),
                'args'                => [
                    'context' => $this->get_context_param([ 'default' => 'view' ]),
                ],
            ],
            'schema' => $this->schema->get_public_item_schema(...),
        ];
    }

    /**
     * Handle the request and return a valid response for this endpoint.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    protected function get_route_response(\WP_REST_Request $request)
    {
        $order_id = absint($request['id']);
        return rest_ensure_response($this->schema->get_item_response(wc_get_order($order_id)));
    }
}
