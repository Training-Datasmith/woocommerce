<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * ProductSearch class.
 */
class Product_Search extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-search';
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
     * Render the block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content    Block content.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block type output.
     */
    protected function render($attributes, $content, $block): string
    {
        static $instance_id = 0;
        $attributes = wp_parse_args($attributes, ['hasLabel' => true, 'align' => '', 'className' => '', 'label' => __('Search', 'woocommerce'), 'placeholder' => __('Search products…', 'woocommerce')]);
        /**
         * Product Search event.
         *
         * Listens for product search form submission, and on submission fires a WP Hook named
         * `experimental__woocommerce_blocks-product-search`. This can be used by tracking extensions such as Google
         * Analytics to track searches.
         */
        $this->asset_api->add_inline_script('wp-hooks', "\n\t\t\twindow.addEventListener( 'DOMContentLoaded', () => {\n\t\t\t\tconst forms = document.querySelectorAll( '.wc-block-product-search form' );\n\n\t\t\t\tfor ( const form of forms ) {\n\t\t\t\t\tform.addEventListener( 'submit', ( event ) => {\n\t\t\t\t\t\tconst field = form.querySelector( '.wc-block-product-search__field' );\n\n\t\t\t\t\t\tif ( field && field.value ) {\n\t\t\t\t\t\t\twp.hooks.doAction( 'experimental__woocommerce_blocks-product-search', { event: event, searchTerm: field.value } );\n\t\t\t\t\t\t}\n\t\t\t\t\t} );\n\t\t\t\t}\n\t\t\t} );\n\t\t\t");
        $input_id = 'wc-block-search__input-' . ++$instance_id;
        $wrapper_attributes = get_block_wrapper_attributes(['class' => implode(' ', array_filter(['wc-block-product-search', $attributes['align'] ? 'align' . $attributes['align'] : '']))]);
        $label_markup = $attributes['hasLabel'] ? sprintf('<label for="%s" class="wc-block-product-search__label">%s</label>', esc_attr($input_id), esc_html($attributes['label'])) : sprintf('<label for="%s" class="wc-block-product-search__label screen-reader-text">%s</label>', esc_attr($input_id), esc_html($attributes['label']));
        $input_markup = sprintf('<input type="search" id="%s" class="wc-block-product-search__field" placeholder="%s" name="s" />', esc_attr($input_id), esc_attr($attributes['placeholder']));
        $button_markup = sprintf('<button type="submit" class="wc-block-product-search__button" aria-label="%s">
				<svg aria-hidden="true" role="img" focusable="false" class="dashicon dashicons-arrow-right-alt2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
					<path d="M6 15l5-5-5-5 1-2 7 7-7 7z" />
				</svg>
			</button>', esc_attr__('Search', 'woocommerce'));
        $field_markup = '
			<div class="wc-block-product-search__fields">
				' . $input_markup . $button_markup . '
				<input type="hidden" name="post_type" value="product" />
			</div>
		';
        return sprintf('<div %s><form role="search" method="get" action="%s">%s</form></div>', $wrapper_attributes, esc_url(home_url('/')), $label_markup . $field_markup);
    }
}