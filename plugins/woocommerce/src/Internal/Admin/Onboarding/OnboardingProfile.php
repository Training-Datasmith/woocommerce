<?php

declare (strict_types=1);
/**
 * WooCommerce Onboarding Setup Wizard
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Onboarding;

/**
 * Contains backend logic for the onboarding profile and checklist feature.
 */
class Onboarding_Profile
{
    /**
     * Profile data option name.
     */
    public const DATA_OPTION = 'woocommerce_onboarding_profile';
    /**
     * Option for storing the onboarding profile progress.
     */
    public const PROGRESS_OPTION = 'woocommerce_onboarding_profile_progress';
    /**
     * Add onboarding actions.
     */
    public static function init(): void
    {
        add_action('update_option_' . self::DATA_OPTION, self::trigger_complete(...), 10, 2);
    }
    /**
     * Trigger the woocommerce_onboarding_profile_completed action
     *
     * @param array $old_value Previous value.
     * @param array $value Current value.
     */
    public static function trigger_complete(array $old_value, array $value): void
    {
        if (isset($old_value['completed']) && $old_value['completed']) {
            return;
        }
        if (!isset($value['completed']) || !$value['completed']) {
            return;
        }
        /**
         * Action hook fired when the onboarding profile (or onboarding wizard,
         * or profiler) is completed.
         *
         * @since 1.5.0
         */
        do_action('woocommerce_onboarding_profile_completed');
    }
    /**
     * Check if the profiler still needs to be completed.
     */
    public static function needs_completion(): bool
    {
        $onboarding_data = get_option(self::DATA_OPTION, []);
        $is_completed = isset($onboarding_data['completed']) && true === $onboarding_data['completed'];
        $is_skipped = isset($onboarding_data['skipped']) && true === $onboarding_data['skipped'];
        // @todo When merging to WooCommerce Core, we should set the `completed` flag to true during the upgrade progress.
        // https://github.com/woocommerce/woocommerce-admin/pull/2300#discussion_r287237498.
        return !$is_completed && !$is_skipped;
    }
}