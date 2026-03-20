<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types\Order_Confirmation;

/**
 * ShippingWrapper class.
 */
class Shipping_Wrapper extends Abstract_Order_Confirmation_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'order-confirmation-shipping-wrapper';
    /**
     * This renders the content of the shipping wrapper.
     *
     * @param \WC_Order    $order Order object.
     * @param string|false $permission If the current user can view the order details or not.
     * @param array        $attributes Block attributes.
     * @param string       $content Original block content.
     */
    protected function render_content($order, $permission = false, $attributes = [], $content = '')
    {
        if (!$order || !$order->has_shipping_address() || !$order->needs_shipping_address() || !$permission) {
            return '';
        }
        return $content;
    }
    /**
     * Get the frontend style handle for this block type.
     */
    protected function get_block_type_style(): null
    {
        return null;
    }
}