<?php

declare (strict_types=1);
/**
 * WooCommerce Onboarding Industries
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Onboarding;

/**
 * Logic around onboarding industries.
 */
class Onboarding_Industries
{
    /**
     * Init.
     */
    public static function init(): void
    {
        add_filter('woocommerce_admin_onboarding_preloaded_data', self::preload_data(...));
    }
    /**
     * Get a list of allowed industries for the onboarding wizard.
     *
     * @return array
     */
    public static function get_allowed_industries()
    {
        /* With "use_description" we turn the description input on. With "description_label" we set the input label */
        return apply_filters('woocommerce_admin_onboarding_industries', ['fashion-apparel-accessories' => ['label' => __('Fashion, apparel, and accessories', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'health-beauty' => ['label' => __('Health and beauty', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'electronics-computers' => ['label' => __('Electronics and computers', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'food-drink' => ['label' => __('Food and drink', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'home-furniture-garden' => ['label' => __('Home, furniture, and garden', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'cbd-other-hemp-derived-products' => ['label' => __('CBD and other hemp-derived products', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'education-and-learning' => ['label' => __('Education and learning', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'sports-and-recreation' => ['label' => __('Sports and recreation', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'arts-and-crafts' => ['label' => __('Arts and crafts', 'woocommerce'), 'use_description' => false, 'description_label' => ''], 'other' => ['label' => __('Other', 'woocommerce'), 'use_description' => true, 'description_label' => __('Description', 'woocommerce')]]);
    }
    /**
     * Add preloaded data to onboarding.
     *
     * @param array $settings Component settings.
     */
    public static function preload_data(array $settings): array
    {
        $settings['onboarding']['industries'] = self::get_allowed_industries();
        return $settings;
    }
}