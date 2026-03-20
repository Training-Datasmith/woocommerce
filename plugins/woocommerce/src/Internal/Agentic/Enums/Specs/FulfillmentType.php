<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Fulfillment types as defined in the Agentic Commerce Protocol.
 */
class Fulfillment_Type
{
    /**
     * Physical shipping.
     */
    public const SHIPPING = 'shipping';
    /**
     * Digital delivery.
     */
    public const DIGITAL = 'digital';
}