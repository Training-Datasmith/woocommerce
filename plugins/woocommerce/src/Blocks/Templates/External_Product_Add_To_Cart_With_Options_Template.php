<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

/**
 * ExternalProductAddToCartWithOptionsTemplate class.
 *
 * @internal
 */
class External_Product_Add_To_Cart_With_Options_Template extends Abstract_Template_Part
{
    /**
     * The slug of the template.
     *
     * @var string
     */
    public const SLUG = 'external-product-add-to-cart-with-options';
    /**
     * The template part area where the template part belongs.
     *
     * @var string
     */
    public $template_area = 'add-to-cart-with-options';
    /**
     * Initialization method.
     */
    public function init()
    {
    }
    /**
     * Returns the title of the template.
     *
     * @return string
     */
    public function get_template_title()
    {
        return _x('External Product Add to Cart + Options', 'Template name', 'woocommerce');
    }
    /**
     * Returns the description of the template.
     *
     * @return string
     */
    public function get_template_description()
    {
        return __('Template used to display the Add to Cart + Options form for External Products.', 'woocommerce');
    }
}