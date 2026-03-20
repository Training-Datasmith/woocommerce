<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Tasks;

use Automattic\Jetpack\Connection\Manager;
use Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Task;
// https://github.com/Automattic/jetpack/blob/trunk/projects/packages/connection/src/class-manager.php .
/**
 * Get Mobile App Task
 */
class Get_Mobile_App extends Task
{
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'get-mobile-app';
    }
    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Get the free WooCommerce mobile app', 'woocommerce');
    }
    /**
     * Content.
     */
    public function get_content(): string
    {
        return '';
    }
    /**
     * Time.
     */
    public function get_time(): string
    {
        return '';
    }
    /**
     * Task completion.
     */
    public function is_complete(): bool
    {
        return get_option('woocommerce_admin_dismissed_mobile_app_modal') === 'yes';
    }
    /**
     * Task visibility.
     * Can view under these conditions:
     *  - Jetpack is installed and connected && current site user has a wordpress.com account connected to jetpack
     *  - Jetpack is not connected && current user is capable of installing plugins
     */
    public function can_view(): bool
    {
        $jetpack_can_be_installed = current_user_can('manage_woocommerce') && current_user_can('install_plugins') && !self::is_jetpack_connected();
        $jetpack_is_installed_and_current_user_connected = self::is_current_user_connected();
        return $jetpack_can_be_installed || $jetpack_is_installed_and_current_user_connected;
    }
    /**
     * Determines if site has any users connected to WordPress.com via JetPack
     *
     * @return bool
     */
    private static function is_jetpack_connected()
    {
        if (class_exists('\Automattic\Jetpack\Connection\Manager') && method_exists('\Automattic\Jetpack\Connection\Manager', 'is_active')) {
            $connection = new Manager();
            return $connection->is_active();
        }
        return false;
    }
    /**
     * Determines if the current user is connected to Jetpack.
     *
     * @return bool
     */
    private static function is_current_user_connected()
    {
        if (class_exists('\Automattic\Jetpack\Connection\Manager') && method_exists('\Automattic\Jetpack\Connection\Manager', 'is_user_connected')) {
            $connection = new Manager();
            return $connection->is_connection_owner();
        }
        return false;
    }
    /**
     * Action URL.
     *
     * @return string
     */
    public function get_action_url()
    {
        return admin_url('admin.php?page=wc-admin&mobileAppModal=true');
    }
}