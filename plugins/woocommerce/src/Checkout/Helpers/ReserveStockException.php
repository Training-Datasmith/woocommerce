<?php

declare(strict_types=1);
/**
 * Exceptions for stock reservation.
 */

namespace Automattic\WooCommerce\Checkout\Helpers;

defined('ABSPATH') || exit;

/**
 * ReserveStockException class.
 */
class ReserveStockException extends \Exception
{
    /**
     * Setup exception.
     *
     * @param string $error_code Machine-readable error code, e.g `woocommerce_invalid_product_id`.
     * @param string $message          User-friendly translated error message, e.g. 'Product ID is invalid'.
     * @param int    $http_status_code Proper HTTP status code to respond with, e.g. 400.
     * @param array $error_data Extra error data.
     */
    public function __construct(/**
     * Sanitized error code.
     */
        protected $error_code,
        $message,
        $http_status_code = 400, /**
     * Error extra data.
     */
        protected $error_data = []
    ) {
        parent::__construct($message, $http_status_code);
    }

    /**
     * Returns the error code.
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * Returns error data.
     *
     * @return array
     */
    public function getErrorData()
    {
        return $this->error_data;
    }
}
