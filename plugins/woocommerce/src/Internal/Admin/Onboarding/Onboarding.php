<?php

declare(strict_types=1);
/**
 * WooCommerce Onboarding
 */

namespace Automattic\WooCommerce\Internal\Admin\Onboarding;

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
        OnboardingHelper::instance()->init();
        OnboardingIndustries::init();
        OnboardingJetpack::instance()->init();
        OnboardingMailchimp::instance()->init();
        OnboardingProfile::init();
        OnboardingSetupWizard::instance()->init();
        OnboardingSync::instance()->init();
    }
}
