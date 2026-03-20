<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types\Reviews;

use Automattic\Woo_Commerce\Blocks\Block_Types\Abstract_Block;
use Automattic\Woo_Commerce\Blocks\Block_Types\Enable_Block_Json_Assets_Trait;
use Automattic\Woo_Commerce\Blocks\Utils\Style_Attributes_Utils;
/**
 * ProductReviews class.
 */
class Product_Reviews extends Abstract_Block
{
    use Enable_Block_Json_Assets_Trait;
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-reviews';
    /**
     * Render the block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content Block content.
     * @param WP_Block $block Block instance.
     *
     * @return string Rendered block output.
     */
    protected function render($attributes, $content, $block)
    {
        if (empty($block->parsed_block['innerBlocks'])) {
            return $this->render_legacy_block($attributes, $content, $block);
        }
        if (!comments_open()) {
            return '';
        }
        $p = new \WP_HTML_Tag_Processor($content);
        $p->next_tag();
        $p->set_attribute('data-wp-interactive', $this->get_full_block_name());
        $p->set_attribute('data-wp-router-region', $this->get_full_block_name());
        return $p->get_updated_html();
    }
    /**
     * Previously, the Product Reviews block was a standalone block. It doesn't
     * have any inner blocks and it rendered the tabs directly like the classic
     * template. When upgrading, we want the existing stores using the block to
     * continue working as before, so we moved the logic the legacy render
     * method here.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content Block content.
     * @param WP_Block $block Block instance.
     *
     * @return string Rendered block output.
     */
    protected function render_legacy_block($attributes, $content, $block)
    {
        if (!is_singular('product')) {
            return $content;
        }
        ob_start();
        rewind_posts();
        while (have_posts()) {
            the_post();
            comments_template();
        }
        $reviews = ob_get_clean();
        return sprintf('<div class="wp-block-woocommerce-product-reviews %1$s">
				%2$s
			</div>', Style_Attributes_Utils::get_classes_by_attributes($attributes, ['extra_classes']), $reviews);
    }
}