<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types\Order_Confirmation;

use Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Fields;
use Automattic\Woo_Commerce\Blocks\Package;
/**
 * AdditionalFieldsWrapper class.
 */
class Additional_Fields_Wrapper extends Abstract_Order_Confirmation_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'order-confirmation-additional-fields-wrapper';
    /**
     * This renders the content of the downloads wrapper.
     *
     * @param \WC_Order    $order Order object.
     * @param string|false $permission If the current user can view the order details or not.
     * @param array        $attributes Block attributes.
     * @param string       $content Original block content.
     */
    protected function render_content($order, $permission = false, $attributes = [], $content = '')
    {
        if (!$permission) {
            return '';
        }
        // Contact and additional fields are currently grouped in this section.
        // If none of the additional fields for contact or order have values then the "Additional fields' section should
        // not show in the order confirmation.
        $additional_field_values = array_merge(Package::container()->get(Checkout_Fields::class)->get_order_additional_fields_with_values($order, 'contact'), Package::container()->get(Checkout_Fields::class)->get_order_additional_fields_with_values($order, 'order'));
        return empty($additional_field_values) ? '' : $content;
    }
    /**
     * Extra data passed through from server to client for block.
     *
     * @param array $attributes  Any attributes that currently are available from the block.
     *                           Note, this will be empty in the editor context when the block is
     *                           not in the post content on editor load.
     */
    protected function enqueue_data(array $attributes = [])
    {
        parent::enqueue_data($attributes);
        $this->asset_data_registry->add('additionalFields', Package::container()->get(Checkout_Fields::class)->get_fields_for_location('order'));
        $this->asset_data_registry->add('additionalContactFields', Package::container()->get(Checkout_Fields::class)->get_fields_for_location('contact'));
    }
}