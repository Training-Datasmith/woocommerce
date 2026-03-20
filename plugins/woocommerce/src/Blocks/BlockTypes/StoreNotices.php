<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

use Automattic\Woo_Commerce\Blocks\Utils\Style_Attributes_Utils;
/**
 * StoreNotices class.
 */
class Store_Notices extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'store-notices';
    /**
     * Render the block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content Block content.
     * @param WP_Block $block Block instance.
     *
     * @return string | void Rendered block output.
     */
    protected function render($attributes, $content, $block)
    {
        /**
         * This block should be rendered only on the frontend. Woo loads notice
         * functions on the front end requests only. So it's safe and handy to
         * check for the print notice function existence to short circuit the
         * render process on the admin side.
         * See WooCommerce::is_request() for the frontend request definition.
         */
        if (!function_exists('wc_print_notices')) {
            return $content;
        }
        ob_start();
        woocommerce_output_all_notices();
        $notices = ob_get_clean();
        if (!$notices) {
            return;
        }
        $classes_and_styles = Style_Attributes_Utils::get_classes_and_styles_by_attributes($attributes, [], ['extra_classes']);
        return sprintf('<div %1$s>%2$s</div>', get_block_wrapper_attributes(['class' => 'wc-block-store-notices woocommerce ' . esc_attr($classes_and_styles['classes'])]), wc_kses_notice($notices));
    }
    /**
     * Disable frontend script for this block type, it's a script module.
     *
     * @param string $key Data to get, or default to everything.
     * @return array|string|null
     */
    protected function get_block_type_script($key = null): null
    {
        return null;
    }
}