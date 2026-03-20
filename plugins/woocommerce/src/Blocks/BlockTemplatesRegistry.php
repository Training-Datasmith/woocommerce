<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks;

use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Blocks\Templates\Abstract_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Abstract_Template_Part;
use Automattic\Woo_Commerce\Blocks\Templates\Cart_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Checkout_Header_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Checkout_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Coming_Soon_Template;
use Automattic\Woo_Commerce\Blocks\Templates\External_Product_Add_To_Cart_With_Options_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Grouped_Product_Add_To_Cart_With_Options_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Mini_Cart_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Order_Confirmation_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Product_Attribute_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Product_Brand_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Product_Catalog_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Product_Category_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Product_Search_Results_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Product_Tag_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Simple_Product_Add_To_Cart_With_Options_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Single_Product_Template;
use Automattic\Woo_Commerce\Blocks\Templates\Variable_Product_Add_To_Cart_With_Options_Template;
use Automattic\Woo_Commerce\Blocks\Utils\Block_Template_Utils;
use Automattic\Woo_Commerce\Enums\Product_Type;
/**
 * BlockTemplatesRegistry class.
 *
 * @internal
 */
class Block_Templates_Registry
{
    /**
     * The array of registered templates.
     *
     * @var AbstractTemplate[]|AbstractTemplatePart[]
     */
    private array $templates = [];
    /**
     * Initialization method.
     */
    public function init(): void
    {
        if (Block_Template_Utils::supports_block_templates('wp_template')) {
            $templates = [Product_Catalog_Template::SLUG => new Product_Catalog_Template(), Product_Category_Template::SLUG => new Product_Category_Template(), Product_Tag_Template::SLUG => new Product_Tag_Template(), Product_Attribute_Template::SLUG => new Product_Attribute_Template(), Product_Brand_Template::SLUG => new Product_Brand_Template(), Product_Search_Results_Template::SLUG => new Product_Search_Results_Template(), Cart_Template::SLUG => new Cart_Template(), Checkout_Template::SLUG => new Checkout_Template(), Order_Confirmation_Template::SLUG => new Order_Confirmation_Template(), Single_Product_Template::SLUG => new Single_Product_Template()];
        } else {
            $templates = [];
        }
        if (Features::is_enabled('launch-your-store')) {
            $templates[Coming_Soon_Template::SLUG] = new Coming_Soon_Template();
        }
        if (Block_Template_Utils::supports_block_templates('wp_template_part')) {
            $template_parts = [Mini_Cart_Template::SLUG => new Mini_Cart_Template(), Checkout_Header_Template::SLUG => new Checkout_Header_Template()];
            if (wp_is_block_theme()) {
                $product_types = wc_get_product_types();
                if (count($product_types) > 0) {
                    add_filter('default_wp_template_part_areas', $this->register_add_to_cart_with_options_template_part_area(...), 10, 1);
                    if (array_key_exists(Product_Type::SIMPLE, $product_types)) {
                        $template_parts[Simple_Product_Add_To_Cart_With_Options_Template::SLUG] = new Simple_Product_Add_To_Cart_With_Options_Template();
                    }
                    if (array_key_exists(Product_Type::EXTERNAL, $product_types)) {
                        $template_parts[External_Product_Add_To_Cart_With_Options_Template::SLUG] = new External_Product_Add_To_Cart_With_Options_Template();
                    }
                    if (array_key_exists(Product_Type::VARIABLE, $product_types)) {
                        $template_parts[Variable_Product_Add_To_Cart_With_Options_Template::SLUG] = new Variable_Product_Add_To_Cart_With_Options_Template();
                    }
                    if (array_key_exists(Product_Type::GROUPED, $product_types)) {
                        $template_parts[Grouped_Product_Add_To_Cart_With_Options_Template::SLUG] = new Grouped_Product_Add_To_Cart_With_Options_Template();
                    }
                }
            }
        } else {
            $template_parts = [];
        }
        // Init all templates.
        foreach ($templates as $template) {
            $template->init();
            // Taxonomy templates are registered automatically by WordPress and
            // are made available through the Add Template menu.
            if (!$template->is_taxonomy_template) {
                $directory = Block_Template_Utils::get_templates_directory('wp_template');
                $template_file_path = $directory . '/' . $template::SLUG . '.html';
                register_block_template('woocommerce//' . $template::SLUG, [
                    'title' => $template->get_template_title(),
                    'description' => $template->get_template_description(),
                    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                    'content' => file_get_contents($template_file_path),
                ]);
            }
        }
        foreach ($template_parts as $template_part) {
            $template_part->init();
        }
        $this->templates = array_merge($templates, $template_parts);
    }
    /**
     * Add Add to Cart + Options to the default template part areas.
     *
     * @param array $default_area_definitions An array of supported area objects.
     * @return array The supported template part areas including the Add to Cart + Options one.
     */
    public function register_add_to_cart_with_options_template_part_area($default_area_definitions): array
    {
        $add_to_cart_with_options_template_part_area = ['area' => 'add-to-cart-with-options', 'label' => __('Add to Cart + Options', 'woocommerce'), 'description' => __('The Add to Cart + Options templates allow defining a different layout for each product type.', 'woocommerce'), 'icon' => 'add-to-cart-with-options', 'area_tag' => 'add-to-cart-with-options'];
        return array_merge($default_area_definitions, [$add_to_cart_with_options_template_part_area]);
    }
    /**
     * Returns the template matching the slug
     *
     * @param string $template_slug Slug of the template to retrieve.
     */
    public function get_template($template_slug): ?\Automattic\Woo_Commerce\Blocks\Templates\Abstract_Template
    {
        if (array_key_exists($template_slug, $this->templates)) {
            return $this->templates[$template_slug];
        }
        return null;
    }
}