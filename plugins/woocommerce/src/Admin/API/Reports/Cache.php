<?php

declare(strict_types=1);
/**
 * REST API Reports Cache.
 *
 * Handles report data object caching.
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined('ABSPATH') || exit;

/**
 * REST API Reports Cache class.
 */
class Cache
{
    /**
     * Cache version. Used to invalidate all cached values.
     */
    public const VERSION_OPTION = 'woocommerce_reports';

    /**
     * Invalidate cache.
     */
    public static function invalidate(): void
    {
        \WC_Cache_Helper::get_transient_version(self::VERSION_OPTION, true);
    }

    /**
     * Get cache version number.
     *
     * @return string
     */
    public static function get_version()
    {
        return \WC_Cache_Helper::get_transient_version(self::VERSION_OPTION);
    }

    /**
     * Get cached value.
     *
     * @param string $key Cache key.
     * @return mixed
     */
    public static function get($key)
    {
        $transient_version = self::get_version();
        $transient_value   = get_transient($key);

        if (
            isset($transient_value['value'], $transient_value['version']) &&
            $transient_value['version'] === $transient_version
        ) {
            return $transient_value['value'];
        }

        return false;
    }

    /**
     * Update cached value.
     *
     * @param string $key   Cache key.
     * @param mixed  $value New value.
     * @return bool
     */
    public static function set($key, $value)
    {
        $transient_version = self::get_version();
        $transient_value   = [
            'version' => $transient_version,
            'value'   => $value,
        ];

        return set_transient($key, $transient_value, WEEK_IN_SECONDS);
    }
}
