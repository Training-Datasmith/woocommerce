<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Error codes for message errors as defined in the Agentic Commerce Protocol.
 */
class Error_Code
{
    /**
     * Required field is missing.
     */
    public const MISSING = 'missing';
    /**
     * Field value is invalid.
     */
    public const INVALID = 'invalid';
    /**
     * Product is out of stock.
     */
    public const OUT_OF_STOCK = 'out_of_stock';
    /**
     * Payment was declined.
     */
    public const PAYMENT_DECLINED = 'payment_declined';
    /**
     * User sign-in is required.
     */
    public const REQUIRES_SIGN_IN = 'requires_sign_in';
    /**
     * 3D Secure authentication is required.
     */
    public const REQUIRES_3DS = 'requires_3ds';
}