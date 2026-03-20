<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types\Add_To_Cart_With_Options;

use Automattic\Woo_Commerce\Blocks\Block_Types\Abstract_Block;
use Automattic\Woo_Commerce\Blocks\Block_Types\Enable_Block_Json_Assets_Trait;
use Automattic\Woo_Commerce\Enums\Product_Type;
/**
 * Block type for grouped product selector in add to cart with options.
 */
class Grouped_Product_Selector extends Abstract_Block
{
    use Enable_Block_Json_Assets_Trait;
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'add-to-cart-with-options-grouped-product-selector';
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
        global $product;
        if ($product instanceof \WC_Product && $product->is_type(Product_Type::GROUPED)) {
            $p = new \WP_HTML_Tag_Processor($content);
            if ($p->next_tag()) {
                $p->set_attribute('data-wp-init', 'callbacks.validateQuantities');
            }
            return $p->get_updated_html();
        }
        return '';
    }
}