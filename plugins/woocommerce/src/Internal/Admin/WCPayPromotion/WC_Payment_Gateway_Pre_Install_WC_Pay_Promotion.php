<?php

declare (strict_types=1);
/**
 * Class WCPaymentGatewayPreInstallWCPayPromotion
 *
 * @package WooCommerce\Admin
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Wc_Pay_Promotion;

use Automattic\Woo_Commerce\Enums\Payment_Gateway_Feature;
if (!defined('ABSPATH')) {
    exit;
}
/**
 * A pseudo WCPay gateway class.
 *
 * @extends \WC_Payment_Gateway
 */
class Wc_Payment_Gateway_Pre_Install_Wc_Pay_Promotion extends \WC_Payment_Gateway
{
    public const GATEWAY_ID = 'pre_install_woocommerce_payments_promotion';
    /**
     * Constructor
     */
    public function __construct()
    {
        $wc_pay_spec = Init::get_wc_pay_promotion_spec();
        if (!$wc_pay_spec) {
            return;
        }
        $this->id = static::GATEWAY_ID;
        $this->method_title = $wc_pay_spec->title;
        if (property_exists($wc_pay_spec, 'sub_title')) {
            $this->title = sprintf('<span class="gateway-subtitle" >%s</span>', $wc_pay_spec->sub_title);
        }
        $this->method_description = $wc_pay_spec->content;
        $this->has_fields = false;
        // Set the promotion pseudo-gateway support features.
        // If the promotion spec provides the supports property, use it.
        if (property_exists($wc_pay_spec, 'supports')) {
            $this->supports = $wc_pay_spec->supports;
        } else {
            // Otherwise, use the default supported features in line with WooPayments ones.
            // We include all features here, even if some of them are behind settings, since this is for info only.
            $this->supports = [
                // Regular features.
                Payment_Gateway_Feature::PRODUCTS,
                Payment_Gateway_Feature::REFUNDS,
                // Subscriptions features.
                Payment_Gateway_Feature::SUBSCRIPTIONS,
                Payment_Gateway_Feature::MULTIPLE_SUBSCRIPTIONS,
                Payment_Gateway_Feature::SUBSCRIPTION_CANCELLATION,
                Payment_Gateway_Feature::SUBSCRIPTION_REACTIVATION,
                Payment_Gateway_Feature::SUBSCRIPTION_SUSPENSION,
                Payment_Gateway_Feature::SUBSCRIPTION_AMOUNT_CHANGES,
                Payment_Gateway_Feature::SUBSCRIPTION_DATE_CHANGES,
                Payment_Gateway_Feature::SUBSCRIPTION_PAYMENT_METHOD_CHANGE_ADMIN,
                Payment_Gateway_Feature::SUBSCRIPTION_PAYMENT_METHOD_CHANGE_CUSTOMER,
                Payment_Gateway_Feature::SUBSCRIPTION_PAYMENT_METHOD_CHANGE,
                // Saved cards features.
                Payment_Gateway_Feature::TOKENIZATION,
                Payment_Gateway_Feature::ADD_PAYMENT_METHOD,
            ];
        }
        // Get setting values.
        $this->enabled = false;
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
    }
    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields(): void
    {
        $this->form_fields = ['is_dismissed' => ['title' => __('Dismiss', 'woocommerce'), 'type' => 'checkbox', 'label' => __('Dismiss the gateway', 'woocommerce'), 'default' => 'no']];
    }
    /**
     * Check if the promotional gateway has been dismissed.
     */
    public static function is_dismissed(): bool
    {
        $settings = get_option('woocommerce_' . self::GATEWAY_ID . '_settings', []);
        return isset($settings['is_dismissed']) && 'yes' === $settings['is_dismissed'];
    }
}