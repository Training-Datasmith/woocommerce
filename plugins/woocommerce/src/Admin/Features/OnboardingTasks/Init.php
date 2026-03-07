<?php

declare(strict_types=1);
/**
 * WooCommerce Onboarding Tasks
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

/**
 * Contains the logic for completing onboarding tasks.
 */
class Init
{
    /**
     * Class instance.
     *
     * @var OnboardingTasks instance
     */
    protected static $instance;

    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        DeprecatedOptions::init();
        TaskLists::init();
    }

    /**
     * Get task item data for settings filter.
     */
    public static function get_settings(): array
    {
        $settings            = [];
        $wc_pay_is_connected = false;
        if (class_exists('\WC_Payments')) {
            $wc_payments_gateway = \WC_Payments::get_gateway();
            $wc_pay_is_connected = method_exists($wc_payments_gateway, 'is_connected')
                ? $wc_payments_gateway->is_connected()
                : false;
        }

        return $settings;
    }
}
