<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

use Automattic\Woo_Commerce\Blocks\Assets\Asset_Data_Registry;
/**
 * ClassicTemplatesCompatibility class.
 *
 * To bridge the gap on compatibility with widget blocks and classic PHP core templates.
 *
 * @internal
 */
class Classic_Templates_Compatibility
{
    /**
     * Constructor.
     *
     * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
     */
    public function __construct(protected \Automattic\Woo_Commerce\Blocks\Assets\Asset_Data_Registry $asset_data_registry)
    {
        $this->init();
    }
    /**
     * Initialization method.
     */
    protected function init()
    {
        if (!wp_is_block_theme()) {
            add_action('template_redirect', $this->set_classic_template_data(...));
            // We need to set this data on the widgets screen so the filters render previews.
            add_action('load-widgets.php', $this->set_filterable_product_data(...));
        }
    }
    /**
     * Executes the methods which set the necessary data needed for filter blocks to work correctly as widgets in Classic templates.
     */
    public function set_classic_template_data(): void
    {
        $this->set_filterable_product_data();
        $this->set_php_template_data();
    }
    /**
     * This method passes the value `has_filterable_products` to the front-end for product archive pages,
     * so that widget product filter blocks are aware of the context they are in and can render accordingly.
     */
    public function set_filterable_product_data(): void
    {
        global $pagenow;
        if (is_shop() || is_product_taxonomy() || 'widgets.php' === $pagenow) {
            $this->asset_data_registry->add('hasFilterableProducts', true);
        }
    }
    /**
     * This method passes the value `is_rendering_php_template` to the front-end of Classic themes,
     * so that widget product filter blocks are aware of how to filter the products.
     *
     * This data only matters on WooCommerce product archive pages.
     * On non-archive pages the merchant could be using the All Products block which is not a PHP template.
     */
    public function set_php_template_data(): void
    {
        if (is_shop() || is_product_taxonomy()) {
            $this->asset_data_registry->add('isRenderingPhpTemplate', true);
        }
    }
}