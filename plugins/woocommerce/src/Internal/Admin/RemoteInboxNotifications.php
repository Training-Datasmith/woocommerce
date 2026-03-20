<?php

declare (strict_types=1);
/**
 * Remote Inbox Notifications feature.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Admin\Remote_Inbox_Notifications\Remote_Inbox_Notifications_Engine;
/**
 * Remote Inbox Notifications feature logic.
 */
class Remote_Inbox_Notifications
{
    /**
     * Option name used to toggle this feature.
     */
    public const TOGGLE_OPTION_NAME = 'woocommerce_show_marketplace_suggestions';
    /**
     * Class instance.
     *
     * @var RemoteInboxNotifications instance
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
        if (Features::is_enabled('remote-inbox-notifications')) {
            Remote_Inbox_Notifications_Engine::init();
        }
    }
}