<?php

declare (strict_types=1);
/**
 * WooCommerce Onboarding Mailchimp
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Onboarding;

use Automattic\Woo_Commerce\Internal\Admin\Schedulers\Mailchimp_Scheduler;
/**
 * Logic around updating Mailchimp during onboarding.
 */
class Onboarding_Mailchimp
{
    /**
     * Class instance.
     *
     * @var OnboardingMailchimp instance
     */
    private static ?self $instance = null;
    /**
     * Get class instance.
     */
    final public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * Init.
     */
    public function init(): void
    {
        add_action('woocommerce_onboarding_profile_data_updated', $this->on_profile_data_updated(...), 10, 2);
    }
    /**
     * Reset MailchimpScheduler if profile data is being updated with a new email.
     *
     * @param array $existing_data Existing option data.
     * @param array $updating_data Updating option data.
     */
    public function on_profile_data_updated(array $existing_data, array $updating_data): void
    {
        if (isset($existing_data['store_email']) && isset($updating_data['store_email']) && $existing_data['store_email'] !== $updating_data['store_email']) {
            Mailchimp_Scheduler::reset();
        }
    }
}