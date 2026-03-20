<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types\Add_To_Cart_With_Options;

use Automattic\Woo_Commerce\Blocks\Block_Types\Abstract_Block;
use Automattic\Woo_Commerce\Blocks\Block_Types\Enable_Block_Json_Assets_Trait;
use Automattic\Woo_Commerce\Blocks\Utils\Style_Attributes_Utils;
/**
 * Block type for variation selector attribute name in add to cart with options.
 * It's responsible to render the attribute name.
 */
class Variation_Selector_Attribute_Name extends Abstract_Block
{
    use Enable_Block_Json_Assets_Trait;
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'add-to-cart-with-options-variation-selector-attribute-name';
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
        if (!isset($block->context['woocommerce/attributeId'], $block->context['woocommerce/attributeName'])) {
            return '';
        }
        $attribute_id = $block->context['woocommerce/attributeId'];
        $attribute_name = $block->context['woocommerce/attributeName'];
        $classes_and_styles = Style_Attributes_Utils::get_classes_and_styles_by_attributes($attributes, [], ['extra_classes']);
        $wrapper_attributes = get_block_wrapper_attributes(['class' => esc_attr($classes_and_styles['classes']), 'for' => esc_attr($attribute_id), 'id' => esc_attr($attribute_id . '_label'), 'style' => esc_attr($classes_and_styles['styles'])]);
        $label_text = esc_html(wc_attribute_label($attribute_name));
        return sprintf('<label %s>%s</label>', $wrapper_attributes, $label_text);
    }
}