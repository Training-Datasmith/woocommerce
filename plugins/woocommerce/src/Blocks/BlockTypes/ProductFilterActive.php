<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * Product Filter: Active Block.
 */
final class Product_Filter_Active extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-filter-active';
    /**
     * Render the block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Block content.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block type output.
     */
    protected function render($attributes, $content, $block)
    {
        if (!isset($block->context['activeFilters'])) {
            return $content;
        }
        $active_filters = $block->context['activeFilters'];
        $filter_context = ['items' => $active_filters];
        $wrapper_attributes = ['data-wp-interactive' => 'woocommerce/product-filters', 'data-wp-key' => wp_unique_prefixed_id($this->get_full_block_name()), 'data-wp-context' => wp_json_encode(['filterType' => 'active'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), 'data-wp-bind--hidden' => '!state.hasActiveFilters', 'data-wp-class--wc-block-product-filter--hidden' => '!state.hasActiveFilters'];
        wp_interactivity_state('woocommerce/product-filters', ['hasActiveFilters' => !empty($active_filters)]);
        wp_interactivity_config('woocommerce/product-filters', [
            /* translators:  {{label}} is the label of the active filter item. */
            'removeLabelTemplate' => __('Remove filter: {{label}}', 'woocommerce'),
        ]);
        return sprintf('<div %1$s>%2$s</div>', get_block_wrapper_attributes($wrapper_attributes), array_reduce($block->parsed_block['innerBlocks'], fn(string $carry, $parsed_block): string => $carry . (new \WP_Block($parsed_block, ['filterData' => $filter_context]))->render(), ''));
    }
    /**
     * Get the frontend style handle for this block type.
     */
    protected function get_block_type_style(): null
    {
        return null;
    }
    /**
     * Disable the editor style handle for this block type.
     */
    protected function get_block_type_editor_style(): null
    {
        return null;
    }
    /**
     * Disable the script handle for this block type. We use block.json to load the script.
     *
     * @param string|null $key The key of the script to get.
     */
    protected function get_block_type_script($key = null): null
    {
        return null;
    }
}