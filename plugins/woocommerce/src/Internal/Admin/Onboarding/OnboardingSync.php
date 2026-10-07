<?php

declare(strict_types=1);
/**
 * WooCommerce Onboarding
 */

namespace Automattic\WooCommerce\Internal\Admin\Onboarding;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;

/**
 * Contains backend logic for the onboarding profile and checklist feature.
 */
class OnboardingSync
{
    /**
     * Class instance.
     *
     * @var OnboardingSync instance
     */
    private static ?self $instance = null;

    /**
     * Get class instance.
     */
    final public static function instance()
    {
        if (! static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Init.
     */
    public function init(): void
    {
        add_action('update_option_' . OnboardingProfile::DATA_OPTION, [ $this, 'send_profile_data_on_update' ], 10, 2);
        add_action('woocommerce_helper_connected', [ $this, 'send_profile_data_on_connect' ]);

        if (! is_admin()) {
            return;
        }

        add_action('current_screen', $this->redirect_wccom_install(...));
    }

    /**
     * Send profile data to WooCommerce.com.
     */
    private function send_profile_data()
    {
        if ('yes' !== get_option('woocommerce_allow_tracking', 'no')) {
            return;
        }

        if (! class_exists('\WC_Helper_API') || ! method_exists('\WC_Helper_API', 'put')) {
            return;
        }

        if (! class_exists('\WC_Helper_Options')) {
            return;
        }

        $auth = \WC_Helper_Options::get('auth');
        if (empty($auth['access_token']) || empty($auth['access_token_secret'])) {
            return false;
        }

        $profile       = get_option(OnboardingProfile::DATA_OPTION, []);
        $base_location = wc_get_base_location();
        $defaults      = [
            'plugins'             => 'skipped',
            'industry'            => [],
            'product_types'       => [],
            'product_count'       => '0',
            'selling_venues'      => 'no',
            'number_employees'    => '1',
            'revenue'             => 'none',
            'other_platform'      => 'none',
            'business_extensions' => [],
            'theme'               => get_stylesheet(),
            'setup_client'        => false,
            'store_location'      => $base_location['country'],
            'default_currency'    => get_woocommerce_currency(),
        ];

        // Prepare industries as an array of slugs if they are in array format.
        if (isset($profile['industry']) && is_array($profile['industry'])) {
            $industry_slugs = [];
            foreach ($profile['industry'] as $industry) {
                $industry_slugs[] = is_array($industry) ? $industry['slug'] : $industry;
            }
            $profile['industry'] = $industry_slugs;
        }
        $body = wp_parse_args($profile, $defaults);

        \WC_Helper_API::put(
            'profile',
            [
                'authenticated' => true,
                'body'          => wp_json_encode($body),
                'headers'       => [
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }

    /**
     * Send profiler data on profiler change to completion.
     *
     * @param array|mixed $old_value Previous value.
     * @param array|mixed $value Current value.
     */
    public function send_profile_data_on_update($old_value, $value): void
    {
        if (! is_array($value) || ! isset($value['completed']) || ! $value['completed']) {
            return;
        }

        $this->send_profile_data();
    }

    /**
     * Send profiler data after a site is connected.
     */
    public function send_profile_data_on_connect(): void
    {
        $profile = get_option(OnboardingProfile::DATA_OPTION, []);
        if (! isset($profile['completed']) || ! $profile['completed']) {
            return;
        }

        $this->send_profile_data();
    }

    /**
     * Redirects the user to the task list if the task list is enabled and finishing a wccom checkout.
     *
     * @todo Once URL params are added to the redirect, we can check those instead of the referer.
     */
    public function redirect_wccom_install(): void
    {
        $task_list = TaskLists::get_list('setup');

        if (
            ! $task_list ||
            $task_list->is_hidden() ||
            ! isset($_SERVER['HTTP_REFERER']) ||
            !str_starts_with(wp_unslash($_SERVER['HTTP_REFERER']), 'https://woocommerce.com/checkout?utm_medium=product') // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        ) {
            return;
        }

        wp_safe_redirect(wc_admin_url());
    }
}
