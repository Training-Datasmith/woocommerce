<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1\Agentic;

use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\ErrorType;
use WP_REST_Response;

/**
 * Error class.
 *
 * Represents an error object as defined in the Agentic Commerce Protocol.
 * This class handles API-level errors with type, code, message, and optional param.
 */
class Error
{
    /**
     * Constructor.
     *
     * @param string      $type    Error type from ErrorType enum.
     * @param string      $code    Implementation-defined error code.
     * @param string      $message Human-readable error message.
     * @param string|null $param   RFC 9535 JSONPath (optional).
     */
    private function __construct(
        /**
         * The error type.
         */
        private $type,
        /**
         * Implementation-defined error code.
         */
        private $code,
        /**
         * Human-readable error message.
         */
        private $message,
        /**
         * RFC 9535 JSONPath to the problematic parameter (optional).
         */
        private $param = null
    ) {
    }

    /**
     * Create an invalid request error.
     *
     * @param string      $code    Implementation-defined error code.
     * @param string      $message Human-readable error message.
     * @param string|null $param   RFC 9535 JSONPath (optional).
     * @return Error
     */
    public static function invalid_request($code, $message, $param = null): self
    {
        return new self(ErrorType::INVALID_REQUEST, $code, $message, $param);
    }

    /**
     * Create a request not idempotent error.
     *
     * @param string      $code    Implementation-defined error code.
     * @param string      $message Human-readable error message.
     * @param string|null $param   RFC 9535 JSONPath (optional).
     * @return Error
     */
    public static function request_not_idempotent($code, $message, $param = null): self
    {
        return new self(ErrorType::REQUEST_NOT_IDEMPOTENT, $code, $message, $param);
    }

    /**
     * Create a processing error.
     *
     * @param string      $code    Implementation-defined error code.
     * @param string      $message Human-readable error message.
     * @param string|null $param   RFC 9535 JSONPath (optional).
     * @return Error
     */
    public static function processing_error($code, $message, $param = null): self
    {
        return new self(ErrorType::PROCESSING_ERROR, $code, $message, $param);
    }

    /**
     * Create a service unavailable error.
     *
     * @param string      $code    Implementation-defined error code.
     * @param string      $message Human-readable error message.
     * @param string|null $param   RFC 9535 JSONPath (optional).
     * @return Error
     */
    public static function service_unavailable($code, $message, $param = null): self
    {
        return new self(ErrorType::SERVICE_UNAVAILABLE, $code, $message, $param);
    }

    /**
     * Convert the error to a WP_REST_Response.
     *
     * @return WP_REST_Response WordPress REST API response object
     */
    public function to_rest_response()
    {
        $data = [
            'type'    => $this->type,
            'code'    => $this->code,
            'message' => $this->message,
        ];

        if (null !== $this->param) {
            $data['param'] = $this->param;
        }

        $status_code = $this->get_http_status_code();

        return new WP_REST_Response($data, $status_code);
    }

    /**
     * Determine HTTP status code based on error type.
     *
     * @return int HTTP status code
     */
    private function get_http_status_code(): int
    {
        return match ($this->type) {
            ErrorType::INVALID_REQUEST => 400,
            ErrorType::REQUEST_NOT_IDEMPOTENT => 409,
            ErrorType::PROCESSING_ERROR => 500,
            ErrorType::SERVICE_UNAVAILABLE => 503,
            default => 500,
        };
    }
}
