<?php

declare (strict_types=1);
/**
 * WooCommerce Onboarding
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Onboarding;

/**
 * Initializes backend logic for the onboarding process.
 */
class Onboarding
{
    /**
     * Initialize onboarding functionality.
     *
     * @internal This method is for internal purposes only.
     */
    final public static function init(): void
    {
        Onboarding_Helper::instance()->init();
        Onboarding_Industries::init();
        Onboarding_Jetpack::instance()->init();
        Onboarding_Mailchimp::instance()->init();
        Onboarding_Profile::init();
        Onboarding_Setup_Wizard::instance()->init();
        Onboarding_Sync::instance()->init();
    }
}