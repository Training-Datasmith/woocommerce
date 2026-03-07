<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportWCTaskOptions
 *
 * This class exports WooCommerce task options.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCTaskOptions implements StepExporter, HasAlias
{
    use UseWPFunctions;

    /**
     * Export WooCommerce task options.
     */
    public function export(): \Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions
    {
        return new SetSiteOptions(
            [
                'woocommerce_admin_customize_store_completed' => $this->wp_get_option('woocommerce_admin_customize_store_completed', 'no'),
                'woocommerce_task_list_tracked_completed_actions' => $this->wp_get_option('woocommerce_task_list_tracked_completed_actions', []),
            ]
        );
    }

    /**
     * Get the name of the step.
     */
    public function get_step_name(): string
    {
        return 'setOptions';
    }

    /**
     * Get the alias for this exporter.
     */
    public function get_alias(): string
    {
        return 'setWCTaskOptions';
    }

    /**
     * Return label used in the frontend.
     *
     * @return string
     */
    public function get_label()
    {
        return __('Task Configurations', 'woocommerce');
    }

    /**
     * Return description used in the frontend.
     *
     * @return string
     */
    public function get_description()
    {
        return __('Includes the task configurations for WooCommerce.', 'woocommerce');
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
