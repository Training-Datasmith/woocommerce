<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Error types as defined in the Agentic Commerce Protocol.
 */
class Error_Type
{
    /**
     * Invalid request.
     */
    public const INVALID_REQUEST = 'invalid_request';
    /**
     * Request not idempotent.
     */
    public const REQUEST_NOT_IDEMPOTENT = 'request_not_idempotent';
    /**
     * Processing error.
     */
    public const PROCESSING_ERROR = 'processing_error';
    /**
     * Service unavailable.
     */
    public const SERVICE_UNAVAILABLE = 'service_unavailable';
}