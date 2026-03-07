<?php

declare(strict_types=1);
/**
 * Returns information about the package and handles init.
 */

/**
 * This namespace isn't compatible with the PSR-4
 * which ensures that the copy in the standalone plugin will not be autoloaded.
 */

namespace Automattic\WooCommerce\Admin\Composer;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Internal\Admin\FeaturePlugin;

/**
 * Main package class.
 */
class Package
{
    /**
     * Version.
     *
     * @var string
     */
    public const VERSION = '3.3.0';

    /**
     * Package active.
     */
    private static bool $package_active = false;

    /**
     * Active version
     *
     * @var bool
     */
    private static $active_version;

    /**
     * Init the package.
     *
     * Only initialize for WP 5.3 or greater.
     */
    public static function init(): void
    {
        // Avoid double initialization when the feature plugin is in use.
        if (defined('WC_ADMIN_VERSION_NUMBER')) {
            self::$active_version = WC_ADMIN_VERSION_NUMBER;
            return;
        }

        $feature_plugin_instance = FeaturePlugin::instance();

        // Indicate to the feature plugin that the core package exists.
        if (! defined('WC_ADMIN_PACKAGE_EXISTS')) {
            define('WC_ADMIN_PACKAGE_EXISTS', true);
        }

        self::$package_active = true;
        self::$active_version = self::VERSION;
        $feature_plugin_instance->init();

        // Unhook the custom Action Scheduler data store class in active older versions of WC Admin.
        remove_filter('action_scheduler_store_class', [ $feature_plugin_instance, 'replace_actionscheduler_store_class' ]);
    }

    /**
     * Return the version of the package.
     */
    public static function get_version(): string
    {
        return self::VERSION;
    }

    /**
     * Return the active version of WC Admin.
     *
     * @return string
     */
    public static function get_active_version()
    {
        return self::$active_version;
    }

    /**
     * Return whether the package is active.
     *
     * @return bool
     */
    public static function is_package_active()
    {
        return self::$package_active;
    }

    /**
     * Return the path to the package.
     */
    public static function get_path(): string
    {
        return dirname(__DIR__);
    }
}
