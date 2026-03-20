<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

use Automattic\Woo_Commerce\Blocks\Utils\Style_Attributes_Utils;
/**
 * CatalogSorting class.
 */
class Catalog_Sorting extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'catalog-sorting';
    /**
     * Render the block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content Block content.
     * @param WP_Block $block Block instance.
     *
     * @return string | void Rendered block output.
     */
    protected function render($attributes, $content, $block)
    {
        ob_start();
        woocommerce_catalog_ordering($attributes);
        $catalog_sorting = ob_get_clean();
        if (!$catalog_sorting) {
            return;
        }
        $classes_and_styles = Style_Attributes_Utils::get_classes_and_styles_by_attributes($attributes, [], ['extra_classes']);
        $wrapper_attributes = get_block_wrapper_attributes(['class' => implode(' ', array_filter(['woocommerce wc-block-catalog-sorting', esc_attr($classes_and_styles['classes'])])), 'style' => esc_attr($styles_and_classes['styles'] ?? '')]);
        return sprintf('<div %1$s>%2$s</div>', $wrapper_attributes, $catalog_sorting);
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