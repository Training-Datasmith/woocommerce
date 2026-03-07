<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Fulfillment types as defined in the Agentic Commerce Protocol.
 */
class FulfillmentType
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
