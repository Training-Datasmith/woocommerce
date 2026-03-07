<?php

declare(strict_types=1);
/**
 * REST API Layout Templates controller
 *
 * Handles requests to /layout-templates.
 *
 * @package WooCommerce\RestApi
 * @since 8.6.0
 */

defined('ABSPATH') || exit;

use Automattic\WooCommerce\LayoutTemplates\LayoutTemplateRegistry;

/**
 * REST API Layout Templates controller class.
 */
class WC_REST_Layout_Templates_Controller extends WC_REST_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v3';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'layout-templates';

    /**
     * Register the routes for template layouts.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => $this->get_items(...),
                'permission_callback' => $this->get_items_permissions_check(...),
                'args'                => [
                    'area' => [
                        'description' => __('Area to get templates for.', 'woocommerce'),
                        'type'        => 'string',
                        'default'     => '',
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>\w[\w\s\-]*)',
            [
                'args' => [
                    'id' => [
                        'description' => __('Unique identifier for the resource.', 'woocommerce'),
                        'type'        => 'string',
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => $this->get_item(...),
                    'permission_callback' => $this->get_item_permissions_check(...),
                    'args'                => [],
                ],
            ]
        );
    }

    /**
     * Check if a given request has access to read template layouts.
     *
     * @param WP_REST_Request $request The request.
     */
    public function get_items_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Check if a given request has access to read a template layout.
     *
     * @param WP_REST_Request $request The request.
     */
    public function get_item_permissions_check($request): bool
    {
        return true;
    }

    /**
     * Handle request for template layouts.
     *
     * @param WP_REST_Request $request The request.
     */
    public function get_items($request)
    {
        $layout_templates = $this->get_layout_templates(
            [
                'area' => $request['area'],
            ]
        );

        return rest_ensure_response($layout_templates);
    }

    /**
     * Handle request for a single template layout.
     *
     * @param WP_REST_Request $request The request.
     */
    public function get_item($request)
    {
        $layout_templates = $this->get_layout_templates(
            [
                'id' => $request['id'],
            ]
        );

        if (count($layout_templates) !== 1) {
            return new WP_Error('woocommerce_rest_layout_template_invalid_id', __('Invalid layout template ID.', 'woocommerce'), [ 'status' => 404 ]);
        }

        return rest_ensure_response(current($layout_templates));
    }

    /**
     * Get layout templates.
     *
     * @param array $query_params Query params.
     */
    private function get_layout_templates(array $query_params): array
    {
        $layout_template_registry = wc_get_container()->get(LayoutTemplateRegistry::class);

        return array_map(
            fn ($layout_template) => $layout_template->to_json(),
            $layout_template_registry->instantiate_layout_templates($query_params)
        );
    }
}
