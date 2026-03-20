<?php

declare (strict_types=1);
/**
 * WooCommerce Onboarding
 */
namespace Automattic\Woo_Commerce\Admin\Features;

use Automattic\Woo_Commerce\Admin\Deprecated_Class_Facade;
/**
 * Contains backend logic for the onboarding profile and checklist feature.
 *
 * @deprecated since 6.3.0, use WooCommerce\Internal\Admin\Onboarding.
 */
class Onboarding extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Admin\Features\Onboarding::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '6.3.0';
    /**
     * Hook into WooCommerce.
     */
    public function __construct()
    {
    }
    /**
     * Get a list of allowed industries for the onboarding wizard.
     *
     * @deprecated 6.3.0
     * @return array
     */
    public static function get_allowed_industries()
    {
        wc_deprecated_function('get_allowed_industries', '6.3', '\Automattic\WooCommerce\Internal\Admin\OnboardingIndustries::get_allowed_industries()');
        return \Automattic\Woo_Commerce\Internal\Admin\Onboarding\Onboarding_Industries::get_allowed_industries();
    }
    /**
     * Get a list of allowed product types for the onboarding wizard.
     *
     * @deprecated 6.3.0
     * @return array
     */
    public static function get_allowed_product_types()
    {
        wc_deprecated_function('get_allowed_product_types', '6.3', '\Automattic\WooCommerce\Internal\Admin\OnboardingProducts::get_allowed_product_types()');
        return \Automattic\Woo_Commerce\Internal\Admin\Onboarding\Onboarding_Products::get_allowed_product_types();
    }
    /**
     * Get a list of themes for the onboarding wizard.
     *
     * @deprecated 6.3.0
     */
    public static function get_themes(): array
    {
        wc_deprecated_function('get_themes', '6.3');
        return [];
    }
    /**
     * Get theme data used in onboarding theme browser.
     *
     * @deprecated 6.3.0
     * @param WP_Theme $theme Theme to gather data from.
     */
    public static function get_theme_data($theme): array
    {
        wc_deprecated_function('get_theme_data', '6.3');
        return [];
    }
    /**
     * Gets an array of themes that can be installed & activated via the onboarding wizard.
     *
     * @deprecated 6.3.0
     */
    public static function get_allowed_themes(): array
    {
        wc_deprecated_function('get_allowed_themes', '6.3');
        return [];
    }
    /**
     * Get dynamic product data from API.
     *
     * @deprecated 6.3.0
     * @param array $product_types Array of product types.
     */
    public static function get_product_data($product_types): array
    {
        wc_deprecated_function('get_product_data', '6.3');
        return [];
    }
}