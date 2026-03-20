<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Payments\Integrations;

use Automattic\Woo_Commerce\Blocks\Assets\Api;
use WC_Gateway_BACS;
/**
 * Bank Transfer (BACS) payment method integration
 *
 * @since 3.0.0
 */
final class Bank_Transfer extends Abstract_Payment_Method_Type
{
    /**
     * Payment method name/id/slug (matches id in WC_Gateway_BACS in core).
     *
     * @var string
     */
    protected $name = WC_Gateway_BACS::ID;
    /**
     * Constructor
     *
     * @param Api $asset_api An instance of Api.
     */
    public function __construct(
        /**
         * An instance of the Asset Api
         */
        private readonly Api $asset_api
    )
    {
    }
    /**
     * Initializes the payment method type.
     */
    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_bacs_settings', []);
    }
    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     */
    public function is_active(): bool
    {
        return filter_var($this->get_setting('enabled', false), FILTER_VALIDATE_BOOLEAN);
    }
    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     */
    public function get_payment_method_script_handles(): array
    {
        $this->asset_api->register_script('wc-payment-method-bacs', 'assets/client/blocks/wc-payment-method-bacs.js');
        return ['wc-payment-method-bacs'];
    }
    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     */
    public function get_payment_method_data(): array
    {
        return ['title' => $this->get_setting('title'), 'description' => $this->get_setting('description'), 'supports' => $this->get_supported_features()];
    }
}