<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Suggestions;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Internal\Admin\Onboarding\Onboarding_Profile;
use Automattic\Woo_Commerce\Internal\Admin\Settings\Payments;
use Automattic\Woo_Commerce\Internal\Admin\Settings\Payments_Providers;
use Automattic\Woo_Commerce\Internal\Utilities\Array_Util;
/**
 * Partner payments extension suggestions provider class.
 *
 * @internal
 */
class Payments_Extension_Suggestions
{
    /*
     * The unique IDs for the payment extension suggestions.
     *
     * The ID is the primary extension identifier throughout the system.
     */
    public const AIRWALLEX = 'airwallex';
    public const ANTOM = 'antom';
    public const MERCADO_PAGO = 'mercado_pago';
    public const MOLLIE = 'mollie';
    public const PAYFAST = 'payfast';
    public const PAYMOB = 'paymob';
    public const PAYPAL_FULL_STACK = 'paypal_full_stack';
    public const PAYPAL_WALLET = 'paypal_wallet';
    public const PAYONEER = 'payoneer';
    public const PAYSTACK = 'paystack';
    public const PAYTRAIL = 'paytrail';
    public const PAYU_INDIA = 'payu_india';
    public const RAZORPAY = 'razorpay';
    public const SQUARE = 'square';
    public const STRIPE = 'stripe';
    public const TILOPAY = 'tilopay';
    public const VIVA_WALLET = 'viva_wallet';
    public const WOOPAYMENTS = 'woopayments';
    public const AMAZON_PAY = 'amazon_pay';
    public const AFFIRM = 'affirm';
    public const AFTERPAY = 'afterpay';
    public const CLEARPAY = 'clearpay';
    public const KLARNA = 'klarna';
    public const KLARNA_CHECKOUT = 'klarna_checkout';
    public const HELIOPAY = 'heliopay';
    public const MONEI = 'monei';
    public const COINBASE = 'coinbase';
    public const BILLIE = 'billie';
    public const BOLT = 'bolt_checkout';
    public const AUTHORIZE_NET = 'authorize_net';
    public const DEPAY = 'depay';
    public const ELAVON = 'elavon';
    public const EWAY = 'eway';
    public const FORTISPAY = 'fortis';
    public const GOCARDLESS = 'gocardless';
    public const NEXI_CHECKOUT = 'nexi_checkout';
    public const PAYPAL_ZETTLE = 'paypal_zettle';
    public const RAPYD = 'rapyd';
    public const PAYPAL_BRAINTREE = 'paypal_braintree';
    public const VISA = 'visa_as';
    public const NGENIUS = 'ngenius';
    /*
     * The extension types.
     *
     * The type is related to the extension's underlying payments methods scope and type.
     */
    public const TYPE_PSP = 'psp';
    // Payment Service Provider.
    public const TYPE_APM = 'apm';
    // Alternative Payment Methods.
    public const TYPE_EXPRESS_CHECKOUT = 'express_checkout';
    public const TYPE_BNPL = 'bnpl';
    // Buy now, pay later.
    public const TYPE_CRYPTO = 'crypto';
    /*
     * The extension plugin types.
     *
     * This will inform how we handle the extension installation and activation.
     */
    public const PLUGIN_TYPE_WPORG = 'wporg';
    /*
     * Extension tags.
     *
     * These are used to categorize the extensions and provide additional information to the system.
     * Some tags may carry special meaning and will be used to influence the suggestions' behavior.
     */
    public const TAG_PREFERRED = 'preferred';
    public const TAG_PREFERRED_OFFLINE = 'preferred_offline';
    // For extensions that are preferred for offline payments.
    public const TAG_MADE_IN_WOO = 'made_in_woo';
    // For extensions developed by Woo.
    public const TAG_RECOMMENDED = 'recommended';
    // For extensions that should be further emphasized.
    /**
     * The memoized extensions base details to avoid computing them multiple times during a request.
     */
    private ?array $extensions_base_details_memo = null;
    /**
     * The payment extension list for each country.
     *
     * The order is important as it will be used to determine the priority of the suggestions.
     *
     * Each entry is keyed by the two-letter country code and consists of a list of payment extensions.
     * Each payment extension can be identified by its ID (the shorthand version) or by an array with the following format:
     * array(
     *   'id' => 'woopayments', // This is required.
     *   '_type' => 'provider', // Overrides the '_type' key.
     *   // Special entry that instructs the system to append the given items to a list-type entry.
     *   // If the original entry is not a list, we will throw an exception.
     *   // If the original entry does not exist, we will create it.
     *   // This is useful when you want to add tags to a suggestion's default list of tags.
     *   '_append' => array(
     *       'tags' => array( self::TAG_PREFERRED ),
     *   ),
     *   // Special entry that instructs the system to remove the given items from a list-type entry.
     *   // If the original entry is not a list, we will throw an exception.
     *   // If the original entry does not exist, we will ignore the instruction.
     *   // This is useful when you want to remove tags from a suggestion's default list of tags.
     *   '_remove' => array(
     *       'tags' => array( self::TAG_PREFERRED ),
     *   ),
     *   // Special entry that instructs the system to merge a list of items based on their _type key value,
     *   // overriding the original entry with the provided one.
     *   // If the original entry is not a list of arrays each with a _type entry, we will throw an exception.
     *   // If the provided entry is not a list of arrays each with a _type entry, we will throw an exception.
     *   // If the original entry does not exist, we will create it.
     *   // This is useful when you want to override certain default details for a particular country.
     *   '_merge_on_type' => array(
     *       'links' => array(
     *           array(
     *               _type' => self::LINK_TYPE_PRICING,
     *               'url'  => 'https://www.example.com/pricing',
     *           ),
     *       ),
     *   ),
     * )
     * Use the extended format when you need to override the extension's default details for a particular country.
     *
     * @see plugins/woocommerce/i18n/countries.php for the list of supported country codes and their names.
     */
    private array $country_extensions = [
        // North America.
        'CA' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::SQUARE => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/ca/en/pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/ca/en/legal/general/ua']]]], self::VISA, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-ca/pricing/']]]], self::PAYPAL_WALLET, self::AFFIRM, self::AFTERPAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/ca/business/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/ca/legal/']]]]],
        'PM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'US' => [
            self::WOOPAYMENTS => ['_append' => ['tags' => ['woopay_eligible']]],
            self::PAYPAL_FULL_STACK,
            self::STRIPE,
            self::SQUARE,
            // Use the default details.
            self::VISA,
            self::AIRWALLEX,
            self::PAYPAL_WALLET,
            self::AMAZON_PAY,
            self::AFFIRM,
            self::AFTERPAY,
            self::KLARNA,
        ],
        'UM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        // UK + Europe.
        'GB' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::SQUARE => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/gb/en/pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/gb/en/legal/general/ua']]]], self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/uk/business/payment-methods/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/uk/terms-and-conditions/']]]], self::PAYPAL_WALLET, self::AMAZON_PAY, self::AFFIRM => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.affirm.com/en-gb/business'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.affirm.com/en-gb/terms']]]], self::CLEARPAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/uk/business/payment-methods/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/uk/terms-and-conditions/']]]]],
        'AX' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'AL' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'AD' => [self::MONEI, self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::VISA],
        'AM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'AT' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-ie/pricing/']]]], self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/at/verkaeufer/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/at/agb/']]]], self::NEXI_CHECKOUT, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/at/verkaeufer/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/at/agb/']]]]],
        'BY' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BE' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-ie/pricing/']]]], self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/be/fr/entreprise/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/be/fr/conditions-generales/']]]]],
        'BA' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BV' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BG' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::PAYPAL_WALLET],
        'HR' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-ie/pricing/']]]], self::PAYPAL_WALLET],
        'CY' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-ie/pricing/']]]], self::PAYPAL_WALLET, self::AMAZON_PAY],
        'CZ' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/cz/firmy/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/cz/obchodni-podminky/']]]]],
        'DK' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/da-dk/priser/']]]], self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/dk/erhverv/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/dk/vilkar/']]]], self::NEXI_CHECKOUT, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/dk/erhverv/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/dk/vilkar/']]]]],
        'EE' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-ie/pricing/']]]], self::PAYPAL_WALLET],
        'FI' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-ie/pricing/']]]], self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/fi/yritys/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/fi/ehdot/']]]], self::PAYTRAIL, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/fi/yritys/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/fi/ehdot/']]]]],
        'FO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'FR' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::SQUARE => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/fr/fr/pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/fr/fr/legal/general/ua']]]], self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/fr-fr/tarifs/']]]], self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/fr/entreprise/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/fr/legal/']]]]],
        'PF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'GI' => [self::STRIPE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET],
        'DE' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/de-de/preise/']]]], self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/de/verkaeufer/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/de/agb/']]]], self::NEXI_CHECKOUT, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/de/verkaeufer/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/de/agb/']]]]],
        'GR' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/gr/business/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/gr/oroi-kai-proypotheseis/']]]]],
        'GL' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'GG' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'VA' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'HU' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/hu/uzlet/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/hu/jogi-informaciok/']]]]],
        'IS' => [self::MOLLIE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::VISA],
        'IE' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::SQUARE => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/ie/en/pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/ie/en/legal/general/ua']]]], self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/ie/business/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/ie/terms-and-conditions/']]]]],
        'IM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'IT' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/it/aziende/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/it/legal/']]]]],
        'JE' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'LV' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::PAYPAL_WALLET],
        'LI' => [self::STRIPE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::MOLLIE, self::VISA, self::PAYPAL_WALLET],
        'LT' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::AIRWALLEX, self::PAYPAL_WALLET],
        'LU' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::PAYPAL_WALLET, self::AMAZON_PAY],
        'MT' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::PAYPAL_WALLET],
        'MD' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'MC' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'ME' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NL' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/nl/zakelijk/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/nl/voorwaarden/']]]], self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/nl/zakelijk/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/nl/voorwaarden/']]]]],
        'MK' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NO' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/no/bedrift/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/no/vilkar/']]]], self::NEXI_CHECKOUT, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/no/bedrift/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/no/vilkar/']]]]],
        'PL' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/pl/biznes/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/pl/zasady-i-warunki/']]]]],
        'PT' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::AIRWALLEX, self::VIVA_WALLET, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/pt/empresa/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/pt/termos-e-condicoes/']]]]],
        'RO' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/ro/companii/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/ro/aspecte-juridice/']]]]],
        'RU' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'RS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SK' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/sk/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/sk/zmluvne-podmienky/']]]]],
        'SI' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::PAYPAL_WALLET],
        'ES' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::SQUARE => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/es/es/pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/es/es/legal/general/ua']]]], self::MOLLIE, self::VISA, self::MONEI, self::AIRWALLEX, self::VIVA_WALLET, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/es/empresa/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/es/legal/']]]]],
        'SJ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SE' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::VIVA_WALLET, self::KLARNA_CHECKOUT => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/international/enterprise/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/se/villkor/']]]], self::NEXI_CHECKOUT, self::PAYPAL_WALLET, self::AMAZON_PAY],
        'CH' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::MOLLIE, self::VISA, self::PAYPAL_WALLET, self::AMAZON_PAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/ch/fr/entreprise/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/ch/fr/conditions-generales-de-vente/']]]]],
        'TR' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'UA' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        // LATAM & Caribbeans.
        'AG' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'AI' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'AR' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'AW' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'BS' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'BB' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'BZ' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'BM' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'BO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::HELIOPAY],
        'BQ' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'BR' => [self::STRIPE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'VG' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'KY' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'CL' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'CO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'CR' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'CU' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CW' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'DM' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'DO' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'EC' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'SV' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'FK' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::HELIOPAY],
        'GF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'GD' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'GP' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'GT' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'GY' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'HT' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'HN' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'JM' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'MQ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'MX' => [self::STRIPE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/mx/negocios/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/mx/terminos-y-condiciones/']]]], self::HELIOPAY],
        'MS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NI' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'PA' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'PY' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::HELIOPAY],
        'PE' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'PR' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::HELIOPAY],
        'BL' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::HELIOPAY],
        'KN' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'LC' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'MF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'VC' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'SX' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'GS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SR' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'TT' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'TC' => [self::TILOPAY, self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET, self::HELIOPAY],
        'UY' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        'VI' => [self::TILOPAY, self::VISA, self::HELIOPAY],
        'VE' => [self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET, self::HELIOPAY],
        // Antarctica.
        'AQ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        // APAC.
        'AS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'AU' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::SQUARE => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/au/en/pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/au/en/legal/general/ua']]]], self::EWAY, self::VISA, self::AIRWALLEX, self::GOCARDLESS => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/en-au/pricing/']]]], self::ANTOM, self::PAYPAL_WALLET, self::AFTERPAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/au/business/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/au/legal/']]]]],
        'BD' => [self::PAYONEER => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::VISA],
        'IO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BN' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'KH' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CN' => [self::PAYPAL_FULL_STACK => [
            '_type' => self::TYPE_PSP,
            // Change the type to PSP.
            '_append' => ['tags' => [self::TAG_PREFERRED]],
        ], self::ANTOM, self::AIRWALLEX, self::PAYONEER, self::VISA],
        'CX' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CC' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CK' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'FJ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'GU' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'HM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'HK' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::ANTOM, self::AIRWALLEX, self::PAYONEER, self::VISA, self::PAYPAL_WALLET],
        'IN' => [self::STRIPE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::RAZORPAY, self::PAYU_INDIA, self::PAYONEER, self::VISA, self::PAYPAL_WALLET],
        'ID' => [self::PAYONEER => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET],
        'JP' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::SQUARE => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/jp/ja/pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/jp/ja/legal/general/ua']]]], self::VISA, self::PAYPAL_WALLET, self::AMAZON_PAY],
        'KI' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'LA' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MY' => [self::STRIPE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYONEER, self::VISA, self::PAYPAL_WALLET],
        'MV' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MH' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'FM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MN' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NR' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NP' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NC' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'NZ' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::EWAY => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://eway.io/nz/online-payments/#pricing'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://eway.io/docs/eWAY-Terms-and-Conditions-NZ.pdf']]]], self::VISA, self::AIRWALLEX, self::PAYPAL_WALLET, self::AFTERPAY, self::KLARNA => ['_merge_on_type' => ['links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/nz/business/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/nz/legal/']]]]],
        'NU' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MP' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'PW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'PG' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'PH' => [self::PAYONEER => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET],
        'PN' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'WS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SG' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::ANTOM, self::AIRWALLEX, self::VISA, self::PAYPAL_WALLET],
        'SB' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'LK' => [self::PAYONEER => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::VISA],
        'KR' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'TW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_WALLET => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TH' => [self::STRIPE => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYONEER, self::VISA, self::PAYPAL_WALLET],
        'TL' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TK' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TV' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'VU' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'VN' => [self::PAYONEER => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET],
        'WF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        // Africa.
        'DZ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'AO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BJ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'BF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BI' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CV' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TD' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'KM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CG' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'CI' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'EG' => [self::PAYMOB => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET],
        'CD' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'DJ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'GQ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'ER' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SZ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'ET' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'GA' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'GH' => [self::PAYSTACK => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::VISA],
        'GM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'GN' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'GW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'KE' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'LS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'LR' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'LY' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MG' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'ML' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MR' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'MU' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'MA' => [self::PAYONEER => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET],
        'MZ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'NA' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NE' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'NG' => [self::PAYSTACK => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::VISA],
        'RE' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'RW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SH' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'ST' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SN' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'SC' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'SL' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'SO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'ZA' => [self::PAYSTACK => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYFAST, self::VISA, self::PAYPAL_WALLET],
        'SS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TZ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TG' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TN' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'UG' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'EH' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'ZM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'ZW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        // Middle East.
        'AF' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'AZ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'BH' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'BT' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'GE' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK],
        'IR' => [],
        'IQ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'IL' => [self::AIRWALLEX => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::VISA],
        'JO' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::NGENIUS, self::PAYPAL_WALLET],
        'KZ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK],
        'KW' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'KG' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'LB' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'OM' => [self::PAYMOB => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::PAYPAL_WALLET],
        'PK' => [self::PAYONEER => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYMOB, self::VISA],
        'PS' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'QA' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::PAYPAL_WALLET],
        'SA' => [self::PAYMOB => ['_append' => ['tags' => [self::TAG_PREFERRED]]], self::PAYPAL_FULL_STACK, self::VISA, self::NGENIUS, self::PAYPAL_WALLET],
        'SD' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TJ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'TM' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'AE' => [self::WOOPAYMENTS, self::PAYPAL_FULL_STACK, self::STRIPE, self::PAYONEER, self::PAYMOB, self::VISA, self::NGENIUS, self::PAYPAL_WALLET],
        'UZ' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
        'YE' => [self::VISA => ['_append' => ['tags' => [self::TAG_PREFERRED]]]],
    ];
    /**
     * The context to incentive type map.
     *
     * @var array|string[]
     */
    private array $context_to_incentive_type_map = [Payments::SUGGESTIONS_CONTEXT => 'wc_settings_payments'];
    /**
     * The suggestion incentives provider.
     */
    private Payments_Extension_Suggestion_Incentives $suggestion_incentives;
    /**
     * Initialize the class instance.
     *
     * @param PaymentsExtensionSuggestionIncentives $suggestion_incentives The suggestion incentives provider.
     *
     * @internal
     */
    final public function init(Payments_Extension_Suggestion_Incentives $suggestion_incentives): void
    {
        $this->suggestion_incentives = $suggestion_incentives;
    }
    /**
     * Get the list of payment extensions details for a specific country.
     *
     * @param string $country_code The two-letter country code.
     * @param string $context      Optional. The context ID of where these extensions are being used.
     *
     * @return array The list of payment extensions (their full details) for the given country.
     *               Empty array if no extensions are available for the country or the country is not supported.
     * @throws \Exception If there were malformed or invalid extension details.
     */
    public function get_country_extensions(string $country_code, string $context = ''): array
    {
        $country_code = strtoupper($country_code);
        if (empty($this->country_extensions[$country_code]) || !is_array($this->country_extensions[$country_code])) {
            return [];
        }
        // Process the extensions.
        $processed_extensions = [];
        $priority = 0;
        foreach ($this->country_extensions[$country_code] as $key => $details) {
            // Check the formats we support.
            if (is_int($key) && is_string($details)) {
                $extension_id = $details;
                $extension_country_details = [];
            } elseif (is_string($key) && is_array($details)) {
                $extension_id = $key;
                $extension_country_details = $details;
            } else {
                // Just ignore the entry as it is malformed.
                continue;
            }
            // Determine if the extension should be included based on the store's state, the provided country and context.
            if (!$this->is_extension_allowed()) {
                continue;
            }
            // Determine the extension details for the given country.
            $extension_base_details = $this->get_extension_base_details($extension_id) ?? [];
            $extension_details = $this->with_country_details($extension_base_details, $extension_country_details);
            // Apply any changes to the extension details based on the store's state.
            $extension_details = $this->with_store_state_details($extension_id, $extension_details);
            // Check if there is an incentive for this extension and attach its details.
            $incentive = $this->get_extension_incentive($extension_id, $country_code, $context);
            if (is_array($incentive) && !empty($incentive)) {
                $extension_details['_incentive'] = $incentive;
            }
            // Include the extension ID.
            $extension_details['id'] = $extension_id;
            // Lock in the priority for ordering purposes.
            // We respect the order in the country extensions list.
            // We use increments of 10 to allow for easy insertions.
            $priority += 10;
            $extension_details['_priority'] = $priority;
            $processed_extensions[] = $this->standardize_extension_details($extension_details);
        }
        return $processed_extensions;
    }
    /**
     * Get the base details of a payment extension by its ID.
     *
     * @param string $extension_id The extension id.
     *
     * @return array|null The extension details for the given ID. Null if not found.
     */
    public function get_by_id(string $extension_id): ?array
    {
        $extension_id = sanitize_title($extension_id);
        $extensions = $this->get_all_extensions_base_details();
        if (isset($extensions[$extension_id])) {
            $extension_details = $extensions[$extension_id];
            $extension_details['id'] = $extension_id;
            $extension_details['_priority'] = 0;
            return $this->standardize_extension_details($extension_details);
        }
        return null;
    }
    /**
     * Get the base details of a payment extension by its plugin slug.
     *
     * If there are multiple extensions with the same plugin slug, the first one found will be returned.
     *
     * @param string $plugin_slug  The plugin slug.
     * @param string $country_code Optional. The two-letter country code for which the extension suggestion should be retrieved.
     * @param string $context      Optional. The context ID of where this extension suggestion is being used.
     *
     * @return array|null The extension details for the given plugin slug. Null if not found or the slug is empty.
     */
    public function get_by_plugin_slug(string $plugin_slug, string $country_code = '', string $context = ''): ?array
    {
        $plugin_slug = sanitize_title($plugin_slug);
        if (empty($plugin_slug)) {
            return null;
        }
        // If we have a country code, try to find a fully localized extension suggestion.
        if (!empty($country_code)) {
            $extensions = $this->get_country_extensions($country_code, $context);
            foreach ($extensions as $extension_details) {
                if (isset($extension_details['plugin']['slug']) && $plugin_slug === $extension_details['plugin']['slug']) {
                    // The extension details are already standardized.
                    return $extension_details;
                }
            }
        }
        // Fallback to the base details.
        $extensions = $this->get_all_extensions_base_details();
        foreach ($extensions as $extension_id => $extension_details) {
            if (isset($extension_details['plugin']['slug']) && $plugin_slug === $extension_details['plugin']['slug']) {
                $extension_details['id'] = $extension_id;
                $extension_details['_priority'] = 0;
                return $this->standardize_extension_details($extension_details);
            }
        }
        return null;
    }
    /**
     * Dismiss an incentive for a specific payment extension suggestion.
     *
     * @param string $incentive_id  The incentive ID.
     * @param string $suggestion_id The suggestion ID.
     * @param string $context       Optional. The context ID for which the incentive should be dismissed.
     *                              If not provided, the incentive will be dismissed for all contexts.
     *
     * @return bool True if the incentive was not previously dismissed and now it is.
     *              False if the incentive was already dismissed or could not be dismissed.
     * @throws \Exception If the incentive could not be dismissed due to an error.
     */
    public function dismiss_incentive(string $incentive_id, string $suggestion_id, string $context = 'all'): bool
    {
        return $this->suggestion_incentives->dismiss_incentive($incentive_id, $suggestion_id, $context);
    }
    /**
     * Determine if a payment extension is allowed to be suggested.
     *
     *
     * @return bool True if the extension is allowed, false otherwise.
     *              Defaults to true if there is no specific logic for the extension.
     */
    private function is_extension_allowed(): bool
    {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        // Add per-extension exclusion logic here.
        // Returning true for now to avoid excluding any extensions.
        return true;
    }
    /**
     * Merges country-specific details into the base details of a payment extension.
     *
     * This function processes special `_append`, `_remove`, and `_merge_on_type` instructions to modify
     * list-type entries within the base details.
     *
     * @param array $base_details    The base details of the payment extension.
     * @param array $country_details The country-specific details, which may include
     *                               special `_append` and `_remove` instructions.
     *
     * @return array The merged details, with country-specific modifications applied.
     *
     * @throws \Exception If the country extension details are malformed or invalid.
     */
    private function with_country_details(array $base_details, array $country_details): array
    {
        // Process any append instructions.
        if (isset($country_details['_append'])) {
            if (!is_array($country_details['_append'])) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \Exception('Malformed country extension details _append entry.');
            }
            foreach ($country_details['_append'] as $append_key => $append_list) {
                // Sanity checks.
                if (!is_string($append_key) || !is_array($append_list) || !Array_Util::array_is_list($append_list)) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new \Exception('Malformed country extension details _append details.');
                }
                // If the target entry doesn't exist, create it as an empty list.
                if (!isset($base_details[$append_key])) {
                    $base_details[$append_key] = [];
                }
                if (!is_array($base_details[$append_key]) || !Array_Util::array_is_list($base_details[$append_key])) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new \Exception('Invalid country extension details _append target.');
                }
                $base_details[$append_key] = array_merge($base_details[$append_key], $append_list);
            }
            // Remove the special entry because we don't need it anymore.
            unset($country_details['_append']);
        }
        // Process any remove instructions.
        if (isset($country_details['_remove'])) {
            if (!is_array($country_details['_remove'])) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \Exception('Malformed country extension details _remove entry.');
            }
            foreach ($country_details['_remove'] as $removal_key => $removal_list) {
                // Sanity checks.
                if (!is_string($removal_key) || !is_array($removal_list) || !Array_Util::array_is_list($removal_list)) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new \Exception('Malformed country extension details _remove details.');
                }
                if (!isset($base_details[$removal_key])) {
                    // If the target entry doesn't exist, we don't need to do anything.
                    continue;
                }
                if (!is_array($base_details[$removal_key]) || !Array_Util::array_is_list($base_details[$removal_key])) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new \Exception('Invalid country extension details _remove target.');
                }
                $base_details[$removal_key] = array_diff($base_details[$removal_key], $removal_list);
            }
            // Remove the special entry because we don't need it anymore.
            unset($country_details['_remove']);
        }
        // Process any merge on type instructions.
        if (isset($country_details['_merge_on_type'])) {
            if (!is_array($country_details['_merge_on_type'])) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \Exception('Malformed country extension details _merge_on_type entry.');
            }
            foreach ($country_details['_merge_on_type'] as $merge_key => $merge_list) {
                // Sanity checks.
                if (!is_string($merge_key) || !is_array($merge_list) || !Array_Util::array_is_list($merge_list) || count(array_column($merge_list, '_type')) !== count($merge_list)) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new \Exception('Malformed country extension details _merge_on_type details.');
                }
                if (!isset($base_details[$merge_key])) {
                    // If the target entry doesn't exist, create it.
                    $base_details[$merge_key] = [];
                }
                if (!is_array($base_details[$merge_key]) || !Array_Util::array_is_list($base_details[$merge_key]) || count(array_column($base_details[$merge_key], '_type')) !== count($base_details[$merge_key])) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new \Exception('Invalid country extension details _merge_on_type target.');
                }
                // Merge the lists based on the '_type' values.
                $base_details[$merge_key] = Array_Util::merge_by_key($base_details[$merge_key], $merge_list, '_type');
            }
            // Remove the special entry because we don't need it anymore.
            unset($country_details['_merge_on_type']);
        }
        // Merge any remaining country details so they overwrite the base details.
        return array_merge($base_details, $country_details);
    }
    /**
     * Apply customizations to the extension details based on the store's state.
     *
     * The customizations may be general or specific to certain extensions.
     * The store's state refers to various aspects of the store's configuration, collected data,
     * store setup/launch process, onboarding task completion, etc.
     *
     * @param string $extension_id      The extension ID.
     * @param array  $extension_details The extension details.
     *
     * @return array The modified extension details.
     */
    private function with_store_state_details(string $extension_id, array $extension_details): array
    {
        // For Square, we add the preferred tags if the merchant self-identified as selling offline via the core profiler.
        if (self::SQUARE === $extension_id && $this->is_merchant_selling_offline()) {
            if (empty($extension_details['tags'])) {
                $extension_details['tags'] = [];
            }
            $extension_details['tags'][] = self::TAG_PREFERRED;
            $extension_details['tags'][] = self::TAG_PREFERRED_OFFLINE;
        }
        return $extension_details;
    }
    /**
     * Get the incentive details for a given extension and country, if any.
     *
     * @param string $extension_id The extension ID.
     * @param string $country_code The two-letter country code.
     * @param string $context      Optional. The context ID of where the extension incentive is being used.
     *
     * @return array|null The incentive details for the given extension and country. Null if not found.
     */
    private function get_extension_incentive(string $extension_id, string $country_code, string $context = ''): ?array
    {
        // Try to map the context to an incentive type.
        $incentive_type = '';
        if (isset($this->context_to_incentive_type_map[$context])) {
            $incentive_type = $this->context_to_incentive_type_map[$context];
        }
        $incentives = $this->suggestion_incentives->get_incentives($extension_id, $country_code, $incentive_type);
        if (empty($incentives)) {
            return null;
        }
        // Use the first incentive, in case there are multiple.
        $incentive = reset($incentives);
        // Sanitize the incentive details.
        $incentive = $this->sanitize_extension_incentive($incentive);
        // Enhance the incentive details.
        $incentive['_suggestion_id'] = $extension_id;
        // Add the dismissals list.
        $incentive['_dismissals'] = $this->suggestion_incentives->get_incentive_dismissals($incentive['id'], $extension_id);
        return $incentive;
    }
    /**
     * Sanitize the incentive details for a payment extension.
     *
     * @param array $incentive The incentive details.
     *
     * @return array The sanitized incentive details.
     */
    private function sanitize_extension_incentive(array $incentive): array
    {
        // Apply a very loose sanitization. Stricter sanitization can be applied downstream, if needed.
        return array_map(function ($value) {
            // Make sure that if we have HTML tags, we only allow a limited set of tags (only stylistic ones).
            if (is_string($value) && preg_match('/<[^>]+>/', $value)) {
                return wp_kses($value, wp_kses_allowed_html('data'));
            }
            return $value;
        }, $incentive);
    }
    /**
     * Get the base details of all extensions.
     *
     * @return array[] The base details of all extensions.
     */
    private function get_all_extensions_base_details(): array
    {
        if (isset($this->extensions_base_details_memo)) {
            return $this->extensions_base_details_memo;
        }
        $this->extensions_base_details_memo = [self::AIRWALLEX => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Airwallex Payments', 'woocommerce'), 'description' => esc_html__('Boost international sales and save on FX fees. Accept 60+ local payment methods including Apple Pay and Google Pay.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/airwallex.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/airwallex.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'airwallex-online-payments-gateway'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.airwallex.com/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/airwallexpayments/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.airwallex.com/terms/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://www.airwallex.com/docs/payments__plugins__woocommerce__install-the-woocommerce-plugin'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://help.airwallex.com/']]], self::ANTOM => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Antom', 'woocommerce'), 'description' => esc_html__('Your trusted payments partner in Asia and around the world.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/antom.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'antom-payments'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/antom-payments/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://global.alipay.com/docs/ac/Platform/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/antom-payment/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=antom-payments']]], self::MERCADO_PAGO => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Mercado Pago', 'woocommerce'), 'description' => esc_html__('Set up your payment methods and accept credit and debit cards, cash, bank transfers and money from your Mercado Pago account. Offer safe and secure payments with Latin America’s leading processor.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/mercadopago.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/mercadopago.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-mercadopago'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/mercado-pago-checkout/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/mercado-pago/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=mercado-pago-checkout']]], self::MOLLIE => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Mollie', 'woocommerce'), 'description' => esc_html__('Effortless payments by Mollie: Offer global and local payment methods, get onboarded in minutes, and supported in your language.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/mollie.svg', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/mollie.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'mollie-payments-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.mollie.com/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/mollie-payments-for-woocommerce/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.mollie.com/user-agreement'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/mollie-payments-for-woocommerce/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://discord.com/invite/mollie']]], self::PAYFAST => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Payfast', 'woocommerce'), 'description' => esc_html__('The Payfast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa\'s most popular payment gateways. No setup fees or monthly subscription costs. Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/payfast.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/payfast.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-payfast-gateway'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://payfast.io/fees/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/payfast-payment-gateway/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://payfast.io/legal/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/payfast-payment-gateway/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=payfast-payment-gateway']], 'tags' => [self::TAG_MADE_IN_WOO]], self::PAYMOB => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Paymob', 'woocommerce'), 'description' => esc_html__('Paymob is a leading payment gateway in the Middle East and Africa. Accept payments online and in-store with Paymob.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/paymob.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'paymob-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://paymob.com/en/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/paymob/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://paymob.com/en/policy'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/paymob-for-woocommerce/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=paymob']]], self::PAYPAL_FULL_STACK => ['_type' => self::TYPE_APM, 'title' => esc_html__('PayPal Payments', 'woocommerce'), 'description' => esc_html__('PayPal Payments lets you offer PayPal, Venmo (US only), Pay Later options and more.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/paypal.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/paypal.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-paypal-payments'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.paypal.com/webapps/mpp/merchant-fees'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/woocommerce-paypal-payments/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.paypal.com/legalhub/home'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/woocommerce-paypal-payments/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-paypal-payments']], 'tags' => [self::TAG_MADE_IN_WOO, self::TAG_PREFERRED]], self::PAYPAL_WALLET => ['_type' => self::TYPE_EXPRESS_CHECKOUT, 'title' => esc_html__('PayPal Payments', 'woocommerce'), 'description' => esc_html__('Safe and secure payments using your customer\'s PayPal account.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/paypal.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/paypal.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-paypal-payments'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.paypal.com/webapps/mpp/merchant-fees#advanced_cd_payments'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/woocommerce-paypal-payments/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.paypal.com/legalhub/home'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/woocommerce-paypal-payments/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-paypal-payments']], 'tags' => [self::TAG_MADE_IN_WOO]], self::PAYONEER => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Payoneer Checkout', 'woocommerce'), 'description' => esc_html__('Payoneer Checkout is the next generation of payment processing platforms, giving merchants around the world the solutions and direction they need to succeed in today\'s hyper-competitive global market.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/payoneer.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/payoneer.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'payoneer-checkout'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.payoneer.com/about/pricing/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/payoneer-checkout/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.payoneer.com/legal-agreements/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://checkoutdocs.payoneer.com/docs/about-woocommerce-integration'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://checkoutdocs.payoneer.com/docs/troubleshoot-woocommerce']]], self::PAYSTACK => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Paystack', 'woocommerce'), 'description' => esc_html__('Paystack helps African merchants accept one-time and recurring payments online with a modern, safe, and secure payment gateway.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/paystack.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/paystack.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woo-paystack'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://paystack.com/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/paystack/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://paystack.com/terms'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/paystack/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://support.paystack.com/en/articles/2130754']]], self::PAYTRAIL => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Paytrail', 'woocommerce'), 'description' => esc_html__('Accept all popular payment methods for Finnish B2C and B2B customers', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/paytrail.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'paytrail-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.paytrail.com/en/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/paytrail/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.paytrail.com/en/terms-conditions'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/paytrail-for-woocommerce/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://www.paytrail.com/en/customer-service#merchants']]], self::PAYU_INDIA => ['_type' => self::TYPE_PSP, 'title' => esc_html__('PayU India', 'woocommerce'), 'description' => esc_html__('Enable PayU\'s exclusive plugin for WooCommerce to start accepting payments in 100+ payment methods available in India including credit cards, debit cards, UPI, & more!', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/payu.svg', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/payu.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'payu-india'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://payu.in/pricing/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/payu-india/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://payu.in/payu-terms-and-conditions/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://payu.in/plugins/payment-gateway-for-woocommerce-plugin'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://help.payu.in/']]], self::RAZORPAY => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Razorpay', 'woocommerce'), 'description' => esc_html__('The official Razorpay extension for WooCommerce allows you to accept credit cards, debit cards, netbanking, wallet, and UPI payments.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/razorpay.svg', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/razorpay.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woo-razorpay'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://razorpay.com/pricing/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/razorpay-for-woocommerce/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://razorpay.com/terms/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://razorpay.com/docs/payment-gateway/ecommerce-plugins/woocommerce/woocommerce-pg/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://razorpay.com/support/']]], self::SQUARE => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Square', 'woocommerce'), 'description' => esc_html__('Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). Sell in store and track sales and inventory in one place.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/square-black.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/square.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-square'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://squareup.com/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/square/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://squareup.com/legal/general/ua'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/woocommerce-square/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=square']], 'tags' => [self::TAG_MADE_IN_WOO]], self::STRIPE => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Stripe', 'woocommerce'), 'description' => esc_html__('Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/stripe.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/stripe.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-stripe'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://stripe.com/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/stripe/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://stripe.com/legal/connect-account'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/stripe'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=stripe']], 'tags' => [self::TAG_MADE_IN_WOO]], self::TILOPAY => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Tilopay', 'woocommerce'), 'description' => esc_html__('Accept credit and debit cards on your WooCommerce store with advanced features like partial refunds, full/partial captures, and 3D Secure security.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/tilopay.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'tilopay'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://tilopay.com/tarifas'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://tilopay.com/tilopay-checkout'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://tilopay.com/terminos-condiciones'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://tilopay.com/documentacion/plataforma-woocommerce'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://cst.support.tilopay.com/servicedesk/customer/portals']], 'tags' => [self::TAG_PREFERRED]], self::VIVA_WALLET => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Viva.com Smart Checkout', 'woocommerce'), 'description' => esc_html__('A European payments solution that allows you to accept payments in over 25 countries and multiple currencies.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/vivacom.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'viva-com-smart-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.viva.com/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/viva-com-smart-for-woocommerce/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.viva.com/terms-portal'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/viva-com-smart-for-woocommerce/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=viva-com-smart-for-woocommerce']]], self::WOOPAYMENTS => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Accept payments with Woo', 'woocommerce'), 'description' => esc_html__('Credit/debit cards, Apple Pay, Google Pay, and more.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/woopayments.svg', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/woo.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-payments'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://woocommerce.com/document/woopayments/fees-and-debits/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/payments/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://woocommerce.com/document/woopayments/our-policies/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/woopayments/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=woopayments']], 'tags' => [self::TAG_MADE_IN_WOO, self::TAG_PREFERRED]], self::AMAZON_PAY => ['_type' => self::TYPE_EXPRESS_CHECKOUT, 'title' => esc_html__('Amazon Pay', 'woocommerce'), 'description' => esc_html__('Enable a familiar, fast checkout for hundreds of millions of active Amazon customers globally.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/amazonpay.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/amazonpay.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-amazon-payments-advanced'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://pay.amazon.com/help/201212280'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/pay-with-amazon/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://pay.amazon.com/help/201212430'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/amazon-payments-advanced/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=pay-with-amazon']], 'tags' => [self::TAG_MADE_IN_WOO]], self::AFFIRM => ['_type' => self::TYPE_BNPL, 'title' => esc_html__('Affirm', 'woocommerce'), 'description' => esc_html__('Affirm\'s tailored Buy Now Pay Later programs remove price as a barrier, turning browsers into buyers, increasing average order value, and expanding your customer base.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/affirm.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/affirm.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-affirm'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.affirm.com/business'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/woocommerce-gateway-affirm/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.affirm.com/terms'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/woocommerce-gateway-affirm/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-gateway-affirm']], 'tags' => [self::TAG_MADE_IN_WOO]], self::AFTERPAY => ['_type' => self::TYPE_BNPL, 'title' => esc_html__('Afterpay', 'woocommerce'), 'description' => esc_html__('Afterpay allows customers to receive products immediately and pay for purchases over four installments, always interest-free.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/afterpay.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/afterpay-clearpay.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'afterpay-gateway-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.afterpay.com/for-retailers'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/afterpay/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.afterpay.com/terms-of-service'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/afterpay/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=afterpay']]], self::CLEARPAY => ['_type' => self::TYPE_BNPL, 'title' => esc_html__('Clearpay', 'woocommerce'), 'description' => esc_html__('Clearpay allows customers to receive products immediately and pay for purchases over four installments, always interest-free.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/afterpay-clearpay.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'clearpay-gateway-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.clearpay.co.uk/en-GB/for-retailers'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/clearpay/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.clearpay.co.uk/terms-of-service'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/clearpay/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=clearpay']]], self::KLARNA => ['_type' => self::TYPE_BNPL, 'title' => esc_html__('Klarna Payments', 'woocommerce'), 'description' => esc_html__('Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce'), 'image' => plugins_url('assets/images/onboarding/klarna-black.png', WC_PLUGIN_FILE), 'icon' => plugins_url('assets/images/onboarding/icons/klarna.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'klarna-payments-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/us/business/payment-methods/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/klarna-payments/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/us/legal/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/klarna-payments/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=klarna-payments']]], self::KLARNA_CHECKOUT => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Klarna Checkout', 'woocommerce'), 'description' => esc_html__('A full checkout experience embedded on your site that includes all popular payment methods (Pay Now, Pay Later, Financing, Installments).', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/klarna-checkout.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'klarna-checkout-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.klarna.com/us/business/payment-methods/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/klarna-checkout/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.klarna.com/us/legal/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/klarna-checkout/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=klarna-checkout']]], self::HELIOPAY => ['_type' => self::TYPE_CRYPTO, 'title' => esc_html__('Helio Pay', 'woocommerce'), 'description' => esc_html__('Effortlessly accept cryptocurrency payments in your store.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/heliopay.png', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'helio'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.hel.io/pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/helio-pay/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://info.docs.hel.io/terms-of-service'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/helio-pay/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=helio-pay']]], self::MONEI => ['_type' => self::TYPE_PSP, 'title' => esc_html__('MONEI', 'woocommerce'), 'description' => esc_html__('Accept Cards, Apple Pay, Google Pay, Bizum, PayPal, and many more payment methods in your store.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/monei.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'monei'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://monei.com/pricing/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://monei.com/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://monei.com/legal-notice/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://support.monei.com/hc/en-us/articles/360017801677-Get-started-with-MONEI'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://support.monei.com/hc/en-us/requests/new']]], self::EWAY => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Eway', 'woocommerce'), 'description' => esc_html__('Take credit card payments securely via Eway keeping customers on your site.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/eway.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-eway'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://www.eway.com.au/online-payments/#pricing'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/eway/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://www.eway.com.au/docs/eWAY-Terms-and-Conditions-AU.pdf'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/eway/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=eway']]], self::VISA => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Visa Acceptance Solutions', 'woocommerce'), 'description' => esc_html__('Accept payments on your WooCommerce store securely.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/visa-acceptance-solutions.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'visa-acceptance-solutions'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/visa-acceptance-solutions/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/visa-acceptance-solutions/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=visa-acceptance-solutions']]], self::NGENIUS => ['_type' => self::TYPE_PSP, 'title' => esc_html__('N-Genius Online by Network', 'woocommerce'), 'description' => esc_html__('Power your business with N-Genius Online—smart, secure, and built for the future', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/ngenius.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'ngenius'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/ngenius/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/ngenius/']]], self::GOCARDLESS => ['_type' => self::TYPE_PSP, 'title' => esc_html__('GoCardless', 'woocommerce'), 'description' => esc_html__('Accept Direct Debit, ACH Pull, and open banking payments.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/gocardless.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-gocardless'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_PRICING, 'url' => 'https://gocardless.com/pricing/'], ['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/gocardless/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://gocardless.com/legal/'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/gocardless/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://woocommerce.com/my-account/contact-support/?select=gocardless']]], self::NEXI_CHECKOUT => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Nexi Checkout', 'woocommerce'), 'description' => esc_html__('A fully embedded checkout, with all popular payment methods, for more sales and less abandoned shopping carts.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/nexi.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'dibs-easy-for-woocommerce'], 'links' => [['_type' => Payments_Providers::LINK_TYPE_ABOUT, 'url' => 'https://woocommerce.com/products/nexi-checkout/'], ['_type' => Payments_Providers::LINK_TYPE_TERMS, 'url' => 'https://support.nets.eu/document/nets-easy-general-terms-and-conditions-2022'], ['_type' => Payments_Providers::LINK_TYPE_DOCS, 'url' => 'https://woocommerce.com/document/nexi-checkout/'], ['_type' => Payments_Providers::LINK_TYPE_SUPPORT, 'url' => 'https://developer.nexigroup.com/nexi-checkout/en-EU/support/']]], self::COINBASE => ['_type' => self::TYPE_CRYPTO, 'icon' => plugins_url('assets/images/onboarding/icons/coinbase.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'coinbase-commerce']], self::AUTHORIZE_NET => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/authorize.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-authorize-net-cim']], self::BILLIE => ['_type' => self::TYPE_PSP, 'title' => esc_html__('Billie', 'woocommerce'), 'description' => esc_html__('Billie is the leading provider of Buy Now, Pay Later payment methods for B2B stores.', 'woocommerce'), 'icon' => plugins_url('assets/images/onboarding/icons/billie.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'billie-for-woocommerce']], self::BOLT => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/bolt.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'bolt-checkout-woocommerce']], self::DEPAY => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/depay.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'depay-payments-for-woocommerce']], self::ELAVON => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/elavon.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-converge']], self::FORTISPAY => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/fortispay.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'fortis-for-woocommerce']], self::PAYPAL_ZETTLE => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/paypal-zettle.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'zettle-pos-integration']], self::RAPYD => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/rapyd.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'rapyd-payments-plugin-for-woocommerce']], self::PAYPAL_BRAINTREE => ['_type' => self::TYPE_PSP, 'icon' => plugins_url('assets/images/onboarding/icons/paypal-braintree.svg', WC_PLUGIN_FILE), 'plugin' => ['_type' => self::PLUGIN_TYPE_WPORG, 'slug' => 'woocommerce-gateway-paypal-powered-by-braintree']]];
        return $this->extensions_base_details_memo;
    }
    /**
     * Get the base details for a specific extension.
     *
     * @see self::standardize_extension_details() for the supported entries.
     *
     * @param string $extension_id The extension ID.
     *
     * @return ?array The extension base details.
     *                Null if the extension is not one we have details for.
     */
    private function get_extension_base_details(string $extension_id): ?array
    {
        $extensions = $this->get_all_extensions_base_details();
        if (!isset($extensions[$extension_id])) {
            return null;
        }
        return $extensions[$extension_id];
    }
    /**
     * Standardize the details for an extension.
     *
     * Ensures that the details array has all the required fields, and fills in any missing optional fields with defaults.
     * We also enforce a consistent order for the fields.
     *
     * @param array $extension_details The extension details.
     *
     * @return array The standardized extension details.
     */
    private function standardize_extension_details(array $extension_details): array
    {
        $standardized = [];
        // Required fields.
        $standardized['id'] = $extension_details['id'];
        $standardized['_priority'] = $extension_details['_priority'];
        $standardized['_type'] = $extension_details['_type'];
        $standardized['plugin'] = $extension_details['plugin'];
        // Optional fields.
        $standardized['title'] = $extension_details['title'] ?? '';
        $standardized['description'] = $extension_details['description'] ?? '';
        $standardized['image'] = $extension_details['image'] ?? '';
        $standardized['icon'] = $extension_details['icon'] ?? '';
        $standardized['links'] = $extension_details['links'] ?? [];
        $standardized['tags'] = $extension_details['tags'] ?? [];
        $standardized['_incentive'] = $extension_details['_incentive'] ?? null;
        return $standardized;
    }
    /**
     * Based on the WC onboarding profile, determine if the merchant is selling offline.
     *
     * If the user skipped the profiler (no data points provided), we assume they are NOT selling offline.
     *
     * @return bool True if the merchant is selling offline, false otherwise.
     */
    private function is_merchant_selling_offline(): bool
    {
        /*
         * We consider a merchant to be selling offline if:
         * - The profiler was NOT skipped (data points provided).
         *   AND
         * - The merchant answered 'Which one of these best describes you?' with 'I’m already selling' AND:
         *   - Answered the 'Are you selling online?' question with either:
         *     - 'No, I’m selling offline'.
         *        OR
         *     - 'I’m selling both online and offline'.
         *
         * @see plugins/woocommerce/client/admin/client/core-profiler/pages/UserProfile.tsx for the values.
         */
        $onboarding_profile = get_option(Onboarding_Profile::DATA_OPTION, []);
        if (isset($onboarding_profile['business_choice']) && ('im_already_selling' === $onboarding_profile['business_choice'] && (isset($onboarding_profile['selling_online_answer']) && ('no_im_selling_offline' === $onboarding_profile['selling_online_answer'] || 'im_selling_both_online_and_offline' === $onboarding_profile['selling_online_answer'])))) {
            return true;
        }
        return false;
    }
}