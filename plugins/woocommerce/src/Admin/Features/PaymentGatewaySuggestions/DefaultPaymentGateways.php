<?php

declare(strict_types=1);
/**
 * Gets a list of fallback methods if remote fetching is disabled.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

defined('ABSPATH') || exit;

use WC_Gateway_BACS;
use WC_Gateway_COD;

/**
 * Default Payment Gateways
 */
class DefaultPaymentGateways
{
    /**
     * This is the default priority for countries that are not in the $recommendation_priority_map.
     * Priority is used to determine which payment gateway to recommend first.
     * The lower the number, the higher the priority.
     */
    private static array $recommendation_priority = [
        'woocommerce_payments'                            => 1,
        'woocommerce_payments:with-in-person-payments'    => 1,
        'woocommerce_payments:without-in-person-payments' => 1,
        'stripe'                                          => 2,
        'woo-mercado-pago-custom'                         => 3,
        // PayPal Payments.
        'ppcp-gateway'                                    => 4,
        'mollie_wc_gateway_banktransfer'                  => 5,
        'razorpay'                                        => 5,
        'payfast'                                         => 5,
        'payubiz'                                         => 6,
        'square_credit_card'                              => 6,
        'klarna_payments'                                 => 6,
        // Klarna Checkout.
        'kco'                                             => 6,
        'paystack'                                        => 6,
        'eway'                                            => 7,
        'amazon_payments_advanced'                        => 7,
        'affirm'                                          => 8,
        'afterpay'                                        => 9,
        'zipmoney'                                        => 10,
        'payoneer-checkout'                               => 11,
    ];

    /**
     * Get default specs.
     *
     * @return array Default specs.
     */
    public static function get_all(): array
    {
        $payment_gateways = [
            [
                'id'                  => 'affirm',
                'title'               => __('Affirm', 'woocommerce'),
                'content'             => __('Affirm’s tailored Buy Now Pay Later programs remove price as a barrier, turning browsers into buyers, increasing average order value, and expanding your customer base.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/affirm.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/affirm.png',
                'plugins'             => [],
                'external_link'       => 'https://woocommerce.com/products/woocommerce-gateway-affirm',
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'US',
                            'CA',
                        ]
                    ),
                    (object) [
                        'type'     => 'or',
                        'operands' => [
                            self::get_rules_for_wcpay_activated(false),
                            self::get_rules_for_wcpay_connected(false),
                        ],
                    ],
                ],
                'category_other'      => [],
                'category_additional' => [
                    'US',
                    'CA',
                ],
            ],
            [
                'id'                  => 'afterpay',
                'title'               => __('Afterpay', 'woocommerce'),
                'content'             => __('Afterpay allows customers to receive products immediately and pay for purchases over four installments, always interest-free.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/afterpay.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/afterpay.png',
                'plugins'             => [ 'afterpay-gateway-for-woocommerce' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'US',
                            'CA',
                            'AU',
                        ]
                    ),
                    (object) [
                        'type'     => 'or',
                        'operands' => [
                            self::get_rules_for_wcpay_activated(false),
                            self::get_rules_for_wcpay_connected(false),
                        ],
                    ],
                ],
                'category_other'      => [],
                'category_additional' => [
                    'US',
                    'CA',
                    'AU',
                ],
            ],
            [
                'id'                  => 'airwallex_main',
                'title'               => __('Airwallex Payments', 'woocommerce'),
                'content'             => __('Boost international sales and save on FX fees. Accept 60+ local payment methods including Apple Pay and Google Pay.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/airwallex.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/airwallex.png',
                'plugins'             => [ 'airwallex-online-payments-gateway' ],
                'is_visible'          => [
                    self::get_rules_for_countries([ 'GB', 'AT', 'BE', 'EE', 'FR', 'DE', 'GR', 'IE', 'IT', 'NL', 'PL', 'PT', 'AU', 'NZ', 'HK', 'SG', 'CN' ]),
                ],
                'category_other'      => [ 'GB', 'AT', 'BE', 'EE', 'FR', 'DE', 'GR', 'IE', 'IT', 'NL', 'PL', 'PT', 'AU', 'NZ', 'HK', 'SG', 'CN' ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'amazon_payments_advanced',
                'title'               => __('Amazon Pay', 'woocommerce'),
                'content'             => __('Enable a familiar, fast checkout for hundreds of millions of active Amazon customers globally.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/amazonpay.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/amazonpay.png',
                'plugins'             => [ 'woocommerce-gateway-amazon-payments-advanced' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'US',
                            'AT',
                            'BE',
                            'CY',
                            'DK',
                            'ES',
                            'FR',
                            'DE',
                            'GB',
                            'HU',
                            'IE',
                            'IT',
                            'LU',
                            'NL',
                            'PT',
                            'SL',
                            'SE',
                            'JP',
                        ]
                    ),
                ],
                'category_other'      => [],
                'category_additional' => [
                    'US',
                    'AT',
                    'BE',
                    'CY',
                    'DK',
                    'ES',
                    'FR',
                    'DE',
                    'GB',
                    'HU',
                    'IE',
                    'IT',
                    'LU',
                    'NL',
                    'PT',
                    'SL',
                    'SE',
                    'JP',
                ],
            ],
            [
                'id'          => WC_Gateway_BACS::ID,
                'title'       => __('Direct bank transfer', 'woocommerce'),
                'content'     => __('Take payments via bank transfer.', 'woocommerce'),
                'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/bacs.svg',
                'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/bacs.png',
                'is_visible'  => [
                    self::get_rules_for_cbd(false),
                ],
                'is_offline'  => true,
            ],
            [
                'id'          => WC_Gateway_COD::ID,
                'title'       => __('Cash on delivery', 'woocommerce'),
                'content'     => __('Take payments in cash upon delivery.', 'woocommerce'),
                'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/cod.svg',
                'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/cod.png',
                'is_visible'  => [
                    self::get_rules_for_cbd(false),
                ],
                'is_offline'  => true,
            ],
            [
                'id'                  => 'eway',
                'title'               => __('Eway', 'woocommerce'),
                'content'             => __('The Eway extension for WooCommerce allows you to take credit card payments directly on your store without redirecting your customers to a third party site to make payment.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/eway.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/eway.png',
                'plugins'             => [ 'woocommerce-gateway-eway' ],
                'is_visible'          => false,
                'category_other'      => [],
                'category_additional' => [],
            ],
            [
                'id'                  => 'kco',
                'title'               => __('Klarna Checkout', 'woocommerce'),
                'content'             => __('Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/klarna-black.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/klarna.png',
                'plugins'             => [ 'klarna-checkout-for-woocommerce' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'NO',
                            'SE',
                            'FI',
                        ]
                    ),
                    self::get_rules_for_cbd(false),
                ],
                'category_other'      => [
                    'NO',
                    'SE',
                    'FI',
                ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'klarna_payments',
                'title'               => __('Klarna Payments', 'woocommerce'),
                'content'             => __('Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/klarna-black.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/klarna.png',
                'plugins'             => [ 'klarna-payments-for-woocommerce' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'MX',
                            'US',
                            'CA',
                            'AT',
                            'BE',
                            'CH',
                            'DK',
                            'ES',
                            'FI',
                            'FR',
                            'DE',
                            'GB',
                            'IT',
                            'NL',
                            'NO',
                            'PL',
                            'SE',
                            'NZ',
                            'AU',
                        ]
                    ),
                    self::get_rules_for_cbd(false),
                    (object) [
                        'type'     => 'or',
                        'operands' => [
                            (object) [
                                'type'    => 'not',
                                'operand' => [
                                    self::get_rules_for_countries(self::get_wcpay_countries()),
                                ],
                            ],
                            self::get_rules_for_wcpay_activated(false),
                            self::get_rules_for_wcpay_connected(false),
                        ],
                    ],
                ],
                'category_other'      => [],
                'category_additional' => [
                    'MX',
                    'US',
                    'CA',
                    'AT',
                    'BE',
                    'CH',
                    'DK',
                    'ES',
                    'FI',
                    'FR',
                    'DE',
                    'GB',
                    'IT',
                    'NL',
                    'NO',
                    'PL',
                    'SE',
                    'NZ',
                    'AU',
                ],
            ],
            [
                'id'                  => 'mollie_wc_gateway_banktransfer',
                'title'               => __('Mollie', 'woocommerce'),
                'content'             => __('Effortless payments by Mollie: Offer global and local payment methods, get onboarded in minutes, and supported in your language.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/mollie.svg',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/mollie.png',
                'plugins'             => [ 'mollie-payments-for-woocommerce' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'AT',
                            'BE',
                            'CH',
                            'ES',
                            'FI',
                            'FR',
                            'DE',
                            'GB',
                            'IT',
                            'NL',
                            'PL',
                        ]
                    ),
                ],
                'category_other'      => [
                    'AT',
                    'BE',
                    'CH',
                    'ES',
                    'FI',
                    'FR',
                    'DE',
                    'GB',
                    'IT',
                    'NL',
                    'PL',
                ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'payfast',
                'title'               => __('Payfast', 'woocommerce'),
                'content'             => __('The Payfast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa’s most popular payment gateways. No setup fees or monthly subscription costs. Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/payfast.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/payfast.png',
                'plugins'             => [ 'woocommerce-payfast-gateway' ],
                'is_visible'          => [
                    self::get_rules_for_countries([ 'ZA' ]),
                    self::get_rules_for_cbd(false),
                ],
                'category_other'      => [ 'ZA' ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'payoneer-checkout',
                'title'               => __('Payoneer Checkout', 'woocommerce'),
                'content'             => __('Payoneer Checkout is the next generation of payment processing platforms, giving merchants around the world the solutions and direction they need to succeed in today’s hyper-competitive global market.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/payoneer.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/payoneer.png',
                'plugins'             => [ 'payoneer-checkout' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'HK',
                            'CN',
                        ]
                    ),
                ],
                'category_other'      => [],
                'category_additional' => [
                    'HK',
                    'CN',
                ],
            ],
            [
                'id'                  => 'paystack',
                'title'               => __('Paystack', 'woocommerce'),
                'content'             => __('Paystack helps African merchants accept one-time and recurring payments online with a modern, safe, and secure payment gateway.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/paystack.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/paystack.png',
                'plugins'             => [ 'woo-paystack' ],
                'is_visible'          => [
                    self::get_rules_for_countries([ 'ZA', 'GH', 'NG' ]),
                    self::get_rules_for_cbd(false),
                ],
                'category_other'      => [ 'ZA', 'GH', 'NG' ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'payubiz',
                'title'               => __('PayU for WooCommerce', 'woocommerce'),
                'content'             => __('Enable PayU’s exclusive plugin for WooCommerce to start accepting payments in 100+ payment methods available in India including credit cards, debit cards, UPI, & more!', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/payu.svg',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/payu.png',
                'plugins'             => [ 'payu-india' ],
                'is_visible'          => [
                    (object) [
                        'type'      => 'base_location_country',
                        'value'     => 'IN',
                        'operation' => '=',
                    ],
                    self::get_rules_for_cbd(false),
                ],
                'category_other'      => [ 'IN' ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'ppcp-gateway',
                'title'               => __('PayPal Payments', 'woocommerce'),
                'content'             => __("Safe and secure payments using credit cards or your customer's PayPal account.", 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/paypal.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/paypal.png',
                'plugins'             => [ 'woocommerce-paypal-payments' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'US',
                            'CA',
                            'MX',
                            'BR',
                            'AR',
                            'CL',
                            'CO',
                            'EC',
                            'PE',
                            'UY',
                            'VE',
                            'AT',
                            'BE',
                            'BG',
                            'HR',
                            'CH',
                            'CY',
                            'CZ',
                            'DK',
                            'EE',
                            'ES',
                            'FI',
                            'FR',
                            'DE',
                            'GB',
                            'GR',
                            'HU',
                            'IE',
                            'IT',
                            'LV',
                            'LT',
                            'LU',
                            'MT',
                            'NL',
                            'NO',
                            'PL',
                            'PT',
                            'RO',
                            'SK',
                            'SL',
                            'SE',
                            'AU',
                            'NZ',
                            'HK',
                            'JP',
                            'SG',
                            'CN',
                            'ID',
                            'IN',
                        ]
                    ),
                    self::get_rules_for_cbd(false),
                ],
                'category_other'      => [
                    'US',
                    'CA',
                    'MX',
                    'BR',
                    'AR',
                    'CL',
                    'CO',
                    'EC',
                    'PE',
                    'UY',
                    'VE',
                    'AT',
                    'BE',
                    'BG',
                    'HR',
                    'CH',
                    'CY',
                    'CZ',
                    'DK',
                    'EE',
                    'ES',
                    'FI',
                    'FR',
                    'DE',
                    'GB',
                    'GR',
                    'HU',
                    'IE',
                    'IT',
                    'LV',
                    'LT',
                    'LU',
                    'MT',
                    'NL',
                    'NO',
                    'PL',
                    'PT',
                    'RO',
                    'SK',
                    'SL',
                    'SE',
                    'AU',
                    'NZ',
                    'HK',
                    'JP',
                    'SG',
                    'CN',
                    'ID',
                ],
                'category_additional' => [
                    'US',
                    'CA',
                    'ZA',
                    'NG',
                    'GH',
                    'EC',
                    'VE',
                    'AR',
                    'CL',
                    'CO',
                    'PE',
                    'UY',
                    'MX',
                    'BR',
                    'AT',
                    'BE',
                    'BG',
                    'HR',
                    'CH',
                    'CY',
                    'CZ',
                    'DK',
                    'EE',
                    'ES',
                    'FI',
                    'FR',
                    'DE',
                    'GB',
                    'GR',
                    'HU',
                    'IE',
                    'IT',
                    'LV',
                    'LT',
                    'LU',
                    'MT',
                    'NL',
                    'NO',
                    'PL',
                    'PT',
                    'RO',
                    'SK',
                    'SL',
                    'SE',
                    'AU',
                    'NZ',
                    'HK',
                    'JP',
                    'SG',
                    'CN',
                    'ID',
                    'IN',
                ],
            ],
            [
                'id'                  => 'razorpay',
                'title'               => __('Razorpay', 'woocommerce'),
                'content'             => __('The official Razorpay extension for WooCommerce allows you to accept credit cards, debit cards, netbanking, wallet, and UPI payments.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/razorpay.svg',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/razorpay.png',
                'plugins'             => [ 'woo-razorpay' ],
                'is_visible'          => [
                    (object) [
                        'type'      => 'base_location_country',
                        'value'     => 'IN',
                        'operation' => '=',
                    ],
                    self::get_rules_for_cbd(false),
                ],
                'category_other'      => [ 'IN' ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'square_credit_card',
                'title'               => __('Square', 'woocommerce'),
                'content'             => __('Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). Sell online and in store and track sales and inventory in one place.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/square-black.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/square.png',
                'plugins'             => [ 'woocommerce-square' ],
                'is_visible'          => [
                    (object) [
                        'type'     => 'or',
                        'operands' => (object) [
                            [
                                self::get_rules_for_countries([ 'US' ]),
                                self::get_rules_for_cbd(true),
                            ],
                            [
                                self::get_rules_for_countries(
                                    [
                                        'US',
                                        'CA',
                                        'IE',
                                        'ES',
                                        'FR',
                                        'GB',
                                        'AU',
                                        'JP',
                                    ]
                                ),
                                (object) [
                                    'type'     => 'or',
                                    'operands' => (object) [
                                        self::get_rules_for_selling_venues([ 'brick-mortar', 'brick-mortar-other' ]),
                                        self::get_rules_selling_offline(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'category_other'      => [
                    'US',
                    'CA',
                    'IE',
                    'ES',
                    'FR',
                    'GB',
                    'AU',
                    'JP',
                ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'stripe',
                'title'               => __(' Stripe', 'woocommerce'),
                'content'             => __('Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/stripe.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/stripe.png',
                'plugins'             => [ 'woocommerce-gateway-stripe' ],
                'is_visible'          => [
                    // https://stripe.com/global.
                    self::get_rules_for_countries(
                        [
                            'US',
                            'CA',
                            'MX',
                            'BR',
                            'AT',
                            'BE',
                            'BG',
                            'CH',
                            'CY',
                            'CZ',
                            'DK',
                            'EE',
                            'ES',
                            'FI',
                            'FR',
                            'DE',
                            'GB',
                            'GR',
                            'HU',
                            'IE',
                            'IT',
                            'LV',
                            'LT',
                            'LU',
                            'MT',
                            'NL',
                            'NO',
                            'PL',
                            'PT',
                            'RO',
                            'SK',
                            'SL',
                            'SE',
                            'AU',
                            'NZ',
                            'HK',
                            'JP',
                            'SG',
                            'ID',
                            'IN',
                        ]
                    ),
                    self::get_rules_for_cbd(false),
                ],
                'category_other'      => [
                    'US',
                    'CA',
                    'MX',
                    'BR',
                    'AT',
                    'BE',
                    'BG',
                    'CH',
                    'CY',
                    'CZ',
                    'DK',
                    'EE',
                    'ES',
                    'FI',
                    'FR',
                    'DE',
                    'GB',
                    'GR',
                    'HU',
                    'IE',
                    'IT',
                    'LV',
                    'LT',
                    'LU',
                    'MT',
                    'NL',
                    'NO',
                    'PL',
                    'PT',
                    'RO',
                    'SK',
                    'SL',
                    'SE',
                    'AU',
                    'NZ',
                    'HK',
                    'JP',
                    'SG',
                    'ID',
                    'IN',
                ],
                'category_additional' => [],
            ],
            [
                'id'                  => 'woo-mercado-pago-custom',
                'title'               => __('Mercado Pago', 'woocommerce'),
                'content'             => __('Set up your payment methods and accept credit and debit cards, cash, bank transfers and money from your Mercado Pago account. Offer safe and secure payments with Latin America’s leading processor.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/mercadopago.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/mercadopago.png',
                'plugins'             => [ 'woocommerce-mercadopago' ],
                'is_visible'          => [
                    self::get_rules_for_countries(
                        [
                            'AR',
                            'CL',
                            'CO',
                            'EC',
                            'PE',
                            'UY',
                            'MX',
                            'BR',
                        ]
                    ),
                ],
                'is_local_partner'    => true,
                'category_other'      => [
                    'AR',
                    'CL',
                    'CO',
                    'EC',
                    'PE',
                    'UY',
                    'MX',
                    'BR',
                ],
                'category_additional' => [],
            ],
            // This is for backwards compatibility only (WC < 5.10.0-dev or WCA < 2.9.0-dev).
            [
                'id'          => 'woocommerce_payments',
                'title'       => __('WooPayments', 'woocommerce'),
                'content'     => __(
                    'Manage transactions without leaving your WordPress Dashboard. Only with WooPayments.',
                    'woocommerce'
                ),
                'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
                'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
                'plugins'     => [ 'woocommerce-payments' ],
                'description' => __('With WooPayments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies. Track cash flow and manage recurring revenue directly from your store’s dashboard - with no setup costs or monthly fees.', 'woocommerce'),
                'is_visible'  => [
                    self::get_rules_for_cbd(false),
                    self::get_rules_for_countries(self::get_wcpay_countries()),
                    (object) [
                        'type'     => 'plugin_version',
                        'plugin'   => 'woocommerce',
                        'version'  => '5.10.0-dev',
                        'operator' => '<',
                    ],
                    (object) [
                        'type'     => 'or',
                        'operands' => (object) [
                            (object) [
                                'type'    => 'not',
                                'operand' => [
                                    (object) [
                                        'type'    => 'plugins_activated',
                                        'plugins' => [ 'woocommerce-admin' ],
                                    ],
                                ],
                            ],
                            (object) [
                                'type'     => 'plugin_version',
                                'plugin'   => 'woocommerce-admin',
                                'version'  => '2.9.0-dev',
                                'operator' => '<',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id'          => 'woocommerce_payments:without-in-person-payments',
                'title'       => __('WooPayments', 'woocommerce'),
                'content'     => __(
                    'Manage transactions without leaving your WordPress Dashboard. Only with WooPayments.',
                    'woocommerce'
                ),
                'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
                'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
                'plugins'     => [ 'woocommerce-payments' ],
                'description' => __('With WooPayments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies. Track cash flow and manage recurring revenue directly from your store’s dashboard - with no setup costs or monthly fees.', 'woocommerce'),
                'is_visible'  => [
                    self::get_rules_for_cbd(false),
                    self::get_rules_for_countries(array_diff(self::get_wcpay_countries(), [ 'US', 'CA' ])),
                    (object) [
                        'type'     => 'or',
                        // Older versions of WooCommerce Admin require the ID to be `woocommerce-payments` to show the suggestion card.
                        'operands' => (object) [
                            (object) [
                                'type'     => 'plugin_version',
                                'plugin'   => 'woocommerce-admin',
                                'version'  => '2.9.0-dev',
                                'operator' => '>=',
                            ],
                            (object) [
                                'type'     => 'plugin_version',
                                'plugin'   => 'woocommerce',
                                'version'  => '5.10.0-dev',
                                'operator' => '>=',
                            ],
                        ],
                    ],
                ],
            ],
            // This is the same as the above, but with a different description for countries that support in-person payments such as US and CA.
            [
                'id'          => 'woocommerce_payments:with-in-person-payments',
                'title'       => __('WooPayments', 'woocommerce'),
                'content'     => __(
                    'Manage transactions without leaving your WordPress Dashboard. Only with WooPayments.',
                    'woocommerce'
                ),
                'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
                'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay.svg',
                'plugins'     => [ 'woocommerce-payments' ],
                'description' => __('With WooPayments, you can securely accept major cards, Apple Pay, and payments in over 100 currencies – with no setup costs or monthly fees – and you can now accept in-person payments with the Woo mobile app.', 'woocommerce'),
                'is_visible'  => [
                    self::get_rules_for_cbd(false),
                    self::get_rules_for_countries([ 'US', 'CA' ]),
                    (object) [
                        'type'     => 'or',
                        // Older versions of WooCommerce Admin require the ID to be `woocommerce-payments` to show the suggestion card.
                        'operands' => (object) [
                            (object) [
                                'type'     => 'plugin_version',
                                'plugin'   => 'woocommerce-admin',
                                'version'  => '2.9.0-dev',
                                'operator' => '>=',
                            ],
                            (object) [
                                'type'     => 'plugin_version',
                                'plugin'   => 'woocommerce',
                                'version'  => '5.10.0-dev',
                                'operator' => '>=',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id'          => 'woocommerce_payments:bnpl',
                'title'       => __('Activate BNPL instantly on WooPayments', 'woocommerce'),
                'content'     => __(
                    'The world’s favorite buy now, pay later options and many more are right at your fingertips with WooPayments — all from one dashboard, without needing multiple extensions and logins.',
                    'woocommerce'
                ),
                'image'       => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay-bnpl.svg',
                'image_72x72' => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/wcpay-bnpl.svg',
                'plugins'     => [ 'woocommerce-payments' ],
                'is_visible'  => [
                    self::get_rules_for_countries(
                        array_intersect(
                            [
                                'US',
                                'CA',
                                'AU',
                                'AT',
                                'BE',
                                'CH',
                                'DK',
                                'ES',
                                'FI',
                                'FR',
                                'DE',
                                'GB',
                                'IT',
                                'NL',
                                'NO',
                                'PL',
                                'SE',
                                'NZ',
                            ],
                            self::get_wcpay_countries()
                        ),
                    ),
                    self::get_rules_for_cbd(false),
                    self::get_rules_for_wcpay_activated(true),
                    self::get_rules_for_wcpay_connected(true),
                ],
            ],
            [
                'id'                  => 'zipmoney',
                'title'               => __('Zip Co - Buy Now, Pay Later', 'woocommerce'),
                'content'             => __('Give your customers the power to pay later, interest free and watch your sales grow.', 'woocommerce'),
                'image'               => WC_ADMIN_IMAGES_FOLDER_URL . '/onboarding/zipco.png',
                'image_72x72'         => WC_ADMIN_IMAGES_FOLDER_URL . '/payment_methods/72x72/zipco.png',
                'plugins'             => [ 'zipmoney-payments-woocommerce' ],
                'is_visible'          => false,
                'category_other'      => [],
                'category_additional' => [],
            ],
        ];

        $base_location = wc_get_base_location();
        $country       = $base_location['country'];
        foreach ($payment_gateways as $index => $payment_gateway) {
            $payment_gateways[ $index ]['recommendation_priority'] = self::get_recommendation_priority($payment_gateway['id'], $country);
        }

        return $payment_gateways;
    }

    /**
     * Get array of countries supported by WCPay depending on feature flag.
     *
     * @return array Array of countries.
     */
    public static function get_wcpay_countries(): array
    {
        return [ 'US', 'PR', 'AU', 'CA', 'CY', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'IE', 'IT', 'LU', 'LT', 'LV', 'NO', 'NZ', 'MT', 'AT', 'BE', 'NL', 'PL', 'PT', 'CH', 'HK', 'SI', 'SK', 'SG', 'BG', 'CZ', 'HR', 'HU', 'RO', 'SE', 'JP', 'AE' ];
    }

    /**
     * Get rules that match the store base location to one of the provided countries.
     *
     * @param array $countries Array of countries to match.
     * @return object Rules to match.
     */
    public static function get_rules_for_countries($countries)
    {
        $rules = [];

        foreach ($countries as $country) {
            $rules[] = (object) [
                'type'      => 'base_location_country',
                'value'     => $country,
                'operation' => '=',
            ];
        }

        return (object) [
            'type'     => 'or',
            'operands' => $rules,
        ];
    }

    /**
     * Get rules that match the store's selling venues.
     *
     * @param array $selling_venues Array of venues to match.
     * @return object Rules to match.
     */
    public static function get_rules_for_selling_venues($selling_venues)
    {
        $rules = [];

        foreach ($selling_venues as $venue) {
            $rules[] = (object) [
                'type'         => 'option',
                'transformers' => [
                    (object) [
                        'use'       => 'dot_notation',
                        'arguments' => (object) [
                            'path' => 'selling_venues',
                        ],
                    ],
                ],
                'option_name'  => 'woocommerce_onboarding_profile',
                'operation'    => '=',
                'value'        => $venue,
                'default'      => [],
            ];
        }

        return (object) [
            'type'     => 'or',
            'operands' => $rules,
        ];
    }

    /**
     * Get rules for when selling offline for core profiler.
     *
     * @return object Rules to match.
     */
    public static function get_rules_selling_offline()
    {
        return (object) [
            'type'         => 'option',
            'transformers' => [
                (object) [
                    'use'       => 'dot_notation',
                    'arguments' => (object) [
                        'path' => 'selling_online_answer',
                    ],
                ],
            ],
            'option_name'  => 'woocommerce_onboarding_profile',
            'operation'    => 'in',
            'value'        => [ 'no_im_selling_offline', 'im_selling_both_online_and_offline' ],
            'default'      => '',
        ];
    }

    /**
     * Get default rules for CBD based on given argument.
     *
     * @param bool $should_have Whether or not the store should have CBD as an industry (true) or not (false).
     * @return object Rules to match.
     */
    public static function get_rules_for_cbd($should_have)
    {
        return (object) [
            'type'         => 'option',
            'transformers' => [
                (object) [
                    'use'       => 'dot_notation',
                    'arguments' => (object) [
                        'path' => 'industry',
                    ],
                ],
                (object) [
                    'use'       => 'array_column',
                    'arguments' => (object) [
                        'key' => 'slug',
                    ],
                ],
            ],
            'option_name'  => 'woocommerce_onboarding_profile',
            'operation'    => $should_have ? 'contains' : '!contains',
            'value'        => 'cbd-other-hemp-derived-products',
            'default'      => [],
        ];
    }

    /**
     * Get default rules for the WooPayments plugin being installed and activated.
     *
     * @param bool $should_be Whether WooPayments should be activated.
     *
     * @return object Rules to match.
     */
    public static function get_rules_for_wcpay_activated($should_be)
    {
        $active_rule = (object) [
            'type'    => 'plugins_activated',
            'plugins' => [ 'woocommerce-payments' ],
        ];

        if ($should_be) {
            return $active_rule;
        }

        return (object) [
            'type'    => 'not',
            'operand' => [ $active_rule ],
        ];
    }

    /**
     * Get default rules for WooPayments being connected or not.
     *
     * This does not include the check for the WooPayments plugin to be active.
     *
     * @param bool $should_be Whether WooPayments should be connected.
     *
     * @return object Rules to match.
     */
    public static function get_rules_for_wcpay_connected($should_be)
    {
        return (object) [
            'type'         => 'option',
            'transformers' => [
                // Extract only the 'data' key from the option.
                (object) [
                    'use'       => 'dot_notation',
                    'arguments' => (object) [
                        'path' => 'data',
                    ],
                ],
                // Extract the keys from the data array.
                (object) [
                    'use' => 'array_keys',
                ],
            ],
            'option_name'  => 'wcpay_account_data',
            // The rule will be look for the 'account_id' key in the account data array.
            'operation'    => $should_be ? 'contains' : '!contains',
            'value'        => 'account_id',
            'default'      => [],
        ];
    }

    /**
     * Get recommendation priority for a given payment gateway by id and country.
     * If country is not supported, return null.
     *
     * @param string $gateway_id Payment gateway id.
     * @param string $country_code Store country code.
     * @return int|null Priority. Priority is 0-indexed, so 0 is the highest priority.
     */
    private static function get_recommendation_priority($gateway_id, $country_code)
    {
        $recommendation_priority_map = [
            'US' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'square_credit_card',
                'amazon_payments_advanced',
                'affirm',
                'afterpay',
                'klarna_payments',
            ],
            'CA' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'square_credit_card',
                'affirm',
                'afterpay',
                'klarna_payments',
            ],
            'AT' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'BE' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'BG' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
            ],
            'HR' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'ppcp-gateway',
            ],
            'CH' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'mollie_wc_gateway_banktransfer',
                'klarna_payments',
            ],
            'CY' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'amazon_payments_advanced',
            ],
            'CZ' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
            ],
            'DK' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'EE' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
            ],
            'ES' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'mollie_wc_gateway_banktransfer',
                'square_credit_card',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'FI' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'mollie_wc_gateway_banktransfer',
                'kco',
                'klarna_payments',
            ],
            'FR' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'square_credit_card',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'DE' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'GB' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'square_credit_card',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'GR' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
            ],
            'HU' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'amazon_payments_advanced',
            ],
            'IE' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'square_credit_card',
                'amazon_payments_advanced',
            ],
            'IT' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'LV' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
            ],
            'LT' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
            ],
            'LU' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'amazon_payments_advanced',
            ],
            'MT' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
            ],
            'NL' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'NO' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'kco',
                'klarna_payments',
            ],
            'PL' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'mollie_wc_gateway_banktransfer',
                'klarna_payments',
            ],
            'PT' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'airwallex_main',
                'amazon_payments_advanced',
            ],
            'RO' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
            ],
            'SK' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
            ],
            'SL' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'amazon_payments_advanced',
            ],
            'SE' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'kco',
                'klarna_payments',
                'amazon_payments_advanced',
            ],
            'MX' => [
                'stripe',
                'woo-mercado-pago-custom',
                'ppcp-gateway',
                'klarna_payments',
            ],
            'BR' => [ 'stripe', 'woo-mercado-pago-custom', 'ppcp-gateway' ],
            'AR' => [ 'woo-mercado-pago-custom', 'ppcp-gateway' ],
            'BO' => [],
            'CL' => [ 'woo-mercado-pago-custom', 'ppcp-gateway' ],
            'CO' => [ 'woo-mercado-pago-custom', 'ppcp-gateway' ],
            'EC' => [ 'woo-mercado-pago-custom', 'ppcp-gateway' ],
            'FK' => [],
            'GF' => [],
            'GY' => [],
            'PY' => [],
            'PE' => [ 'woo-mercado-pago-custom', 'ppcp-gateway' ],
            'SR' => [],
            'UY' => [ 'woo-mercado-pago-custom', 'ppcp-gateway' ],
            'VE' => [ 'ppcp-gateway' ],
            'AU' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'airwallex_main',
                'ppcp-gateway',
                'square_credit_card',
                'afterpay',
                'klarna_payments',
            ],
            'NZ' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'airwallex_main',
                'ppcp-gateway',
                'klarna_payments',
            ],
            'HK' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'airwallex_main',
                'ppcp-gateway',
                'payoneer-checkout',
            ],
            'JP' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'ppcp-gateway',
                'square_credit_card',
                'amazon_payments_advanced',
            ],
            'SG' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
                'stripe',
                'airwallex_main',
                'ppcp-gateway',
            ],
            'CN' => [ 'airwallex_main', 'ppcp-gateway', 'payoneer-checkout' ],
            'FJ' => [],
            'GU' => [],
            'ID' => [ 'stripe', 'ppcp-gateway' ],
            'IN' => [ 'stripe', 'razorpay', 'payubiz', 'ppcp-gateway' ],
            'ZA' => [ 'payfast', 'paystack' ],
            'NG' => [ 'paystack' ],
            'GH' => [ 'paystack' ],
            'AE' => [
                'woocommerce_payments:with-in-person-payments',
                'woocommerce_payments:without-in-person-payments',
                'woocommerce_payments',
            ],
        ];

        // If the country code is not in the list, return default priority.
        if (! isset($recommendation_priority_map[ $country_code ])) {
            return self::get_default_recommendation_priority($gateway_id);
        }

        $index = array_search($gateway_id, $recommendation_priority_map[ $country_code ], true);

        // If the gateway is not in the list, return the last index + 1.
        if (false === $index) {
            return count($recommendation_priority_map[ $country_code ]);
        }

        return $index;
    }

    /**
     * Get the default recommendation priority for a payment gateway.
     * This is used when a country is not in the $recommendation_priority_map array.
     *
     * @param string $id Payment gateway id.
     * @return int Priority.
     */
    private static function get_default_recommendation_priority($id)
    {
        if (! $id || ! array_key_exists($id, self::$recommendation_priority)) {
            return null;
        }
        return self::$recommendation_priority[ $id ];
    }
}
