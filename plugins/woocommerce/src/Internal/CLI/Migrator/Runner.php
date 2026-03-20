<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\CLI\Migrator;

use Automattic\Woo_Commerce\Internal\CLI\Migrator\Commands\List_Command;
use Automattic\Woo_Commerce\Internal\CLI\Migrator\Commands\Products_Command;
use Automattic\Woo_Commerce\Internal\CLI\Migrator\Commands\Reset_Command;
use Automattic\Woo_Commerce\Internal\CLI\Migrator\Commands\Setup_Command;
use Automattic\Woo_Commerce\Internal\CLI\Migrator\Platforms\Shopify\Shopify_Platform;
use WP_CLI;
/**
 * The main runner for the migrator.
 */
final class Runner
{
    /**
     * Register the commands for the migrator.
     */
    public static function register_commands(): void
    {
        // Initialize built-in platforms.
        self::init_platforms();
        $container = wc_get_container();
        WP_CLI::add_command('wc migrate products', $container->get(Products_Command::class), ['shortdesc' => 'Migrate products from a source platform to WooCommerce.', 'longdesc' => 'Migrate products from a source platform to WooCommerce. The migrator will fetch products from the source platform, map them to the WooCommerce product schema, and then import them into WooCommerce.']);
        WP_CLI::add_command('wc migrate reset', $container->get(Reset_Command::class), ['shortdesc' => 'Resets (deletes) the credentials for a given platform.']);
        WP_CLI::add_command('wc migrate setup', $container->get(Setup_Command::class), ['shortdesc' => 'Interactively sets up the credentials for a given platform.']);
        WP_CLI::add_command('wc migrate list', $container->get(List_Command::class), ['shortdesc' => 'Lists all registered migration platforms.']);
    }
    /**
     * Initialize built-in migration platforms.
     */
    private static function init_platforms(): void
    {
        Shopify_Platform::init();
    }
}