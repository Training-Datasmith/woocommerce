<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * CartExpressPaymentBlock class.
 */
class CartExpressPaymentBlock extends AbstractInnerBlock
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
