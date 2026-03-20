<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * CheckoutActionsBlock class.
 */
class Checkout_Actions_Block extends Abstract_Inner_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'checkout-actions-block';
    /**
     * Initialize this block type.
     *
     * - Hook into WP lifecycle.
     * - Register the block with WordPress.
     */
    protected function initialize()
    {
        parent::initialize();
        add_action('wp_loaded', $this->register_style_variations(...));
    }
    /**
     * Register style variations for the block.
     */
    public function register_style_variations(): void
    {
        register_block_style($this->get_full_block_name(), ['name' => 'without-price', 'label' => __('Hide Price', 'woocommerce'), 'is_default' => true]);
        register_block_style($this->get_full_block_name(), ['name' => 'with-price', 'label' => __('Show Price', 'woocommerce'), 'is_default' => false]);
    }
}