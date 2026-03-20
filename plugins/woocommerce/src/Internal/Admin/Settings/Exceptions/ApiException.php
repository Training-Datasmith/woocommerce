<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Settings\Exceptions;

/**
 * ApiException class.
 */
class Api_Exception extends \Exception
{
    /**
     * Additional error data.
     */
    public array $additional_data = [];
    /**
     * Setup exception.
     *
     * @param string $error_code       Machine-readable error code, e.g `woocommerce_invalid_step_id`.
     * @param string $message          User-friendly translated error message, e.g. 'Step ID is invalid'.
     * @param int    $http_status_code Optional. Proper HTTP status code to respond with.
     *                                 Defaults to 400 (Bad request).
     * @param array  $additional_data  Optional. Extra data (key value pairs) to expose in the error response.
     *                                 Defaults to empty array.
     */
    public function __construct(
        /**
         * Sanitized error code.
         */
        public string $error_code,
        string $message,
        int $http_status_code = 400,
        array $additional_data = []
    )
    {
        $this->additional_data = array_filter($additional_data);
        parent::__construct($message, $http_status_code);
    }
    /**
     * Returns the error code.
     *
     * @return string The machine-readable error code.
     */
    public function get_error_code(): string
    {
        return $this->error_code;
    }
    /**
     * Returns additional error data.
     *
     * @return array Extra data (key value pairs).
     */
    public function get_additional_data(): array
    {
        return $this->additional_data;
    }
}