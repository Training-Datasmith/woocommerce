<?php

declare (strict_types=1);
/**
 * WooCommerce Async Product Editor Category Field.
 */
namespace Automattic\Woo_Commerce\Admin\Features\Async_Product_Editor_Category_Field;

use Automattic\Jetpack\Constants;
use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Admin\Page_Controller;
use Automattic\Woo_Commerce\Internal\Admin\Wc_Admin_Assets;
/**
 * Loads assets related to the async category field for the product editor.
 */
class Init
{
    public const FEATURE_ID = 'async-product-editor-category-field';
    /**
     * Constructor
     */
    public function __construct()
    {
        if (Features::is_enabled(self::FEATURE_ID)) {
            add_action('admin_enqueue_scripts', $this->enqueue_styles(...));
            add_action('admin_enqueue_scripts', $this->enqueue_scripts(...));
            add_filter('woocommerce_taxonomy_args_product_cat', $this->add_metabox_args(...));
        }
    }
    /**
     * Adds meta_box_cb callback arguments for custom metabox.
     *
     * @param array $args Category taxonomy args.
     * @return array $args category taxonomy args.
     */
    public function add_metabox_args(array $args): array
    {
        if (!isset($args['meta_box_cb'])) {
            $args['meta_box_cb'] = 'WC_Meta_Box_Product_Categories::output';
            $args['meta_box_sanitize_cb'] = 'taxonomy_meta_box_sanitize_cb_checkboxes';
        }
        return $args;
    }
    /**
     * Enqueue scripts needed for the product form block editor.
     */
    public function enqueue_scripts(): void
    {
        if (!Page_Controller::is_embed_page()) {
            return;
        }
        Wc_Admin_Assets::register_script('wp-admin-scripts', 'product-category-metabox', true);
        wp_localize_script('wc-admin-product-category-metabox', 'wc_product_category_metabox_params', ['search_categories_nonce' => wp_create_nonce('search-categories'), 'search_taxonomy_terms_nonce' => wp_create_nonce('search-taxonomy-terms')]);
        wp_enqueue_script('product-category-metabox');
    }
    /**
     * Enqueue styles needed for the rich text editor.
     */
    public function enqueue_styles(): void
    {
        if (!Page_Controller::is_embed_page()) {
            return;
        }
        $version = Constants::get_constant('WC_VERSION');
        wp_register_style('woocommerce_admin_product_category_metabox_styles', Wc_Admin_Assets::get_url('product-category-metabox/style', 'css'), [], $version);
        wp_style_add_data('woocommerce_admin_product_category_metabox_styles', 'rtl', 'replace');
        wp_enqueue_style('woocommerce_admin_product_category_metabox_styles');
    }
}