<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\EmailEditor;

defined('ABSPATH') || exit;

/**
 * This class is used to initialize the email editor package.
 *
 * It is a wrapper around the Automattic\WooCommerce\EmailEditor\Package class and
 * ensures that the email editor package is only initialized if the block editor feature flag is enabled.
 */
class Package
{
    /**
     * Version.
     *
     * @var string
     */
    public const VERSION = \Automattic\WooCommerce\EmailEditor\Package::VERSION;

    /**
     * Package active.
     */
    private static bool $package_active = false;

    /**
     * Init the package.
     *
     * @internal
     */
    final public static function init(): void
    {
        self::$package_active = get_option('woocommerce_feature_block_email_editor_enabled', 'no') === 'yes'; // init is called pretty early. Cant use FeaturesUtil.

        // we only want to initialize the package if the block editor feature flag is enabled.
        if (! self::$package_active) {
            return;
        }

        self::initialize();
        \Automattic\WooCommerce\EmailEditor\Package::init();
    }

    /**
     * Return the version of the package.
     */
    public static function get_version(): string
    {
        return \Automattic\WooCommerce\EmailEditor\Package::get_version();
    }

    /**
     * Return the path to the package.
     */
    public static function get_path(): string
    {
        return \Automattic\WooCommerce\EmailEditor\Package::get_path();
    }

    /**
     * Initialize the email editor integration by fetching the class from the container.
     */
    public static function initialize(): void
    {
        $container = wc_get_container();
        $container->get(Integration::class);
    }
}
