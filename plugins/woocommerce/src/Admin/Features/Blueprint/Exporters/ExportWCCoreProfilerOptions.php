<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * ExportWCCoreProfilerOptions class
 */
class ExportWCCoreProfilerOptions implements StepExporter, HasAlias
{
    use UseWPFunctions;

    /**
     * Export the step
     */
    public function export(): \Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions
    {
        return new SetSiteOptions(
            [
                'blogname'                       => $this->wp_get_option('blogname'),
                'woocommerce_allow_tracking'     => $this->wp_get_option('woocommerce_allow_tracking'),
                'woocommerce_onboarding_profile' => $this->wp_get_option('woocommerce_onboarding_profile', []),
                'woocommerce_default_country'    => $this->wp_get_option('woocommerce_default_country'),
            ]
        );
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
