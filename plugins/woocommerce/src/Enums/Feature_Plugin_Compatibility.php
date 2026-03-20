<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Enums;

/**
 * Enum class for feature plugin compatibility.
 */
final class Feature_Plugin_Compatibility
{
    /**
     * Plugins are compatible by default with the feature.
     *
     * @var string
     */
    public const COMPATIBLE = 'compatible';
    /**
     * Plugins are incompatible by default with the feature.
     *
     * @var string
     */
    public const INCOMPATIBLE = 'incompatible';
    /**
     * Plugin compatibility with the feautre is yet to be determined. Internal use only.
     *
     * @internal
     * @var string
     */
    public const UNCERTAIN = 'uncertain';
    /**
     * Valid values for registration of feature compatibility.
     *
     * @var string[]
     */
    public const VALID_REGISTRATION_VALUES = [self::COMPATIBLE, self::INCOMPATIBLE];
}