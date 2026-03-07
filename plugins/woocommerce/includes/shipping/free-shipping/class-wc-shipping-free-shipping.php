<?php

declare(strict_types=1);
/**
 * Class WC_Shipping_Free_Shipping file.
 *
 * @package WooCommerce\Shipping
 */

use Automattic\WooCommerce\Utilities\NumberUtil;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Free Shipping Method.
 *
 * A simple shipping method for free shipping.
 *
 * @class   WC_Shipping_Free_Shipping
 * @version 2.6.0
 * @package WooCommerce\Classes\Shipping
 */
class WC_Shipping_Free_Shipping extends WC_Shipping_Method
{
    /**
     * Min amount to be valid.
     *
     * @var integer
     */
    public $min_amount = 0;

    /**
     * Requires option.
     *
     * @var string
     */
    public $requires = '';

    /**
     * Ignore discounts.
     *
     * If set, free shipping would be available based on pre-discount order amount.
     *
     * @var string
     */
    public $ignore_discounts;

    /**
     * Constructor.
     *
     * @param int $instance_id Shipping method instance.
     */
    public function __construct($instance_id = 0)
    {
        $this->id                 = 'free_shipping';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Free shipping', 'woocommerce');
        $this->method_description = __('Free shipping is a special method which can be triggered with coupons and minimum spends.', 'woocommerce');
        $this->supports           = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        $this->init();
    }

    /**
     * Initialize free shipping.
     */
    public function init(): void
    {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title            = $this->get_option('title');
        $this->min_amount       = $this->get_option('min_amount', 0);
        $this->requires         = $this->get_option('requires');
        $this->ignore_discounts = $this->get_option('ignore_discounts');

        // Actions.
        add_action('woocommerce_update_options_shipping_' . $this->id, $this->process_admin_options(...));
        add_action('admin_footer', [ 'WC_Shipping_Free_Shipping', 'enqueue_admin_js' ], 10); // Priority needs to be higher than wc_print_js (25).
    }

    /**
     * Sanitize the cost field.
     *
     * @since 8.3.0
     * @param string $value Unsanitized value.
     * @throws Exception Last error triggered.
     */
    public function sanitize_cost($value): string
    {
        return \Automattic\WooCommerce\Utilities\NumberUtil::sanitize_cost_in_current_locale($value);
    }

    /**
     * Init form fields.
     */
    public function init_form_fields(): void
    {
        $this->instance_form_fields = [
            'title'            => [
                'title'       => __('Name', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Your customers will see the name of this shipping method during checkout.', 'woocommerce'),
                'default'     => $this->method_title,
                'placeholder' => __('e.g. Free shipping', 'woocommerce'),
                'desc_tip'    => true,
            ],
            'requires'         => [
                'title'   => __('Free shipping requires', 'woocommerce'),
                'type'    => 'select',
                'class'   => 'wc-enhanced-select',
                'default' => '',
                'options' => [
                    ''           => __('No requirement', 'woocommerce'),
                    'coupon'     => __('A valid free shipping coupon', 'woocommerce'),
                    'min_amount' => __('A minimum order amount', 'woocommerce'),
                    'either'     => __('A minimum order amount OR coupon', 'woocommerce'),
                    'both'       => __('A minimum order amount AND coupon', 'woocommerce'),
                ],
            ],
            'min_amount'       => [
                'title'             => __('Minimum order amount', 'woocommerce'),
                'type'              => 'text',
                'class'             => 'wc-shipping-modal-price',
                'placeholder'       => wc_format_localized_price(0),
                'description'       => __('Customers will need to spend this amount to get free shipping.', 'woocommerce'),
                'default'           => '0',
                'desc_tip'          => true,
                'sanitize_callback' => $this->sanitize_cost(...),
            ],
            'ignore_discounts' => [
                'title'       => __('Coupons discounts', 'woocommerce'),
                'label'       => __('Apply minimum order rule before coupon discount', 'woocommerce'),
                'type'        => 'checkbox',
                'description' => __('If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce'),
                'default'     => 'no',
                'desc_tip'    => true,
            ],
        ];
    }

    /**
     * Get setting form fields for instances of this shipping method within zones.
     *
     * @return array
     */
    public function get_instance_form_fields()
    {
        return parent::get_instance_form_fields();
    }

    /**
     * See if free shipping is available based on the package and cart.
     *
     * @param array $package Shipping package.
     * @return bool
     */
    public function is_available($package)
    {
        $has_coupon         = false;
        $has_met_min_amount = false;

        if (in_array($this->requires, [ 'coupon', 'either', 'both' ], true)) {
            $coupons = WC()->cart->get_coupons();

            if ($coupons) {
                foreach ($coupons as $coupon) {
                    if ($coupon->is_valid() && $coupon->get_free_shipping()) {
                        $has_coupon = true;
                        break;
                    }
                }
            }
        }

        if (in_array($this->requires, [ 'min_amount', 'either', 'both' ], true)) {
            $total = WC()->cart->get_displayed_subtotal();

            if ('no' === $this->ignore_discounts) {
                $total = $total - WC()->cart->get_discount_total();
                if (WC()->cart->display_prices_including_tax()) {
                    $total = $total - WC()->cart->get_discount_tax();
                }
            }

            $total = NumberUtil::round($total, wc_get_price_decimals());

            if ($total >= $this->min_amount) {
                $has_met_min_amount = true;
            }
        }

        $is_available = match ($this->requires) {
            'min_amount' => $has_met_min_amount,
            'coupon' => $has_coupon,
            'both' => $has_met_min_amount && $has_coupon,
            'either' => $has_met_min_amount || $has_coupon,
            default => true,
        };

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this);
    }

    /**
     * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
     *
     * @uses WC_Shipping_Method::add_rate()
     *
     * @param array $package Shipping package.
     */
    public function calculate_shipping($package = []): void
    {
        $this->add_rate(
            [
                'label'   => $this->title,
                'cost'    => 0,
                'taxes'   => false,
                'package' => $package,
            ]
        );
    }

    /**
     * Enqueue JS to handle free shipping options.
     *
     * Static so that's enqueued only once.
     */
    public static function enqueue_admin_js(): void
    {
        $handle = 'wc-admin-shipping-free-shipping';
        wp_register_script($handle, '', [ 'jquery' ], WC_VERSION, [ 'in_footer' => true ]);
        wp_enqueue_script($handle);
        wp_add_inline_script(
            $handle,
            "jQuery( function( $ ) {
				function wcFreeShippingShowHideMinAmountField( el ) {
					const form = $( el ).closest( 'form' );
					const minAmountField = $( '#woocommerce_free_shipping_min_amount', form ).closest( 'tr' );
					const ignoreDiscountField = $( '#woocommerce_free_shipping_ignore_discounts', form ).closest( 'tr' );
					if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
						minAmountField.hide();
						ignoreDiscountField.hide();
					} else {
						minAmountField.show();
						ignoreDiscountField.show();
					}
				}

				$( document.body ).on( 'change', '#woocommerce_free_shipping_requires', function() {
					wcFreeShippingShowHideMinAmountField( this );
				});

				// Change while load.
				$( '#woocommerce_free_shipping_requires' ).trigger( 'change' );
				$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
					if ( 'wc-modal-shipping-method-settings' === target ) {
						wcFreeShippingShowHideMinAmountField( $( '#wc-backbone-modal-dialog #woocommerce_free_shipping_requires', evt.currentTarget ) );
					}
				} );
			});"
        );
    }
}
