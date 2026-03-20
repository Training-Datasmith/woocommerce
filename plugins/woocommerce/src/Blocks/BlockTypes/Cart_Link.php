<?php

declare (strict_types=1);
// phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

use Automattic\Woo_Commerce\Blocks\Utils\Mini_Cart_Utils;
use Automattic\Woo_Commerce\Blocks\Utils\Style_Attributes_Utils;
/**
 * CartLink class.
 */
class Cart_Link extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'cart-link';
    /**
     * Render the block.
     *
     * @param array     $attributes Block attributes.
     * @param string    $content Block content.
     * @param \WP_Block $block Block instance.
     * @return string | void Rendered block output.
     */
    protected function render($attributes, $content, $block): string
    {
        $classes_and_styles = Style_Attributes_Utils::get_classes_and_styles_by_attributes($attributes);
        $icon = Mini_Cart_Utils::get_svg_icon($attributes['cartIcon'] ?? '');
        $text = array_key_exists('content', $attributes) ? esc_html($attributes['content']) : esc_html__('Cart', 'woocommerce');
        return sprintf('<div %1$s><a class="wc-block-cart-link" href="%2$s">%3$s<span class="wc-block-cart-link__text">%4$s</span></a></div>', get_block_wrapper_attributes(['class' => esc_attr($classes_and_styles['classes']), 'style' => $classes_and_styles['styles']]), esc_url(wc_get_cart_url()), $icon, $text);
    }
    /**
     * Get the frontend script handle for this block type.
     *
     * @param string $key Data to get, or default to everything.
     */
    protected function get_block_type_script($key = null): null
    {
        return null;
    }
}