<?php

declare(strict_types=1);
/**
 * REST API Features Controller
 *
 * Handles requests to /features
 */

namespace Automattic\WooCommerce\Admin\API;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Admin\Features\Features as FeaturesClass;

/**
 * Features Controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Features extends \WC_REST_Data_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-admin';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'features';

    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => $this->get_features(...),
                    'permission_callback' => $this->get_items_permissions_check(...),
                ],
                'schema' => [ $this, 'get_public_item_schema' ],
            ]
        );
    }

    /**
     * Check whether a given request has permission to read onboarding profile data.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request): \WP_Error|true
    {
        if (! wc_rest_check_manager_permissions('settings', 'read')) {
            return new \WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot list resources.', 'woocommerce'), [ 'status' => rest_authorization_required_code() ]);
        }

        return true;
    }

    /**
     * Return available payment methods.
     *
     * @param \WP_REST_Request $request Request data.
     *
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_features($request)
    {
        return FeaturesClass::get_available_features();
    }

}
