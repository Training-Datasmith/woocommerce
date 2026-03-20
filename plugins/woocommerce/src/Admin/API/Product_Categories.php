<?php

declare (strict_types=1);
/**
 * REST API Product Categories Controller
 *
 * Handles requests to /products/categories.
 */
namespace Automattic\Woo_Commerce\Admin\API;

defined('ABSPATH') || exit;
/**
 * Product categories controller.
 *
 * @internal
 * @extends WC_REST_Product_Categories_Controller
 */
class Product_Categories extends \WC_REST_Product_Categories_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-analytics';
}