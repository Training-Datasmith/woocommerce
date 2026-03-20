<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Blueprint\Exporters;

use Automattic\Woo_Commerce\Blueprint\Steps\Run_Sql;
use Automattic\Woo_Commerce\Blueprint\Use_Wp_Functions;
use Automattic\Woo_Commerce\Blueprint\Util;
/**
 * Class ExportWCSettingsTax
 *
 * This class exports WooCommerce settings on the Tax page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class Export_Wc_Settings_Tax extends Export_Wc_Settings
{
    use Use_Wp_Functions;
    /**
     * Get the alias for this exporter.
     */
    public function get_alias(): string
    {
        return 'setWCSettingsTax';
    }
    /**
     * Export WooCommerce tax rates.
     *
     * @return array array of steps
     */
    public function export(): array
    {
        $basic_tax_settings = parent::export();
        return [$basic_tax_settings, ...$this->generate_tax_rate_steps('wc_tax_rate_classes'), ...$this->generate_tax_rate_steps('woocommerce_tax_rates'), ...$this->generate_tax_rate_steps('woocommerce_tax_rate_locations')];
    }
    /**
     * Return label used in the frontend.
     *
     * @return string
     */
    public function get_label()
    {
        return __('Tax', 'woocommerce');
    }
    /**
     * Return description used in the frontend.
     *
     * @return string
     */
    public function get_description()
    {
        return __('Includes all settings in WooCommerce | Settings | Tax.', 'woocommerce');
    }
    /**
     * Get the page ID for the settings page.
     */
    protected function get_page_id(): string
    {
        return 'tax';
    }
    /**
     * Generate SQL steps for exporting data.
     *
     * @param string $table Table identifier.
     * @return array Array of RunSql steps.
     */
    private function generate_tax_rate_steps(string $table): array
    {
        global $wpdb;
        $table = $wpdb->prefix . $table;
        return array_map(fn($record): \Automattic\Woo_Commerce\Blueprint\Steps\Run_Sql => new Run_Sql(Util::array_to_insert_sql($record, $table, 'replace into')), $wpdb->get_results($wpdb->prepare('SELECT * FROM %i', $table), ARRAY_A));
    }
}