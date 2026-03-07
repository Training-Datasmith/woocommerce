<?php

/**
 * Email Editor Container class file.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor;

defined('ABSPATH') || exit;

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
    public const VERSION = '0.1.0';

    /**
     * Init the package.
     */
    public static function init(): void
    {
        Email_Editor_Container::init();
    }

    /**
     * Return the version of the package.
     */
    public static function get_version(): string
    {
        return self::VERSION;
    }

    /**
     * Return the path to the package.
     */
    public static function get_path(): string
    {
        return dirname(__DIR__);
    }
}
