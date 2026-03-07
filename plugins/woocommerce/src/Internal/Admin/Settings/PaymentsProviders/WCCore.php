<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Gateway_Paypal;
use WC_Payment_Gateway;

defined('ABSPATH') || exit;

/**
 * WooCommerce core payment gateways provider class.
 *
 * This class handles all the custom logic for the payment gateways built into the WC core.
 */
class WCCore extends PaymentGateway
{
    /**
     * Get the provider icon URL of the payment gateway.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return string The provider icon URL of the payment gateway.
     */
    public function get_icon(WC_Payment_Gateway $payment_gateway): string
    {
        // Provide custom icons for core payment gateways.
        return match ($payment_gateway->id) {
            WC_Gateway_BACS::ID => plugins_url('assets/images/payment_methods/bacs.svg', WC_PLUGIN_FILE),
            WC_Gateway_Cheque::ID => plugins_url('assets/images/payment_methods/cheque.svg', WC_PLUGIN_FILE),
            WC_Gateway_COD::ID => plugins_url('assets/images/payment_methods/cod.svg', WC_PLUGIN_FILE),
            WC_Gateway_Paypal::ID => plugins_url('assets/images/payment_methods/72x72/paypal.png', WC_PLUGIN_FILE),
            default => parent::get_icon($payment_gateway),
        };
    }

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
        // Provide custom account connected logic for core payment gateways.
        return match ($payment_gateway->id) {
            // BACS requires bank account details to be set up.
            WC_Gateway_BACS::ID => property_exists($payment_gateway, 'account_details') && ! empty($payment_gateway->account_details),
            // There is no account setup for these gateways, so we return true.
            WC_Gateway_Cheque::ID, WC_Gateway_COD::ID => true,
            // PayPal requires just an account email address to be set up.
            WC_Gateway_Paypal::ID => property_exists($payment_gateway, 'email') && is_email($payment_gateway->email),
            default => parent::is_account_connected($payment_gateway),
        };
    }

    /**
     * Try to determine if the payment gateway is in test mode onboarding (aka sandbox or test-drive).
     *
     * This is a best-effort attempt, as there is no standard way to determine this.
     * Trust the true value, but don't consider a false value as definitive.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
     */
    public function is_in_test_mode_onboarding(WC_Payment_Gateway $payment_gateway): bool
    {
        // Provide custom test mode onboarding logic for core payment gateways.
        return match ($payment_gateway->id) {
            WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID => false,
            // Test mode is actually sandbox mode for PayPal, affecting the API keys used.
            WC_Gateway_Paypal::ID => $this->is_in_test_mode($payment_gateway),
            default => parent::is_in_test_mode_onboarding($payment_gateway),
        };
    }

    /**
     * Get the plugin details for a WC core-provided payment gateway.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return array The plugin details for the payment gateway.
     */
    public function get_plugin_details(WC_Payment_Gateway $payment_gateway): array
    {
        $plugin_details = parent::get_plugin_details($payment_gateway);

        // Since these are core-provided gateways, we need to make sure that the provider (WC) can't be deactivated.
        // The way to do this is to NOT provide a plugin file path.
        $plugin_details['file'] = '';

        return $plugin_details;
    }
}
