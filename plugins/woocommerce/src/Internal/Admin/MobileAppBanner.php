<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin;

defined('ABSPATH') || exit;
/**
 * Determine if the mobile app banner shows on Android devices
 */
class Mobile_App_Banner
{
    /**
     * Class instance.
     *
     * @var Analytics instance
     */
    protected static $instance;
    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Hook into WooCommerce.
     */
    public function __construct()
    {
        add_filter('woocommerce_admin_get_user_data_fields', $this->add_user_data_fields(...));
    }
    /**
     * Adds fields so that we can store user preferences for the mobile app banner
     *
     * @param array $user_data_fields User data fields.
     */
    public function add_user_data_fields($user_data_fields): array
    {
        return array_merge($user_data_fields, ['android_app_banner_dismissed']);
    }
}