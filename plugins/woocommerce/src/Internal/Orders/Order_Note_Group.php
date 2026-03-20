<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Orders;

/**
 * Enum class for order note groups. This is stored as meta data to categorize order notes.
 *
 * This is not surfaced in core UI presently.
 */
final class OrderNoteGroup
{
    /**
     * Any note concerning errors.
     *
     * @var string
     */
    public const ERROR = 'error';

    /**
     * Any note concerning emails to customers.
     *
     * @var string
     */
    public const EMAIL_NOTIFICATION = 'email_notification';

    /**
     * Any note concerning stock levels.
     *
     * @var string
     */
    public const PRODUCT_STOCK = 'product_stock';

    /**
     * Any note concerning payments.
     *
     * @var string
     */
    public const PAYMENT = 'payment';

    /**
     * Any note concerning order updates.
     *
     * @var string
     */
    public const ORDER_UPDATE = 'order_update';

    /**
     * Any note concerning fulfillments.
     *
     * @var string
     */
    public const FULFILLMENT = 'fulfillment';

    /**
     * Get the default group title for a given group.
     *
     * @param string $group The group.
     * @return string The default group title.
     */
    public static function get_default_group_title(string $group): string
    {
        return match ($group) {
            self::PRODUCT_STOCK => __('Product stock', 'woocommerce'),
            self::PAYMENT => __('Payment', 'woocommerce'),
            self::EMAIL_NOTIFICATION => __('Email notification', 'woocommerce'),
            self::ERROR => __('Error', 'woocommerce'),
            self::FULFILLMENT => __('Fulfillment', 'woocommerce'),
            default => __('Order updated', 'woocommerce'),
        };
    }
}
