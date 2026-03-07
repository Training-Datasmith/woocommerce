<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Exceptions;

/**
 * TooManyInCartException class.
 *
 * This exception is thrown when more than one of a product that can only be purchased individually is in a cart.
 */
class TooManyInCartException extends StockAvailabilityException
{
}
