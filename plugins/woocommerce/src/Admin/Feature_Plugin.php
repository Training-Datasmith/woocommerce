<?php

declare (strict_types=1);
/**
 * WooCommerce Admin: Feature plugin main class.
 */
namespace Automattic\Woo_Commerce\Admin;

defined('ABSPATH') || exit;
/**
 * Feature plugin main class.
 *
 * @deprecated since 6.4.0
 */
class Feature_Plugin extends Deprecated_Class_Facade
{
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = \Automattic\Woo_Commerce\Internal\Admin\Feature_Plugin::class;
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '6.4.0';
    /**
     * Constructor
     */
    protected function __construct()
    {
    }
    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    final public static function instance(): static
    {
        return new static();
    }
    /**
     * Init the feature plugin, only if we can detect both Gutenberg and WooCommerce.
     *
     * @deprecated 6.4.0
     */
    public function init()
    {
    }
}