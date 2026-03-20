<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Blueprint\Exporters;

use Automattic\Woo_Commerce\Blueprint\Use_Wp_Functions;
/**
 * Class ExportWCSettingsGeneral
 *
 * This class exports WooCommerce settings on the General page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class Export_Wc_Settings_General extends Export_Wc_Settings
{
    use Use_Wp_Functions;
    /**
     * Get the alias for this exporter.
     */
    public function get_alias(): string
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
     * Get the page ID for the settings page.
     */
    protected function get_page_id(): string
    {
        return 'general';
    }
}