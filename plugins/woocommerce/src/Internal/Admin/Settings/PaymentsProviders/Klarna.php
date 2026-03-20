<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Settings\Payments_Providers;

use Automattic\Woo_Commerce\Internal\Logging\Safe_Global_Function_Proxy;
use Throwable;
use WC_Payment_Gateway;
defined('ABSPATH') || exit;
/**
 * Klarna payment gateway provider class.
 *
 * This class handles all the custom logic for the Klarna payment gateway provider.
 */
class Klarna extends Payment_Gateway
{
    /**
     * Check if the payment gateway needs setup.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return bool True if the payment gateway needs setup, false otherwise.
     */
    public function needs_setup(WC_Payment_Gateway $payment_gateway): bool
    {
        try {
            if (class_exists('\KP_Settings_Page') && is_callable('\KP_Settings_Page::get_setting_status')) {
                return !wc_string_to_bool(\KP_Settings_Page::get_setting_status('credentials'));
            }
        } catch (Throwable $e) {
            // Do nothing but log so we can investigate.
            Safe_Global_Function_Proxy::wc_get_logger()->debug('Failed to determine if gateway needs setup: ' . $e->get_message(), ['gateway' => $payment_gateway->id, 'source' => 'settings-payments', 'exception' => $e]);
        }
        return parent::needs_setup($payment_gateway);
    }
}