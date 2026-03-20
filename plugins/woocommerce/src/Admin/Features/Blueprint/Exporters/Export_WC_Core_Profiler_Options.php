<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Blueprint\Exporters;

use Automattic\Woo_Commerce\Blueprint\Exporters\Has_Alias;
use Automattic\Woo_Commerce\Blueprint\Exporters\Step_Exporter;
use Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options;
use Automattic\Woo_Commerce\Blueprint\Use_Wp_Functions;
/**
 * ExportWCCoreProfilerOptions class
 */
class Export_Wc_Core_Profiler_Options implements Step_Exporter, Has_Alias
{
    use Use_Wp_Functions;
    /**
     * Export the step
     */
    public function export(): \Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options
    {
        return new Set_Site_Options(['blogname' => $this->wp_get_option('blogname'), 'woocommerce_allow_tracking' => $this->wp_get_option('woocommerce_allow_tracking'), 'woocommerce_onboarding_profile' => $this->wp_get_option('woocommerce_onboarding_profile', []), 'woocommerce_default_country' => $this->wp_get_option('woocommerce_default_country')]);
    }
    /**
     * Get the step name
     */
    public function get_step_name(): string
    {
        return 'setSiteOptions';
    }
    /**
     * Get the alias
     */
    public function get_alias(): string
    {
        return 'setWCCoreProfilerOptions';
    }
    /**
     * Return label used in the frontend.
     *
     * @return string
     */
    public function get_label()
    {
        return __('Onboarding Configuration', 'woocommerce');
    }
    /**
     * Return description used in the frontend.
     *
     * @return string
     */
    public function get_description()
    {
        return __('Includes onboarding configuration options', 'woocommerce');
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