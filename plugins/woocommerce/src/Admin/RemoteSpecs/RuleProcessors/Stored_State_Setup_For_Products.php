<?php

declare (strict_types=1);
/**
 * Handles stored state setup for products.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Remote_Inbox_Notifications\Remote_Inbox_Notifications_Engine;
/**
 * Handles stored state setup for products.
 */
class Stored_State_Setup_For_Products
{
    public const ASYNC_RUN_REMOTE_NOTIFICATIONS_ACTION_NAME = 'woocommerce_admin/stored_state_setup_for_products/async/run_remote_notifications';
    /**
     * Initialize the class via the admin_init hook.
     */
    public static function admin_init(): void
    {
        add_action('product_page_product_importer', self::run_on_product_importer(...));
        add_action('transition_post_status', self::run_on_transition_post_status(...), 10, 3);
    }
    /**
     * Initialize the class via the init hook.
     *
     * @internal
     */
    final public static function init(): void
    {
        add_action(self::ASYNC_RUN_REMOTE_NOTIFICATIONS_ACTION_NAME, self::run_remote_notifications(...));
    }
    /**
     * Run the remote notifications engine. This is triggered by
     * action-scheduler after a product is added. It also cleans up from
     * setting the product count increment.
     */
    public static function run_remote_notifications(): void
    {
        Remote_Inbox_Notifications_Engine::run();
    }
    /**
     * Set initial stored state values.
     *
     * @param object $stored_state The stored state.
     *
     * @return object The stored state.
     */
    public static function init_stored_state($stored_state)
    {
        $stored_state->there_were_no_products = !self::are_there_products();
        $stored_state->there_are_now_products = !$stored_state->there_were_no_products;
        return $stored_state;
    }
    /**
     * Are there products query.
     */
    private static function are_there_products(): bool
    {
        $query = new \WC_Product_Query(['limit' => 1, 'paginate' => true, 'return' => 'ids', 'status' => ['publish']]);
        $products = $query->get_products();
        $count = $products->total;
        return $count > 0;
    }
    /**
     * Runs on product importer steps.
     */
    public static function run_on_product_importer(): void
    {
        // We're only interested in when the importer completes.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (!isset($_REQUEST['step'])) {
            return;
        }
        if ('done' !== $_REQUEST['step']) {
            return;
        }
        // phpcs:enable
        self::update_stored_state_and_possibly_run_remote_notifications();
    }
    /**
     * Runs when a post status transitions, but we're only interested if it is
     * a product being published.
     *
     * @param string $new_status The new status.
     * @param string $old_status The old status.
     * @param Post   $post       The post.
     */
    public static function run_on_transition_post_status($new_status, $old_status, $post): void
    {
        if ('product' !== $post->post_type || 'publish' !== $new_status) {
            return;
        }
        self::update_stored_state_and_possibly_run_remote_notifications();
    }
    /**
     * Enqueues an async action (using action-scheduler) to run remote
     * notifications.
     */
    private static function update_stored_state_and_possibly_run_remote_notifications(): void
    {
        $stored_state = Remote_Inbox_Notifications_Engine::get_stored_state();
        // If the stored_state is the same, we don't need to run remote notifications to avoid unnecessary action scheduling.
        if (true === $stored_state->there_are_now_products) {
            return;
        }
        $stored_state->there_are_now_products = true;
        Remote_Inbox_Notifications_Engine::update_stored_state($stored_state);
        // Run self::run_remote_notifications asynchronously.
        as_enqueue_async_action(self::ASYNC_RUN_REMOTE_NOTIFICATIONS_ACTION_NAME);
    }
}