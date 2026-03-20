<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

use Automattic\Woo_Commerce\Blocks\Utils\Block_Template_Utils;
/**
 * ProductCategoryTemplate class.
 *
 * @internal
 */
class Product_Category_Template extends Abstract_Template_With_Fallback
{
    /**
     * The slug of the template.
     *
     * @var string
     */
    public const SLUG = 'taxonomy-product_cat';
    /**
     * The template used as a fallback if that one is customized.
     */
    public string $fallback_template = Product_Catalog_Template::SLUG;
    /**
     * Whether this is a taxonomy template.
     */
    public bool $is_taxonomy_template = true;
    /**
     * Returns the title of the template.
     *
     * @return string
     */
    public function get_template_title()
    {
        return _x('Products by Category', 'Template name', 'woocommerce');
    }
    /**
     * Returns the description of the template.
     *
     * @return string
     */
    public function get_template_description()
    {
        return __('Displays products filtered by a category.', 'woocommerce');
    }
    /**
     * Run template-specific logic when the query matches this template.
     */
    public function render_block_template(): void
    {
        if (!is_embed() && is_product_taxonomy() && is_tax('product_cat')) {
            $compatibility_layer = new Archive_Product_Templates_Compatibility();
            $compatibility_layer->init();
            $templates = get_block_templates(['slug__in' => [self::SLUG]]);
            if (isset($templates[0]) && Block_Template_Utils::template_has_legacy_template_block($templates[0])) {
                add_filter('woocommerce_disable_compatibility_layer', '__return_true');
            }
            add_filter('woocommerce_has_block_template', '__return_true', 10, 0);
        }
    }
}