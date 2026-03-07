<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;

/**
 * Built-in exporters.
 */
class BuiltInExporters
{
    /**
     * Get all built-in exporters.
     *
     * @return array List of all built-in exporters.
     */
    public function get_all(): array
    {
        return [
            new ExportInstallPluginSteps(),
            new ExportInstallThemeSteps(),
        ];
    }
}
