<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Total types as defined in the Agentic Commerce Protocol.
 */
class Total_Type
{
    /**
     * Base amount of all items before discounts.
     */
    public const ITEMS_BASE_AMOUNT = 'items_base_amount';
    /**
     * Total discount on items.
     */
    public const ITEMS_DISCOUNT = 'items_discount';
    /**
     * Subtotal after item discounts.
     */
    public const SUBTOTAL = 'subtotal';
    /**
     * Additional discount applied to order.
     */
    public const DISCOUNT = 'discount';
    /**
     * Fulfillment/shipping cost.
     */
    public const FULFILLMENT = 'fulfillment';
    /**
     * Tax amount.
     */
    public const TAX = 'tax';
    /**
     * Additional fee.
     */
    public const FEE = 'fee';
    /**
     * Final total amount.
     */
    public const TOTAL = 'total';
}