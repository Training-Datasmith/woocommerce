<?php

/**
 * PayPal Gateway Constants.
 *
 * Provides constants for PayPal payment statuses, intents, and other PayPal-related values.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants instead. This class will be removed in 11.0.0.
 * @version    10.3.0
 * @package    WooCommerce\Gateways
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

/**
 * WC_Gateway_Paypal_Constants Class.
 *
 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants instead. This class will be removed in 11.0.0.
 */
class WC_Gateway_Paypal_Constants
{
    /**
     * PayPal proxy request timeout.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::WPCOM_PROXY_REQUEST_TIMEOUT instead.
     */
    public const WPCOM_PROXY_REQUEST_TIMEOUT = PayPalConstants::WPCOM_PROXY_REQUEST_TIMEOUT;

    /**
     * PayPal payment statuses.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::STATUS_* instead.
     */
    public const STATUS_COMPLETED             = PayPalConstants::STATUS_COMPLETED;
    public const STATUS_APPROVED              = PayPalConstants::STATUS_APPROVED;
    public const STATUS_CAPTURED              = PayPalConstants::STATUS_CAPTURED;
    public const STATUS_AUTHORIZED            = PayPalConstants::STATUS_AUTHORIZED;
    public const STATUS_PAYER_ACTION_REQUIRED = PayPalConstants::STATUS_PAYER_ACTION_REQUIRED;
    public const VOIDED                       = PayPalConstants::VOIDED;

    /**
     * PayPal payment intents.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::INTENT_* instead.
     */
    public const INTENT_CAPTURE   = PayPalConstants::INTENT_CAPTURE;
    public const INTENT_AUTHORIZE = PayPalConstants::INTENT_AUTHORIZE;

    /**
     * PayPal payment actions.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::PAYMENT_ACTION_* instead.
     */
    public const PAYMENT_ACTION_CAPTURE   = PayPalConstants::PAYMENT_ACTION_CAPTURE;
    public const PAYMENT_ACTION_AUTHORIZE = PayPalConstants::PAYMENT_ACTION_AUTHORIZE;

    /**
     * PayPal shipping preferences.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::SHIPPING_* instead.
     */
    public const SHIPPING_NO_SHIPPING          = PayPalConstants::SHIPPING_NO_SHIPPING;
    public const SHIPPING_GET_FROM_FILE        = PayPalConstants::SHIPPING_GET_FROM_FILE;
    public const SHIPPING_SET_PROVIDED_ADDRESS = PayPalConstants::SHIPPING_SET_PROVIDED_ADDRESS;

    /**
     * PayPal user actions.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::USER_ACTION_* instead.
     */
    public const USER_ACTION_PAY_NOW = PayPalConstants::USER_ACTION_PAY_NOW;

    /**
     * Maximum lengths for PayPal fields.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::PAYPAL_* instead.
     */
    public const PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH = PayPalConstants::PAYPAL_ORDER_ITEM_NAME_MAX_LENGTH;
    public const PAYPAL_INVOICE_ID_MAX_LENGTH      = PayPalConstants::PAYPAL_INVOICE_ID_MAX_LENGTH;
    public const PAYPAL_ADDRESS_LINE_MAX_LENGTH    = PayPalConstants::PAYPAL_ADDRESS_LINE_MAX_LENGTH;
    public const PAYPAL_COUNTRY_CODE_LENGTH        = PayPalConstants::PAYPAL_COUNTRY_CODE_LENGTH;
    public const PAYPAL_STATE_MAX_LENGTH           = PayPalConstants::PAYPAL_STATE_MAX_LENGTH;
    public const PAYPAL_CITY_MAX_LENGTH            = PayPalConstants::PAYPAL_CITY_MAX_LENGTH;
    public const PAYPAL_POSTAL_CODE_MAX_LENGTH     = PayPalConstants::PAYPAL_POSTAL_CODE_MAX_LENGTH;
    public const PAYPAL_LOCALE_MAX_LENGTH          = PayPalConstants::PAYPAL_LOCALE_MAX_LENGTH;

    /**
     * Supported payment sources.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::PAYMENT_SOURCE_* instead.
     */
    public const PAYMENT_SOURCE_PAYPAL     = PayPalConstants::PAYMENT_SOURCE_PAYPAL;
    public const PAYMENT_SOURCE_VENMO      = PayPalConstants::PAYMENT_SOURCE_VENMO;
    public const PAYMENT_SOURCE_PAYLATER   = PayPalConstants::PAYMENT_SOURCE_PAYLATER;
    public const SUPPORTED_PAYMENT_SOURCES = PayPalConstants::SUPPORTED_PAYMENT_SOURCES;

    /**
     * Fields to redact from logs.
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::FIELDS_TO_REDACT instead.
     * @var array
     */
    public const FIELDS_TO_REDACT = PayPalConstants::FIELDS_TO_REDACT;

    /**
     * List of currencies supported by PayPal (Orders API V2).
     *
     * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\Constants::SUPPORTED_CURRENCIES instead.
     * @var array<string>
     */
    public const SUPPORTED_CURRENCIES = PayPalConstants::SUPPORTED_CURRENCIES;
}
