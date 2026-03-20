<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Domain\Services;

use Automattic\Woo_Commerce\Admin\Deprecated_Class_Facade;
/**
 * Service class that used to handle feature flags. That functionality
 * is removed now and it is only used to determine "environment".
 *
 * @internal
 *
 * @deprecated since 9.6.0, use wp_get_environment_type() instead.
 */
class Feature_Gating extends Deprecated_Class_Facade
{
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '9.6.0';
    /**
     * Constructor
     */
    public function __construct()
    {
    }
}