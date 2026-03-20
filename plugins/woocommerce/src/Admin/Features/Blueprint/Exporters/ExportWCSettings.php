<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Blueprint\Exporters;

use Automattic\Woo_Commerce\Admin\Features\Blueprint\Setting_Options;
use Automattic\Woo_Commerce\Blueprint\Exporters\Has_Alias;
use Automattic\Woo_Commerce\Blueprint\Exporters\Step_Exporter;
use Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options;
use Automattic\Woo_Commerce\Blueprint\Use_Wp_Functions;
/**
 * Class ExportWCSettings
 *
 * This abstract class provides the functionality for exporting WooCommerce settings on a specific page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
abstract class Export_Wc_Settings implements Step_Exporter, Has_Alias
{
    use Use_Wp_Functions;
    /**
     * The setting options class.
     */
    protected \Automattic\Woo_Commerce\Admin\Features\Blueprint\Setting_Options $setting_options;
    /**
     * Constructor.
     *
     * @param SettingOptions|null $setting_options The setting options class.
     */
    public function __construct(?Setting_Options $setting_options = null)
    {
        $this->setting_options = $setting_options ?? new Setting_Options();
    }
    /**
     * Return a page I.D to export.
     *
     * @return string The page ID.
     */
    abstract protected function get_page_id(): string;
    /**
     * Export WooCommerce settings.
     *
     * @return SetSiteOptions
     */
    public function export()
    {
        return new Set_Site_Options($this->setting_options->get_page_options($this->get_page_id()));
    }
    /**
     * Get the name of the step.
     *
     * @return string
     */
    public function get_step_name()
    {
        return 'setSiteOptions';
    }
    /**
     * Get the alias for this exporter.
     *
     * @return string
     */
    public function get_alias()
    {
        return 'setWCSettingsGeneral';
    }
    /**
     * Return label used in the frontend.
     *
     * @return string
     */
    public function get_label()
    {
        return __('General', 'woocommerce');
    }
    /**
     * Return description used in the frontend.
     *
     * @return string
     */
    public function get_description()
    {
        return __('Includes all settings in WooCommerce | Settings | General.', 'woocommerce');
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