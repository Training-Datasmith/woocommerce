<?php

declare (strict_types=1);
/**
 * REST API Setting Options Controller
 *
 * Handles requests to /settings/{option}
 */
namespace Automattic\Woo_Commerce\Admin\API;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\API\Reports\Cache as ReportsCache;
/**
 * Setting Options controller.
 *
 * @internal
 * @extends WC_REST_Setting_Options_Controller
 */
class Setting_Options extends \WC_REST_Setting_Options_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-analytics';
    /**
     * Invalidates API cache when updating settings options.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return array Of WP_Error or WP_REST_Response.
     */
    public function batch_items($request)
    {
        // Invalidate the API cache.
        Reports_Cache::invalidate();
        // Process the request.
        return parent::batch_items($request);
    }
}