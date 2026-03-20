<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Blueprint\Exporters;

use Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options;
use Automattic\Woo_Commerce\Blueprint\Use_Wp_Functions;
/**
 * Class ExportWCSettingsIntegrations
 *
 * This class exports WooCommerce settings on the Integrations page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class Export_Wc_Settings_Integrations extends Export_Wc_Settings
{
    use Use_Wp_Functions;
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
    public function export(): \Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options
    {
        if (!isset(WC()->integrations)) {
            return new Set_Site_Options([]);
        }
        $integrations = WC()->integrations->get_integrations();
        $settings = [];
        foreach ($integrations as $integration) {
            $option_key = $integration->get_option_key();
            $settings[$option_key] = get_option($option_key, null);
        }
        return new Set_Site_Options($settings);
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