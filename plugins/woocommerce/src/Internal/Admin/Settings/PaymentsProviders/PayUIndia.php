<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Settings\Payments_Providers;

use Automattic\Woo_Commerce\Internal\Logging\Safe_Global_Function_Proxy;
use Throwable;
use WC_Payment_Gateway;
defined('ABSPATH') || exit;
/**
 * PayU India payment gateway provider class.
 *
 * This class handles all the custom logic for the PayU India payment gateway provider.
 */
class Pay_U_India extends Payment_Gateway
{
    /**
     * Check if the payment gateway has a payments processor account connected.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return bool True if the payment gateway account is connected, false otherwise.
     *              If the payment gateway does not provide the information, it will return true.
     */
    public function is_account_connected(WC_Payment_Gateway $payment_gateway): bool
    {
        try {
            return !empty($payment_gateway->get_option('currency1_payu_key')) && !empty($payment_gateway->get_option('currency1_payu_salt'));
        } catch (Throwable $e) {
            // Do nothing but log so we can investigate.
            Safe_Global_Function_Proxy::wc_get_logger()->debug('Failed to determine if gateway has an account connected: ' . $e->get_message(), ['gateway' => $payment_gateway->id, 'source' => 'settings-payments', 'exception' => $e]);
        }
        return parent::is_account_connected($payment_gateway);
    }
}