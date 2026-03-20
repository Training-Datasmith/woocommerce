<?php

declare (strict_types=1);
/**
 * WooCommerce Transient Notices
 */
namespace Automattic\Woo_Commerce\Admin\Features;

/**
 * Shows print shipping label banner on edit order page.
 */
class Transient_Notices
{
    /**
     * Option name for the queue.
     */
    public const QUEUE_OPTION = 'woocommerce_admin_transient_notices_queue';
    /**
     * Constructor
     */
    public function __construct()
    {
        add_filter('woocommerce_admin_preload_options', $this->preload_options(...));
    }
    /**
     * Get all notices in the queue.
     *
     * @return array
     */
    public static function get_queue()
    {
        return get_option(self::QUEUE_OPTION, []);
    }
    /**
     * Get all notices in the queue by a given user ID.
     *
     * @param int $user_id User ID.
     */
    public static function get_queue_by_user($user_id): array
    {
        $notices = self::get_queue();
        return array_filter($notices, fn(array $notice) => !isset($notice['user_id']) || null === $notice['user_id'] || $user_id === $notice['user_id']);
    }
    /**
     * Get a notice by ID.
     *
     * @param array $notice_id Notice of ID to get.
     * @return array|null
     */
    public static function get($notice_id)
    {
        $queue = self::get_queue();
        return $queue[$notice_id] ?? null;
    }
    /**
     * Add a notice to be shown.
     *
     * @param array $notice Notice.
     *    $notice = array(
     *      'id'      => (string) Unique ID for the notice. Required.
     *      'user_id' => (int|null) User ID to show the notice to.
     *      'status'  => (string) info|error|success
     *      'content' => (string) Content to be shown for the notice. Required.
     *      'options' => (array) Array of options to be passed to the notice component.
     *       See https://developer.wordpress.org/block-editor/reference-guides/data/data-core-notices/#createNotice for available options.
     *    ).
     */
    public static function add(array $notice): void
    {
        $queue = self::get_queue();
        $defaults = ['user_id' => null, 'status' => 'info', 'options' => []];
        $notice_data = array_merge($defaults, $notice);
        $notice_data['options'] = (object) $notice_data['options'];
        $queue[$notice['id']] = $notice_data;
        update_option(self::QUEUE_OPTION, $queue);
    }
    /**
     * Remove a notice by ID.
     *
     * @param array $notice_id Notice of ID to remove.
     */
    public static function remove($notice_id): void
    {
        $queue = self::get_queue();
        unset($queue[$notice_id]);
        update_option(self::QUEUE_OPTION, $queue);
    }
    /**
     * Preload options to prime state of the application.
     *
     * @param array $options Array of options to preload.
     * @return array
     */
    public function preload_options($options)
    {
        $options[] = self::QUEUE_OPTION;
        return $options;
    }
}