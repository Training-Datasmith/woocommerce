<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Blueprint\Exporters;

use Automattic\Woo_Commerce\Admin\Features\Blueprint\Setting_Options;
use Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options;
use Automattic\Woo_Commerce\Blueprint\Use_Wp_Functions;
/**
 * Class ExportWCSettingsEmails
 *
 * This class exports WooCommerce settings on the Emails page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class Export_Wc_Settings_Emails extends Export_Wc_Settings
{
    use Use_Wp_Functions;
    /**
     * Get the alias for this exporter.
     */
    public function get_alias(): string
    {
        return 'setWCSettingsEmails';
    }
    /**
     * Export WooCommerce settings.
     */
    public function export(): \Automattic\Woo_Commerce\Blueprint\Steps\Set_Site_Options
    {
        $emails = \WC_Emails::instance();
        $setting_options = new Setting_Options();
        $email_settings = $setting_options->get_page_options($this->get_page_id());
        // Get sub-settings for each email.
        foreach ($emails->get_emails() as $email) {
            $email_settings = array_merge($email_settings, $setting_options->get_page_options('email_' . $email->id));
        }
        return new Set_Site_Options($email_settings);
    }
    /**
     * Return label used in the frontend.
     *
     * @return string
     */
    public function get_label()
    {
        return __('Emails', 'woocommerce');
    }
    /**
     * Return description used in the frontend.
     *
     * @return string
     */
    public function get_description()
    {
        return __('Includes all settings in WooCommerce | Settings | Emails.', 'woocommerce');
    }
    /**
     * Get the page ID for the settings page.
     */
    protected function get_page_id(): string
    {
        return 'email';
    }
}