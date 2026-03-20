<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Remote_Free_Extensions;

use Automattic\Woo_Commerce\Admin\Remote_Specs\Data_Source_Poller;
use WC_Helper;
/**
 * Specs data source poller class for remote free extensions.
 */
class Remote_Free_Extensions_Data_Source_Poller extends Data_Source_Poller
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
        if (!self::$instance) {
            self::$instance = new self(self::ID, self::get_data_sources(), ['spec_key' => 'key']);
        }
        return self::$instance;
    }
    /**
     * Get data sources.
     */
    public static function get_data_sources(): array
    {
        return [WC_Helper::get_woocommerce_com_base_url() . 'wp-json/wccom/obw-free-extensions/4.0/extensions.json'];
    }
}