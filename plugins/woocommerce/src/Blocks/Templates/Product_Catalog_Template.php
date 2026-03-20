<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

use Automattic\Woo_Commerce\Blocks\Utils\Block_Template_Utils;
/**
 * ProductCatalogTemplate class.
 *
 * @internal
 */
class Product_Catalog_Template extends Abstract_Template
{
    /**
     * The slug of the template.
     *
     * @var string
     */
    public const SLUG = 'archive-product';
    /**
     * Initialization method.
     */
    public function init(): void
    {
        add_action('template_redirect', $this->render_block_template(...));
        add_filter('current_theme_supports-block-templates', $this->remove_block_template_support_for_shop_page(...));
    }
    /**
     * Returns the title of the template.
     *
     * @return string
     */
    public function get_template_title()
    {
        return _x('Product Catalog', 'Template name', 'woocommerce');
    }
    /**
     * Returns the description of the template.
     *
     * @return string
     */
    public function get_template_description()
    {
        return __('Displays your products.', 'woocommerce');
    }
    /**
     * Run template-specific logic when the query matches this template.
     */
    public function render_block_template(): void
    {
        if (!is_embed() && (is_post_type_archive('product') || is_page(wc_get_page_id('shop'))) && !is_search()) {
            $compatibility_layer = new Archive_Product_Templates_Compatibility();
            $compatibility_layer->init();
            $templates = get_block_templates(['slug__in' => [self::SLUG]]);
            if (isset($templates[0]) && Block_Template_Utils::template_has_legacy_template_block($templates[0])) {
                add_filter('woocommerce_disable_compatibility_layer', '__return_true');
            }
        }
    }
    /**
     * Remove the template panel from the Sidebar of the Shop page because
     * the Site Editor handles it.
     *
     * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/6278
     *
     * @param bool $is_support Whether the active theme supports block templates.
     *
     * @return bool
     */
    public function remove_block_template_support_for_shop_page($is_support)
    {
        global $pagenow, $post;
        if (is_admin() && 'post.php' === $pagenow && function_exists('wc_get_page_id') && is_a($post, 'WP_Post') && wc_get_page_id('shop') === $post->ID) {
            return false;
        }
        return $is_support;
    }
}