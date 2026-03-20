<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Settings\Payments_Providers;

use Automattic\Woo_Commerce\Internal\Logging\Safe_Global_Function_Proxy;
use WC_Payment_Gateway;
defined('ABSPATH') || exit;
/**
 * MercadoPago payment gateway provider class.
 *
 * This class handles all the custom logic for the MercadoPago payment gateway provider.
 */
class Mercado_Pago extends Payment_Gateway
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
        $is_onboarded = $this->is_mercado_pago_onboarded($payment_gateway);
        if (!is_null($is_onboarded)) {
            return !$is_onboarded;
        }
        return parent::needs_setup($payment_gateway);
    }
    /**
     * Try to determine if the payment gateway is in test mode.
     *
     * This is a best-effort attempt, as there is no standard way to determine this.
     * Trust the true value, but don't consider a false value as definitive.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return bool True if the payment gateway is in test mode, false otherwise.
     */
    public function is_in_test_mode(WC_Payment_Gateway $payment_gateway): bool
    {
        return $this->is_mercado_pago_in_sandbox_mode($payment_gateway) ?? parent::is_in_test_mode($payment_gateway);
    }
    /**
     * Try to determine if the payment gateway is in dev mode.
     *
     * This is a best-effort attempt, as there is no standard way to determine this.
     * Trust the true value, but don't consider a false value as definitive.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return bool True if the payment gateway is in dev mode, false otherwise.
     */
    public function is_in_dev_mode(WC_Payment_Gateway $payment_gateway): bool
    {
        return $this->is_mercado_pago_in_sandbox_mode($payment_gateway) ?? parent::is_in_dev_mode($payment_gateway);
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
        return $this->is_mercado_pago_onboarded($payment_gateway) ?? parent::is_account_connected($payment_gateway);
    }
    /**
     * Check if the payment gateway has completed the onboarding process.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return bool True if the payment gateway has completed the onboarding process, false otherwise.
     *              If the payment gateway does not provide the information,
     *              it will infer it from having a connected account.
     */
    public function is_onboarding_completed(WC_Payment_Gateway $payment_gateway): bool
    {
        return $this->is_mercado_pago_onboarded($payment_gateway) ?? parent::is_onboarding_completed($payment_gateway);
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
        return $this->is_mercado_pago_in_sandbox_mode($payment_gateway) ?? parent::is_in_test_mode_onboarding($payment_gateway);
    }
    /**
     * Check if the MercadoPago payment gateway is in sandbox mode.
     *
     * For MercadoPago, there are two different environments: sandbox and production.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
     *               Null if the environment could not be determined.
     */
    private function is_mercado_pago_in_sandbox_mode(WC_Payment_Gateway $payment_gateway): ?bool
    {
        global $mercadopago;
        try {
            // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
            if (class_exists('\MercadoPago\Woocommerce\WoocommerceMercadoPago') && class_exists('\MercadoPago\Woocommerce\Configs\Store') && $mercadopago instanceof \Mercado_Pago\Woocommerce\Woocommerce_Mercado_Pago && !is_null($mercadopago->store_config) && $mercadopago->store_config instanceof \Mercado_Pago\Woocommerce\Configs\Store && is_callable([$mercadopago->store_config, 'isTestMode'])) {
                return wc_string_to_bool($mercadopago->store_config->is_test_mode());
            }
        } catch (\Throwable $e) {
            // Do nothing but log so we can investigate.
            Safe_Global_Function_Proxy::wc_get_logger()->debug('Failed to determine if gateway is in sandbox mode: ' . $e->get_message(), ['gateway' => $payment_gateway->id, 'source' => 'settings-payments', 'exception' => $e]);
        }
        // Let the caller know that we couldn't determine the environment.
        return null;
    }
    /**
     * Check if the MercadoPago payment gateway is onboarded.
     *
     * For MercadoPago, there are two different environments: sandbox/test and production/sale.
     *
     * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
     *
     * @return ?bool True if the payment gateway is onboarded, false otherwise.
     *               Null if we failed to determine the onboarding status.
     */
    private function is_mercado_pago_onboarded(WC_Payment_Gateway $payment_gateway): ?bool
    {
        global $mercadopago;
        try {
            // phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
            if (class_exists('\MercadoPago\Woocommerce\WoocommerceMercadoPago') && class_exists('\MercadoPago\Woocommerce\Configs\Seller') && $mercadopago instanceof \Mercado_Pago\Woocommerce\Woocommerce_Mercado_Pago && !is_null($mercadopago->seller_config) && $mercadopago->seller_config instanceof \Mercado_Pago\Woocommerce\Configs\Seller && is_callable([$mercadopago->seller_config, 'getCredentialsPublicKey']) && is_callable([$mercadopago->seller_config, 'getCredentialsAccessToken'])) {
                return !empty($mercadopago->seller_config->get_credentials_public_key()) && !empty($mercadopago->seller_config->get_credentials_access_token());
            }
        } catch (\Throwable $e) {
            // Do nothing but log so we can investigate.
            Safe_Global_Function_Proxy::wc_get_logger()->debug('Failed to determine if gateway is onboarded: ' . $e->get_message(), ['gateway' => $payment_gateway->id, 'source' => 'settings-payments', 'exception' => $e]);
        }
        // Let the caller know that we couldn't determine the onboarding status.
        return null;
    }
}