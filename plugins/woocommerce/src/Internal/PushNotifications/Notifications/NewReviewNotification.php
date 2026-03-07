<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications\Notifications;

use WP_Comment;

defined('ABSPATH') || exit;

/**
 * Notification for new product reviews.
 *
 * @since 10.7.0
 */
class NewReviewNotification extends Notification
{
    /**
     * The notification type identifier.
     */
    public const TYPE = 'store_review';

    /**
     * Creates a new review notification.
     *
     * @param int $comment_id The comment ID.
     *
     * @since 10.7.0
     */
    public function __construct(int $comment_id)
    {
        parent::__construct(self::TYPE, $comment_id);
    }

    /**
     * Returns the WPCOM-ready payload for this notification.
     *
     * Returns null if the comment no longer exists.
     *
     *
     * @since 10.7.0
     */
    public function to_payload(): ?array
    {
        $comment = WC()->call_function('get_comment', $this->get_resource_id());

        if (! $comment || ! $comment instanceof WP_Comment) {
            return null;
        }

        return [
            'type'        => self::TYPE,
            'blog_id'     => get_current_blog_id(),
            'resource_id' => $this->get_resource_id(),
            'title'       => [
                'format' => 'You have a new review! ⭐️',
            ],
            'message'     => [
                /**
                 * This will be translated in WordPress.com, format:
                 * 1: reviewer name, 2: product name, 3: comment content
                 */
                'format' => '%1$s left a review on %2$s: %3$s',
                'args'   => [
                    wp_strip_all_tags($comment->comment_author),
                    wp_strip_all_tags(get_the_title((int) $comment->comment_post_ID)),
                    wp_strip_all_tags($comment->comment_content),
                ],
            ],
            'icon'        => get_avatar_url($comment->comment_author_email),
            'meta'        => [
                'comment_id' => $this->get_resource_id(),
            ],
        ];
    }
}
