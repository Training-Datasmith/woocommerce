<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

use Automattic\Woo_Commerce\Blocks\Utils\Block_Template_Utils;
/**
 * ProductSearchResultsTemplate class.
 *
 * @internal
 */
class Product_Search_Results_Template extends Abstract_Template
{
    /**
     * The slug of the template.
     *
     * @var string
     */
    public const SLUG = 'product-search-results';
    /**
     * Initialization method.
     */
    public function init(): void
    {
        add_action('template_redirect', $this->render_block_template(...));
        add_filter('search_template_hierarchy', $this->update_search_template_hierarchy(...), 10, 3);
    }
    /**
     * Returns the title of the template.
     *
     * @return string
     */
    public function get_template_title()
    {
        return _x('Product Search Results', 'Template name', 'woocommerce');
    }
    /**
     * Returns the description of the template.
     *
     * @return string
     */
    public function get_template_description()
    {
        return __('Displays search results for your store.', 'woocommerce');
    }
    /**
     * Run template-specific logic when the query matches this template.
     */
    public function render_block_template(): void
    {
        if (!is_embed() && is_post_type_archive('product') && is_search()) {
            $compatibility_layer = new Archive_Product_Templates_Compatibility();
            $compatibility_layer->init();
            $templates = get_block_templates(['slug__in' => [self::SLUG]]);
            if (isset($templates[0]) && Block_Template_Utils::template_has_legacy_template_block($templates[0])) {
                add_filter('woocommerce_disable_compatibility_layer', '__return_true');
            }
        }
    }
    /**
     * When the search is for products and a block theme is active, render the Product Search Template.
     *
     * @param array $templates Templates that match the search hierarchy.
     */
    public function update_search_template_hierarchy($templates)
    {
        if (is_search() && is_post_type_archive('product') && wp_is_block_theme()) {
            array_unshift($templates, self::SLUG);
        }
        return $templates;
    }
}