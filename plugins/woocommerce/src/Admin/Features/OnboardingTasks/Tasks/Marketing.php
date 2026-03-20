<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Tasks;

use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Task;
/**
 * Marketing Task
 */
class Marketing extends Task
{
    /**
     * Constructor
     *
     * @param TaskList $task_list Parent task list.
     */
    public function __construct($task_list)
    {
        parent::__construct($task_list);
        add_action('activated_plugin', $this->on_activated_plugin(...), 10, 1);
    }
    /**
     * Mark the task as complete when related plugins are activated.
     */
    public function on_activated_plugin($plugin): void
    {
        basename(plugin_basename($plugin), '.php');
        // Example: How to mark the marketing task as complete when a specific plugin is activated.
        /**
         * Example:
         * if (
         *     $plugin_basename === 'multichannel-by-cedcommerce' &&
         *     $this->task_list->visible &&
         *     ! $this->task_list->is_hidden() &&
         *     ! $this->is_complete()
         * ) {
         *     $this->mark_actioned();
         * }
         */
    }
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'marketing';
    }
    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Grow your business', 'woocommerce');
    }
    /**
     * Content.
     *
     * @return string
     */
    public function get_content()
    {
        return __('Add recommended marketing tools to reach new customers and grow your business', 'woocommerce');
    }
    /**
     * Time.
     *
     * @return string
     */
    public function get_time()
    {
        return __('2 minutes', 'woocommerce');
    }
    /**
     * Task visibility.
     */
    public function can_view(): bool
    {
        return Features::is_enabled('remote-free-extensions');
    }
    /**
     * Get the marketing plugins.
     *
     * @deprecated 9.3.0 Removed to improve performance.
     */
    public static function get_plugins(): array
    {
        wc_deprecated_function(__METHOD__, '9.3.0');
        return [];
    }
    /**
     * Check if the store has installed marketing extensions.
     *
     * @deprecated 9.3.0 Removed to improve performance.
     */
    public static function has_installed_extensions(): bool
    {
        wc_deprecated_function(__METHOD__, '9.3.0');
        return false;
    }
}