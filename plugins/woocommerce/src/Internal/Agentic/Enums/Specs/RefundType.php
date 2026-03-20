<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Refund types as defined in the Agentic Commerce Protocol.
 */
class Refund_Type
{
    /**
     * Refund to store credit.
     */
    public const STORE_CREDIT = 'store_credit';
    /**
     * Refund to original payment method.
     */
    public const ORIGINAL_PAYMENT = 'original_payment';
}