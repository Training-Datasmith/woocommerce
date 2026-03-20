<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * CartExpressPaymentBlock class.
 */
class Cart_Express_Payment_Block extends Abstract_Inner_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'cart-express-payment-block';
    /**
     * Uniform default_styles for the express payment buttons
     *
     * @var boolean
     */
    protected $default_styles;
    /**
     * Current styles for the express payment buttons
     *
     * @var boolean
     */
    protected $current_styles;
}