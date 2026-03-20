<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * CategoryDescription block: renders the current term description using context.
 */
class Category_Description extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'category-description';
    /**
     * Render the block.
     *
     * @param array     $attributes Block attributes.
     * @param string    $content    Block content.
     * @param \WP_Block $block     Block instance.
     *
     * @return string Rendered block output.
     */
    protected function render($attributes, $content, $block): string
    {
        $term_id = $block->context['termId'] ?? 0;
        $term_taxonomy = $block->context['termTaxonomy'] ?? 'product_cat';
        $text_align = isset($attributes['textAlign']) ? sanitize_key($attributes['textAlign']) : '';
        if (!$term_id) {
            return '';
        }
        $term = get_term($term_id, $term_taxonomy);
        if (!$term || is_wp_error($term)) {
            return '';
        }
        $description = $term->description;
        if (empty(trim((string) $description))) {
            return '';
        }
        $classes = [];
        if ($text_align) {
            $classes[] = 'has-text-align-' . $text_align;
        }
        $wrapper_attributes = get_block_wrapper_attributes(['class' => implode(' ', $classes)]);
        return sprintf('<div %1$s>%2$s</div>', $wrapper_attributes, wp_kses_post(wc_format_content($description)));
    }
    /**
     * Register the context used by this block.
     */
    protected function get_block_type_uses_context(): array
    {
        return ['termId', 'termTaxonomy'];
    }
    /**
     * Disable the style handle for this block.
     */
    protected function get_block_type_style(): null
    {
        return null;
    }
}