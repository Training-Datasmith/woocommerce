<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Blueprint\Exporters;

use Automattic\Woo_Commerce\Blueprint\Exporters\Has_Alias;
use Automattic\Woo_Commerce\Blueprint\Exporters\Step_Exporter;
use Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options;
use Automattic\Woo_Commerce\Blueprint\Use_Wp_Functions;
/**
 * Class ExportWCSettingsSiteVisibility
 *
 * This class exports WooCommerce settings on the Site Visibility page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class Export_Wc_Settings_Site_Visibility implements Step_Exporter, Has_Alias
{
    use Use_Wp_Functions;
    /**
     * Export Site Visibility settings.
     */
    public function export(): \Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options
    {
        return new Set_Site_Options(['woocommerce_coming_soon' => $this->wp_get_option('woocommerce_coming_soon'), 'woocommerce_store_pages_only' => $this->wp_get_option('woocommerce_store_pages_only')]);
    }
    /**
     * Get the alias for this exporter.
     */
    public function get_alias(): string
    {
        return 'setWCSettingsSiteVisibility';
    }
    /**
     * Return label used in the frontend.
     *
     * @return string
     */
    public function get_label()
    {
        return __('Site Visibility', 'woocommerce');
    }
    /**
     * Return description used in the frontend.
     *
     * @return string
     */
    public function get_description()
    {
        return __('Includes all settings in WooCommerce | Settings | Visibility.', 'woocommerce');
    }
    /**
     * Get the name of the step.
     */
    public function get_step_name(): string
    {
        return 'setSiteOptions';
    }
    /**
     * Check if the current user has the required capabilities for this step.
     *
     * @return bool True if the user has the required capabilities. False otherwise.
     */
    public function check_step_capabilities(): bool
    {
        return current_user_can('manage_woocommerce');
    }
}