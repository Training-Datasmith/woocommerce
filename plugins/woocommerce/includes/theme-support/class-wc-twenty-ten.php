<?php

declare(strict_types=1);
/**
 * Twenty Ten support.
 *
 * @since   3.3.0
 * @package WooCommerce\Classes
 */

defined('ABSPATH') || exit;

/**
 * WC_Twenty_Ten class.
 */
class WC_Twenty_Ten
{
    /**
     * Theme init.
     */
    public static function init(): void
    {
        // Remove default wrappers.
        remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper');
        remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end');

        // Add custom wrappers.
        add_action('woocommerce_before_main_content', self::output_content_wrapper(...));
        add_action('woocommerce_after_main_content', self::output_content_wrapper_end(...));

        // Declare theme support for features.
        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
        add_theme_support('wc-product-gallery-slider');
        add_theme_support(
            'woocommerce',
            [
                'thumbnail_image_width' => 200,
                'single_image_width'    => 300,
            ]
        );
    }

    /**
     * Open wrappers.
     */
    public static function output_content_wrapper(): void
    {
        echo '<div id="container"><div id="content" role="main">';
    }

    /**
     * Close wrappers.
     */
    public static function output_content_wrapper_end(): void
    {
        echo '</div></div>';
    }
}

WC_Twenty_Ten::init();
