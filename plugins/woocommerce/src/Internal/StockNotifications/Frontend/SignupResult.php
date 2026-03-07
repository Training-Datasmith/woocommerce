<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * A class for representing the result of a signup.
 *
 * @internal
 */
class SignupResult
{
    /**
     * Constructor.
     *
     * @param string            $code The signup code.
     * @param Notification|null $notification The notification.
     */
    public function __construct(
        /**
         * The signup code.
         */
        private readonly string $code,
        /**
         * The notification.
         */
        private readonly ?Notification $notification = null
    ) {
    }

    /**
     * Get the signup code.
     */
    public function get_code(): string
    {
        return $this->code;
    }

    /**
     * Get the notification.
     */
    public function get_notification(): ?Notification
    {
        return $this->notification;
    }
}
