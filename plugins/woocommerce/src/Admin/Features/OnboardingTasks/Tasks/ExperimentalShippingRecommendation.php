<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Tasks;

use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Task;
use Automattic\Woo_Commerce\Admin\Plugins_Helper;
use Automattic\Woo_Commerce\Internal\Jetpack\Jetpack_Connection;
/**
 * Shipping Task
 */
class Experimental_Shipping_Recommendation extends Task
{
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'shipping-recommendation';
    }
    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Get your products shipped', 'woocommerce');
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
        return self::has_plugins_active() && self::has_jetpack_connected();
    }
    /**
     * Task visibility.
     */
    public function can_view(): bool
    {
        return Features::is_enabled('shipping-smart-defaults');
    }
    /**
     * Action URL.
     */
    public function get_action_url(): string
    {
        return '';
    }
    /**
     * Check if the store has any shipping zones.
     *
     * @return bool
     */
    public static function has_plugins_active()
    {
        return Plugins_Helper::is_plugin_active('woocommerce-shipping');
    }
    /**
     * Check if the Jetpack is connected.
     */
    public static function has_jetpack_connected(): bool
    {
        $jetpack_connection_manager = Jetpack_Connection::get_manager();
        return $jetpack_connection_manager->is_connected() && $jetpack_connection_manager->has_connected_owner();
    }
}