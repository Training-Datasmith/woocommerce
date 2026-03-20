<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types\Add_To_Cart_With_Options;

use Automattic\Woo_Commerce\Blocks\Block_Types\Abstract_Block;
use Automattic\Woo_Commerce\Blocks\Block_Types\Add_To_Cart_With_Options\Utils as AddToCartWithOptionsUtils;
use Automattic\Woo_Commerce\Blocks\Block_Types\Enable_Block_Json_Assets_Trait;
use WP_Block;
/**
 * Block type for the label of grouped product selector items in Add to Cart + Options.
 * It's responsible to render the label for each child product.
 */
class Grouped_Product_Item_Label extends Abstract_Block
{
    use Enable_Block_Json_Assets_Trait;
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'add-to-cart-with-options-grouped-product-item-label';
    /**
     * Render the block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content Block content.
     * @param WP_Block $block Block instance.
     * @return string Rendered block output.
     */
    protected function render($attributes, $content, $block): string
    {
        $product = Add_To_Cart_With_Options_Utils::get_product_from_context($block, $GLOBALS['product']);
        $markup = '';
        if ($product) {
            $wrapper_attributes = get_block_wrapper_attributes();
            $title = $product->get_name();
            if (!$product->is_purchasable() || $product->has_options() || !$product->is_in_stock()) {
                $markup = sprintf('<div %1$s>%2$s</div>', $wrapper_attributes, esc_html($title));
            } else {
                // Checkbox.
                $markup = sprintf('<label %1$s for="%2$s">%3$s</label>', $wrapper_attributes, esc_attr('quantity_' . $product->get_id()), esc_html($title));
            }
        }
        return $markup;
    }
}