<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportWCSettingsIntegrations
 *
 * This class exports WooCommerce settings on the Integrations page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCSettingsIntegrations extends ExportWCSettings
{
    use UseWPFunctions;

    /**
     * Get the alias for this exporter.
     */
    public function get_alias(): string
    {
        return 'setWCSettingsIntegrations';
    }

    /**
     * Return label used in the frontend.
     *
     * @return string
     */
    public function get_label()
    {
        return __('Integrations', 'woocommerce');
    }

    /**
     * Export WooCommerce settings.
     */
    public function export(): \Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions
    {
        if (! isset(WC()->integrations)) {
            return new SetSiteOptions([]);
        }

        $integrations = WC()->integrations->get_integrations();

        $settings = [];
        foreach ($integrations as $integration) {
            $option_key              = $integration->get_option_key();
            $settings[ $option_key ] = get_option($option_key, null);
        }

        return new SetSiteOptions($settings);
    }

    /**
     * Return description used in the frontend.
     *
     * @return string
     */
    public function get_description()
    {
        return __('Includes all settings in WooCommerce | Settings | Integrations.', 'woocommerce');
    }

    /**
     * Get the page ID for the settings page.
     */
    protected function get_page_id(): string
    {
        return 'integration';
    }
}
