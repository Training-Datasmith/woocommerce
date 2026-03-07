<?php

declare(strict_types=1);
/**
 * WooCommerce Data Exception Class
 *
 * Extends Exception to provide additional data.
 *
 * @package WooCommerce\Classes
 * @since   3.0.0
 */

defined('ABSPATH') || exit;

/**
 * Data exception class.
 */
class WC_Data_Exception extends Exception
{
    /**
     * Error extra data.
     */
    protected array $error_data;

    /**
     * Setup exception.
     *
     * @param string $error_code Machine-readable error code, e.g `woocommerce_invalid_product_id`.
     * @param string $message          User-friendly translated error message, e.g. 'Product ID is invalid'.
     * @param int    $http_status_code Proper HTTP status code to respond with, e.g. 400.
     * @param array  $data             Extra error data.
     */
    public function __construct(/**
     * Sanitized error code.
     */
        protected $error_code,
        $message,
        $http_status_code = 400,
        $data = []
    ) {
        $this->error_data = array_merge([ 'status' => $http_status_code ], $data);

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
