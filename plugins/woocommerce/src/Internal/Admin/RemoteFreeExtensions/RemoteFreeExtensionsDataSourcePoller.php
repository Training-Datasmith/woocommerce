<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use WC_Helper;

/**
 * Specs data source poller class for remote free extensions.
 */
class RemoteFreeExtensionsDataSourcePoller extends DataSourcePoller
{
    public const ID = 'remote_free_extensions';

    /**
     * Default data sources array.
     *
     * @deprecated since 9.5.0. Use get_data_sources() instead.
     */
    public const DATA_SOURCES = [];

    /**
     * Class instance.
     *
     * @var RemoteFreeExtensionsDataSourcePoller instance
     */
    protected static $instance;

    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (! self::$instance) {
            self::$instance = new self(
                self::ID,
                self::get_data_sources(),
                [
                    'spec_key' => 'key',
                ]
            );
        }
        return self::$instance;
    }

    /**
     * Get data sources.
     */
    public static function get_data_sources(): array
    {
        return [
            WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/obw-free-extensions/4.0/extensions.json',
        ];
    }
}
