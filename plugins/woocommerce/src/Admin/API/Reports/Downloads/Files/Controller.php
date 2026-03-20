<?php

declare (strict_types=1);
/**
 * REST API Reports downloads files controller
 *
 * Handles requests to the /reports/downloads/files endpoint.
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports\Downloads\Files;

defined('ABSPATH') || exit;
/**
 * REST API Reports downloads files controller class.
 *
 * @internal
 * @extends WC_REST_Reports_Controller
 */
class Controller extends \WC_REST_Reports_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-analytics';
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/downloads/files';
}