<?php

declare (strict_types=1);
/**
 * Represents a price with a currency.
 */
namespace Automattic\Woo_Commerce\Admin\Marketing;

/**
 * Price class
 *
 * @since x.x.x
 */
class Price
{
    /**
     * Price constructor.
     *
     * @param string $value    The value of the price.
     * @param string $currency The currency of the price.
     */
    public function __construct(protected string $value, protected string $currency)
    {
    }
    /**
     * Get value of the price.
     */
    public function get_value(): string
    {
        return $this->value;
    }
    /**
     * Get the currency of the price.
     */
    public function get_currency(): string
    {
        return $this->currency;
    }
}