<?php

namespace Automattic\Woo_Commerce\Blocks\Block_Types;

use Automattic\Woo_Commerce\Admin\Features\Features;
/**
 * EmptyMiniCartContentsBlock class.
 */
class Empty_Mini_Cart_Contents_Block extends Abstract_Inner_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'empty-mini-cart-contents-block';
    /**
     * Render the markup for the Filled Mini-Cart Contents block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Block content.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block type output.
     */
    protected function render($attributes, $content, $block)
    {
        if (Features::is_enabled('experimental-iapi-mini-cart')) {
            return $this->render_experimental_empty_mini_cart_contents($attributes, $content, $block);
        }
        return $content;
    }
    /**
     * Render the experimental interactivity API powered Filled Mini-Cart Contents block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Block content.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block type output.
     */
    protected function render_experimental_empty_mini_cart_contents($attributes, $content, $block): string|false
    {
        $wrapper_attributes = get_block_wrapper_attributes(['data-wp-bind--aria-hidden' => '!state.cartIsEmpty', 'data-wp-bind--hidden' => '!state.cartIsEmpty', 'data-wp-interactive' => 'woocommerce/mini-cart']);
        ob_start();
        ?>
		<div <?php 
        echo $wrapper_attributes;
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>>
			<div class="wc-block-mini-cart__empty-cart-wrapper">
				<?php 
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $content;
        ?>
			</div>
		</div>
		<?php 
        return ob_get_clean();
    }
}