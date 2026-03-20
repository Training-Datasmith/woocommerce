<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Order status values as defined in the Agentic Commerce Protocol.
 *
 * @since 10.3.0
 */
class Order_Status
{
    /**
     * Order has been created.
     */
    public const CREATED = 'created';
    /**
     * Order requires manual review.
     */
    public const MANUAL_REVIEW = 'manual_review';
    /**
     * Order has been confirmed.
     */
    public const CONFIRMED = 'confirmed';
    /**
     * Order has been canceled.
     */
    public const CANCELED = 'canceled';
    /**
     * Order has been shipped.
     */
    public const SHIPPED = 'shipped';
    /**
     * Order has been fulfilled.
     */
    public const FULFILLED = 'fulfilled';
    /**
     * Get all valid order statuses.
     *
     * @return array Array of valid order status values.
     */
    public static function get_all(): array
    {
        return [self::CREATED, self::MANUAL_REVIEW, self::CONFIRMED, self::CANCELED, self::SHIPPED, self::FULFILLED];
    }
    /**
     * Check if a status is valid.
     *
     * @param string $status Status to check.
     * @return bool True if valid, false otherwise.
     */
    public static function is_valid($status): bool
    {
        return in_array($status, self::get_all(), true);
    }
}