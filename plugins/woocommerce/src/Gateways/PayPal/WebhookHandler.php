<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Gateways\Pay_Pal;

use Automattic\Woo_Commerce\Enums\Order_Status;
use Automattic\Woo_Commerce\Gateways\Pay_Pal\Constants as PayPalConstants;
use Automattic\Woo_Commerce\Gateways\Pay_Pal\Helper as PayPalHelper;
use Automattic\Woo_Commerce\Gateways\Pay_Pal\Request as PayPalRequest;
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Class WebhookHandler file.
 *
 * Handles webhook events.
 *
 * @since 10.5.0
 */
class Webhook_Handler
{
    /**
     * Process the webhook event.
     *
     * @since 10.5.0
     *
     * @param \WP_REST_Request $request The request object.
     */
    public function process_webhook(\WP_REST_Request $request): void
    {
        $data = $request->get_json_params();
        if (!is_array($data) || empty($data['event_type']) || empty($data['resource'])) {
            \WC_Gateway_Paypal::log('Invalid PayPal webhook payload: ' . wc_print_r($data, true));
            return;
        }
        \WC_Gateway_Paypal::log('Webhook received: ' . wc_print_r(Pay_Pal_Helper::redact_data($data), true));
        match ($data['event_type']) {
            'CHECKOUT.ORDER.APPROVED' => $this->process_checkout_order_approved($data),
            'PAYMENT.CAPTURE.PENDING' => $this->process_payment_capture_pending($data),
            'PAYMENT.CAPTURE.COMPLETED' => $this->process_payment_capture_completed($data),
            'PAYMENT.AUTHORIZATION.CREATED' => $this->process_payment_authorization_created($data),
            default => \WC_Gateway_Paypal::log('Unhandled PayPal webhook event: ' . wc_print_r(Pay_Pal_Helper::redact_data($data), true)),
        };
    }
    /**
     * Process the CHECKOUT.ORDER.APPROVED webhook event.
     *
     * @since 10.5.0
     *
     * @param array $event The webhook event data.
     */
    private function process_checkout_order_approved(array $event): void
    {
        $custom_id = $event['resource']['purchase_units'][0]['custom_id'] ?? '';
        $order = Pay_Pal_Helper::get_wc_order_from_paypal_custom_id($custom_id);
        if (!$order) {
            \WC_Gateway_Paypal::log('Invalid order. Custom ID: ' . wc_print_r($custom_id, true));
            return;
        }
        // Skip if the payment is already processed.
        $paypal_status = $order->get_meta(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, true);
        if (in_array($paypal_status, [Pay_Pal_Constants::STATUS_COMPLETED, Pay_Pal_Constants::STATUS_APPROVED], true)) {
            return;
        }
        $status = $event['resource']['status'] ?? null;
        $paypal_order_id = $event['resource']['id'] ?? null;
        if (Pay_Pal_Constants::STATUS_APPROVED === $status) {
            \WC_Gateway_Paypal::log('PayPal payment approved. Order ID: ' . $order->get_id());
            $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, $status);
            // Clear the shipping callback token by setting it to an empty string.
            // This is done to prevent the token from being used again for the same order.
            // We are not deleting the meta key as we use the existence of the meta key to determine if the token was ever generated for this order.
            $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, '');
            $order->save();
            $order->add_order_note(sprintf(
                /* translators: %1$s: PayPal order ID */
                __('PayPal payment approved. PayPal Order ID: %1$s', 'woocommerce'),
                $paypal_order_id
            ));
            $order->save();
            // Update the addresses in the order with the addresses from the PayPal order details.
            Pay_Pal_Helper::update_addresses_in_order($order, $event['resource']);
            // Authorize or capture the payment after approval.
            $paypal_intent = $event['resource']['intent'] ?? null;
            $links = $event['resource']['links'] ?? null;
            $action = Pay_Pal_Constants::INTENT_CAPTURE === $paypal_intent ? Pay_Pal_Constants::PAYMENT_ACTION_CAPTURE : Pay_Pal_Constants::PAYMENT_ACTION_AUTHORIZE;
            $this->authorize_or_capture_payment($order, $links, $action);
        } else {
            // This is unexpected for a CHECKOUT.ORDER.APPROVED event.
            \WC_Gateway_Paypal::log('PayPal payment approval failed. Order ID: ' . $order->get_id() . ' Status: ' . $status);
            $order->add_order_note(sprintf(
                /* translators: %1$s: PayPal order ID, %2$s: Status */
                __('PayPal payment approval failed. PayPal Order ID: %1$s. Status: %2$s', 'woocommerce'),
                $paypal_order_id,
                $status
            ));
        }
    }
    /**
     * Process the PAYMENT.CAPTURE.COMPLETED webhook event.
     *
     * @since 10.5.0
     *
     * @param array $event The webhook event data.
     */
    private function process_payment_capture_completed(array $event): void
    {
        $custom_id = $event['resource']['custom_id'] ?? '';
        $order = Pay_Pal_Helper::get_wc_order_from_paypal_custom_id($custom_id);
        if (!$order) {
            \WC_Gateway_Paypal::log('Invalid order. Custom ID: ' . wc_print_r($custom_id, true));
            return;
        }
        // Skip if the payment is already processed.
        if (Pay_Pal_Constants::STATUS_COMPLETED === $order->get_meta(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, true)) {
            return;
        }
        $transaction_id = $event['resource']['id'] ?? null;
        $status = $event['resource']['status'] ?? null;
        $order->set_transaction_id($transaction_id);
        $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_CAPTURE_ID, $transaction_id);
        $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, $status);
        $order->payment_complete();
        $order->add_order_note(sprintf(
            /* translators: %1$s: Transaction ID */
            __('PayPal payment captured. Transaction ID: %1$s.', 'woocommerce'),
            $transaction_id
        ));
        $order->save();
    }
    /**
     * Process the PAYMENT.CAPTURE.PENDING webhook event.
     *
     * @since 10.5.0
     *
     * @param array $event The webhook event data.
     */
    private function process_payment_capture_pending(array $event): void
    {
        $custom_id = $event['resource']['custom_id'] ?? '';
        $order = Pay_Pal_Helper::get_wc_order_from_paypal_custom_id($custom_id);
        if (!$order) {
            \WC_Gateway_Paypal::log('Invalid order. Custom ID: ' . wc_print_r($custom_id, true));
            return;
        }
        // Skip if the payment is already processed.
        if (Pay_Pal_Constants::STATUS_COMPLETED === $order->get_meta(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, true)) {
            return;
        }
        $transaction_id = $event['resource']['id'] ?? null;
        $status = $event['resource']['status'] ?? null;
        $reason = $event['resource']['status_details']['reason'] ?? 'Unknown';
        $order->set_transaction_id($transaction_id);
        $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_CAPTURE_ID, $transaction_id);
        $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, $status);
        /* translators: %s: reason */
        $order->update_status(Order_Status::ON_HOLD, sprintf(__('Payment pending (reason: %s).', 'woocommerce'), $reason));
        $order->save();
    }
    /**
     * Process the PAYMENT.AUTHORIZATION.CREATED webhook event.
     *
     * @since 10.5.0
     *
     * @param array $event The webhook event data.
     */
    private function process_payment_authorization_created(array $event): void
    {
        $custom_id = $event['resource']['custom_id'] ?? '';
        $order = Pay_Pal_Helper::get_wc_order_from_paypal_custom_id($custom_id);
        if (!$order) {
            \WC_Gateway_Paypal::log('Invalid order. Custom ID: ' . wc_print_r($custom_id, true));
            return;
        }
        // Skip if the payment is already processed.
        if (Pay_Pal_Constants::STATUS_COMPLETED === $order->get_meta(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, true)) {
            return;
        }
        $transaction_id = $event['resource']['id'] ?? null;
        $order->set_transaction_id($transaction_id);
        $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_AUTHORIZATION_ID, $transaction_id);
        $order->update_meta_data(Pay_Pal_Constants::PAYPAL_ORDER_META_STATUS, Pay_Pal_Constants::STATUS_AUTHORIZED);
        $order->add_order_note(sprintf(
            /* translators: %1$s: Transaction ID */
            __('PayPal payment authorized. Transaction ID: %1$s. Change payment status to processing or complete to capture funds.', 'woocommerce'),
            $transaction_id
        ));
        $order->update_status(Order_Status::ON_HOLD);
        $order->save();
    }
    /**
     * Capture the payment.
     *
     * @since 10.5.0
     *
     * @param \WC_Order $order The order object.
     * @param array     $links The links from the webhook event.
     * @param string    $action The action to perform (capture or authorize).
     */
    private function authorize_or_capture_payment(\WC_Order $order, array $links, string $action): void
    {
        $action_url = $this->get_action_url($links, $action);
        $payment_gateways = WC()->payment_gateways()->payment_gateways();
        if (!isset($payment_gateways['paypal'])) {
            \WC_Gateway_Paypal::log('PayPal gateway is not available.');
            return;
        }
        $gateway = $payment_gateways['paypal'];
        $paypal_request = new Pay_Pal_Request($gateway);
        $paypal_request->authorize_or_capture_payment($order, $action_url, $action);
    }
    /**
     * Get the action URL from the links.
     *
     * @since 10.5.0
     *
     * @param array  $links The links from the webhook event.
     * @param string $action The action to perform (capture or authorize).
     */
    private function get_action_url(array $links, string $action): ?string
    {
        $action_url = null;
        foreach ($links as $link) {
            if ($action === $link['rel'] && 'POST' === $link['method'] && filter_var($link['href'], FILTER_VALIDATE_URL)) {
                $action_url = esc_url_raw($link['href']);
                break;
            }
        }
        return $action_url;
    }
}