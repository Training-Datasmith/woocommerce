<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\Jetpack\Constants;

/**
 * Trait ScriptDebug
 *
 * @since 8.5.0
 */
trait ScriptDebug
{
    /**
     * Get the script suffix based on the SCRIPT_DEBUG constant.
     */
    protected function get_script_suffix(): string
    {
        return $this->is_script_debug_enabled() ? '' : '.min';
    }

    /**
     * Check if SCRIPT_DEBUG is enabled.
     */
    protected function is_script_debug_enabled(): bool
    {
        return Constants::is_true('SCRIPT_DEBUG');
    }
}
