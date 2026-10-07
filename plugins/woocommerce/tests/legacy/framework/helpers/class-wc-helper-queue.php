<?php

declare(strict_types=1);
/**
 * Helper code for wc-admin unit tests.
 *
 * @package WooCommerce\Admin\Tests\Framework\Helpers
 */

/**
 * Class WC_Helper_Queue.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Queue
{
    /**
     * Cap queue processing so a large Action Scheduler backlog cannot stall the full PHPUnit suite.
     */
    private const MAX_PENDING_JOBS_PER_RUN = 500;
    /**
     * Get all pending queued actions.
     * @param string|null $group Optionally. Filter the actions by group.
     * @return array Pending jobs.
     */
    public static function get_all_pending($group = null)
    {
        $args = [
            'per_page' => -1,
            'status'   => 'pending',
            'claimed'  => false,
        ];

        if ($group) {
            $args['group'] = $group;
        }

        return WC()->queue()->search($args);
    }

    /**
     * Run all pending queued actions.
     * @param string|null $group Optionally. Filter the actions by group.
     * @return void
     */
    public static function run_all_pending($group = null)
    {
        $queue_runner = new ActionScheduler_QueueRunner();
        $processed    = 0;
        $jobs         = self::get_all_pending($group);
        while ($jobs && $processed < self::MAX_PENDING_JOBS_PER_RUN) {
            foreach ($jobs as $job_id => $job) {
                if ($processed >= self::MAX_PENDING_JOBS_PER_RUN) {
                    break;
                }
                $queue_runner->process_action($job_id);
                $processed++;
            }
            $jobs = self::get_all_pending($group);
        }
    }

    /**
     * Cancel all pending actions.
     *
     * @return void
     */
    public static function cancel_all_pending()
    {
        global $wpdb;

        // Force immediate hard delete for Action Scheduler < 3.0.
        $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'scheduled-action'");

        // Delete pending actions for Action Scheduler >= 3.0.
        $actions_table = $wpdb->prefix . 'actionscheduler_actions';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $actions_table)) === $actions_table) {
            $wpdb->query("DELETE FROM {$actions_table} WHERE status IN ('pending', 'in-progress')");
        }

        $store = ActionScheduler_Store::instance();

        if (is_callable([ $store, 'cancel_actions_by_group' ])) {
            $store->cancel_actions_by_group('wc-admin-data');
        }
    }
}
