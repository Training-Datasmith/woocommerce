<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * Product Filter: Clear Button Block.
 */
final class Product_Filter_Clear_Button extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-filter-clear-button';
    /**
     * Get the frontend script handle for this block type.
     *
     * @param string $key Data to get, or default to everything.
     */
    protected function get_block_type_script($key = null): null
    {
        return null;
    }
    /**
     * Include and render the block.
     *
     * @param array    $attributes Block attributes. Default empty array.
     * @param string   $content    Block content. Default empty string.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block type output.
     */
    protected function render($attributes, $content, $block): string
    {
        // don't render if its admin, or ajax in progress.
        if (is_admin() || wp_doing_ajax() || empty($block->context['filterData'])) {
            return '';
        }
        $p = new \WP_HTML_Tag_Processor($content);
        if ($p->next_tag()) {
            $p->set_attribute('data-wp-on--click', 'actions.removeAllActiveFilters');
            $content = $p->get_updated_html();
        }
        $content = str_replace(['<a', '</a>'], ['<button', '</button>'], $content);
        return sprintf('<div %1$s>%2$s</div>', get_block_wrapper_attributes(), $content);
    }
}