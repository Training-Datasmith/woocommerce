<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

use Automattic\Woo_Commerce\Blocks\Utils\Block_Template_Utils;
/**
 * ProductAttributeTemplate class.
 *
 * @internal
 */
class Product_Attribute_Template extends Abstract_Template_With_Fallback
{
    /**
     * The slug of the template.
     *
     * @var string
     */
    public const SLUG = 'taxonomy-product_attribute';
    /**
     * The template used as a fallback if that one is customized.
     */
    public string $fallback_template = Product_Catalog_Template::SLUG;
    /**
     * Returns the title of the template.
     *
     * @return string
     */
    public function get_template_title()
    {
        return _x('Products by Attribute', 'Template name', 'woocommerce');
    }
    /**
     * Returns the description of the template.
     *
     * @return string
     */
    public function get_template_description()
    {
        return __('Displays products filtered by an attribute.', 'woocommerce');
    }
    /**
     * Run template-specific logic when the query matches this template.
     */
    public function render_block_template(): void
    {
        $queried_object = get_queried_object();
        if (is_null($queried_object)) {
            return;
        }
        if (isset($queried_object->taxonomy) && taxonomy_is_product_attribute($queried_object->taxonomy)) {
            $compatibility_layer = new Archive_Product_Templates_Compatibility();
            $compatibility_layer->init();
            $templates = get_block_templates(['slug__in' => [self::SLUG]]);
            if (isset($templates[0]) && Block_Template_Utils::template_has_legacy_template_block($templates[0])) {
                add_filter('woocommerce_disable_compatibility_layer', '__return_true');
            }
        }
    }
    /**
     * Renders the Product by Attribute template for product attributes taxonomy pages.
     *
     * @param array $templates Templates that match the product attributes taxonomy.
     */
    public function template_hierarchy($templates)
    {
        $queried_object = get_queried_object();
        if (!is_null($queried_object) && taxonomy_is_product_attribute($queried_object->taxonomy) && wp_is_block_theme()) {
            // If Products by Attribute template has been customized or it's in the
            // theme, we load it first, otherwise we only load the fallback template.
            // If we don't do that, the WC core template would always have priority
            // over the fallback template.
            $slugs = [$this->fallback_template];
            if (Block_Template_Utils::theme_has_template(self::SLUG) || Block_Template_Utils::get_block_templates_from_db([self::SLUG])) {
                $slugs = [self::SLUG, $this->fallback_template];
            }
            array_splice($templates, count($templates) - 1, 0, $slugs);
        }
        return $templates;
    }
}