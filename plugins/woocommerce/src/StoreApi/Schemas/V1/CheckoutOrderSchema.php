<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * CheckoutOrderSchema class.
 */
class CheckoutOrderSchema extends CheckoutSchema
{
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'checkout-order';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'checkout-order';

    /**
     * Checkout schema properties.
     *
     * @return array
     */
    public function get_properties(): array
    {
        $parent_properties = parent::get_properties();
        unset($parent_properties['create_account']);
        return $parent_properties;
    }
}
