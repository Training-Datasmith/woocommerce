<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Importers\ImportActivatePlugin;
use Automattic\WooCommerce\Blueprint\Importers\ImportActivateTheme;
use Automattic\WooCommerce\Blueprint\Importers\ImportInstallPlugin;
use Automattic\WooCommerce\Blueprint\Importers\ImportInstallTheme;
use Automattic\WooCommerce\Blueprint\Importers\ImportRunSql;
use Automattic\WooCommerce\Blueprint\Importers\ImportSetSiteOptions;
use Automattic\WooCommerce\Blueprint\ResourceStorages\OrgPluginResourceStorage;
use Automattic\WooCommerce\Blueprint\ResourceStorages\OrgThemeResourceStorage;

/**
 * Class BuiltInStepProcessors
 *
 * @package Automattic\WooCommerce\Blueprint
 */
class BuiltInStepProcessors
{
    /**
     * Returns an array of all step processors.
     *
     * @return array The array of step processors.
     */
    public function get_all(): array
    {
        return [
            $this->create_install_plugins_processor(),
            $this->create_install_themes_processor(),
            new ImportSetSiteOptions(),
            new ImportActivatePlugin(),
            new ImportActivateTheme(),
            new ImportRunSql(),
        ];
    }

    /**
     * Creates the processor for installing plugins.
     *
     * @return ImportInstallPlugin The processor for installing plugins.
     */
    private function create_install_plugins_processor(): \Automattic\WooCommerce\Blueprint\Importers\ImportInstallPlugin
    {
        $storages = new ResourceStorages();
        $storages->add_storage(new OrgPluginResourceStorage());
        return new ImportInstallPlugin($storages);
    }

    /**
     * Creates the processor for installing themes.
     *
     * @return ImportInstallTheme The processor for installing themes.
     */
    private function create_install_themes_processor(): \Automattic\WooCommerce\Blueprint\Importers\ImportInstallTheme
    {
        $storage = new ResourceStorages();
        $storage->add_storage(new OrgThemeResourceStorage());
        return new ImportInstallTheme($storage);
    }
}
