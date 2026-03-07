<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

use WC_Order;

defined('ABSPATH') || exit;

/**
 * Notification for new WooCommerce orders.
 *
 * @since 10.7.0
 */
class NewOrderNotification extends Notification
{
    /**
     * The notification type identifier, this should match the subtype or type
     * (if there isn't a subtype) values attributed to notes in WordPress.com.
     */
    public const TYPE = 'store_order';

    /**
     * The icon to use in the notification.
     */
    public const ICON = 'https://s.wp.com/wp-content/mu-plugins/notes/images/update-payment-2x.png';

    /**
     * An array of emojis to select from when forming the payload.
     */
    public const EMOJI_LIST = [ '🎉', '🎊', '🥳', '👏', '🙌' ];

    /**
     * Creates a new order notification.
     *
     * @param int $order_id The order ID.
     *
     * @since 10.7.0
     */
    public function __construct(int $order_id)
    {
        parent::__construct(self::TYPE, $order_id);
    }

    /**
     * Returns the WPCOM-ready payload for this notification.
     *
     * Returns null if the order no longer exists.
     *
     *
     * @since 10.7.0
     */
    public function to_payload(): ?array
    {
        $order = WC()->call_function('wc_get_order', $this->get_resource_id());

        if (! $order || ! $order instanceof WC_Order) {
            return null;
        }

        return [
            'type'        => self::TYPE,
            'icon'        => self::ICON,
            'blog_id'     => get_current_blog_id(),
            'resource_id' => $this->get_resource_id(),
            'title'       => [
                /**
                 * This will be translated in WordPress.com, format:
                 * 1: emoji
                 */
                'format' => 'You have a new order! %1$s',
                'args'   => [ self::EMOJI_LIST[ wp_rand(0, count(self::EMOJI_LIST) - 1) ] ],
            ],
            'message'     => [
                /**
                 * This will be translated in WordPress.com, format:
                 * 1: order total, 2: site title
                 */
                'format' => 'New order for %1$s on %2$s',
                'args'   => [
                    wp_strip_all_tags($order->get_formatted_order_total()),
                    wp_strip_all_tags(get_bloginfo('name')),
                ],
            ],
            'meta'        => [
                'order_id' => $this->get_resource_id(),
            ],
        ];
    }
}
