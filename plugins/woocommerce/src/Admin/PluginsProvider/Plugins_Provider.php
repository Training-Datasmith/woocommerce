<?php

declare (strict_types=1);
/**
 * A provider for getting access to plugin queries.
 */
namespace Automattic\Woo_Commerce\Admin\Plugins_Provider;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Plugins_Helper;
/**
 * Plugins Provider.
 *
 * Uses the live PluginsHelper.
 */
class Plugins_Provider implements Plugins_Provider_Interface
{
    /**
     * The deactivated plugin slug.
     */
    private static string $deactivated_plugin_slug = '';
    /**
     * Get an array of active plugin slugs.
     */
    public function get_active_plugin_slugs(): array
    {
        return array_filter(Plugins_Helper::get_active_plugin_slugs(), fn($p) => $p !== self::$deactivated_plugin_slug);
    }
    /**
     * Set the deactivated plugin. This is needed because the deactivated_plugin
     * hook happens before the option is updated which means that getting the
     * active plugins includes the deactivated plugin.
     *
     * @param string $plugin_path The path to the plugin being deactivated.
     */
    public static function set_deactivated_plugin($plugin_path): void
    {
        self::$deactivated_plugin_slug = explode('/', $plugin_path)[0];
    }
    /**
     * Get plugin data.
     *
     * @param string $plugin Path to the plugin file relative to the plugins directory or the plugin directory name.
     *
     * @return array|false
     */
    public function get_plugin_data($plugin)
    {
        return Plugins_Helper::get_plugin_data($plugin);
    }
    /**
     * Get the path to the plugin file relative to the plugins directory from the plugin slug.
     *
     * E.g. 'woocommerce' returns 'woocommerce/woocommerce.php'
     *
     * @param string $slug Plugin slug to get path for.
     *
     * @return string|false
     */
    public function get_plugin_path_from_slug($slug)
    {
        return Plugins_Helper::get_plugin_path_from_slug($slug);
    }
}