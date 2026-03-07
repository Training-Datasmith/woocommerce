<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ComingSoon;

/**
 * Adds hooks to invalidate caches when the coming soon settings are changed.
 */
class ComingSoonCacheInvalidator
{
    /**
     * Sets up the hooks.
     *
     * @internal
     */
    final public function init(): void
    {
        add_action('update_option_woocommerce_coming_soon', $this->invalidate_caches(...));
        add_action('update_option_woocommerce_store_pages_only', $this->invalidate_caches(...));
    }

    /**
     * Invalidate the WordPress object cache and other known caches.
     *
     * @internal
     */
    public function invalidate_caches(): void
    {
        // Standard WordPress object cache invalidation.
        wp_cache_flush();

        /**
         * Temporary solution to invalidate the WordPress.com Edge Cache. We can trigger
         * invalidation by publishing any post. It should be refactored with a supported integration.
         */
        $cart_page_id = get_option('woocommerce_cart_page_id') ?? null;
        if ($cart_page_id) {
            // Re-publish the coming soon page. Has the side-effect of invalidating the Edge Cache.
            wp_update_post(
                [
                    'ID'          => $cart_page_id,
                    'post_status' => 'publish',
                ]
            );
        }
    }
}
