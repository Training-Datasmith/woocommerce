<?php

declare(strict_types=1);
/**
 * Handles storage and retrieval of task lists
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\ReviewShippingOptions;

/**
 * Task Lists class.
 */
class TaskLists
{
    /**
     * Class instance.
     *
     * @var TaskLists instance
     */
    protected static $instance;

    /**
     * An array of all registered lists.
     *
     * @var array
     */
    protected static $lists = [];

    /**
     * Boolean value to indicate if default tasks have been added.
     *
     * @var boolean
     */
    protected static $default_tasks_loaded = false;

    /**
     * The contents of this array is used in init_tasks() to run their init() methods.
     * If the classes do not have an init() method then nothing is executed.
     * Beyond that, adding tasks to this list has no effect, see init_default_lists() for the list of tasks.
     * that are added for each task list.
     *
     * @var array
     */
    public const DEFAULT_TASKS = [
        'StoreDetails',
        'Products',
        'WooCommercePayments',
        'Payments',
        'Tax',
        'Shipping',
        'Marketing',
        'AdditionalPayments',
        'ReviewShippingOptions',
        'GetMobileApp',
    ];

    /**
     * Get class instance.
     */
    final public static function instance()
    {
        if (! static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Initialize the task lists.
     */
    public static function init(): void
    {
        self::init_default_lists();
        add_action('admin_init', self::set_active_task(...), 5);
        add_action('init', self::init_tasks(...));
        add_action('admin_menu', self::menu_task_count(...));
        add_filter('woocommerce_admin_shared_settings', self::task_list_preloaded_settings(...), 20);
    }

    /**
     * Check if an experiment is the treatment or control.
     *
     * @param string $name Name prefix of experiment.
     */
    public static function is_experiment_treatment(string $name): bool
    {
        $anon_id        = isset($_COOKIE['tk_ai']) ? sanitize_text_field(wp_unslash($_COOKIE['tk_ai'])) : '';
        $allow_tracking = 'yes' === get_option('woocommerce_allow_tracking');
        $abtest         = new \WooCommerce\Admin\Experimental_Abtest(
            $anon_id,
            'woocommerce',
            $allow_tracking
        );

        $date = new \DateTime();
        $date->setTimeZone(new \DateTimeZone('UTC'));

        $experiment_name = sprintf(
            '%s_%s_%s',
            $name,
            $date->format('Y'),
            $date->format('m')
        );
        return $abtest->get_variation($experiment_name) === 'treatment';
    }

    /**
     * Initialize default lists.
     */
    public static function init_default_lists(): void
    {
        $tasks = [
            'StoreDetails',
            'Products',
            'Payments',
            'CustomizeStore',
            'Tax',
            'Shipping',
            'LaunchYourStore',
        ];

        if (Features::is_enabled('core-profiler')) {
            $key = array_search('StoreDetails', $tasks, true);
            if (false !== $key) {
                unset($tasks[ $key ]);
            }
        }

        self::add_list(
            [
                'id'                      => 'setup',
                'title'                   => __('Get ready to start selling', 'woocommerce'),
                'tasks'                   => $tasks,
                'display_progress_header' => true,
                'event_prefix'            => 'tasklist_',
                'options'                 => [
                    'use_completed_title' => true,
                ],
                'visible'                 => true,
            ]
        );

        self::add_list(
            [
                'id'      => 'extended',
                'title'   => __('Things to do next', 'woocommerce'),
                'sort_by' => [
                    [
                        'key'   => 'is_complete',
                        'order' => 'asc',
                    ],
                    [
                        'key'   => 'level',
                        'order' => 'asc',
                    ],
                ],
                'tasks'   => [
                    'Marketing',
                    'ExtendStore',
                    'AdditionalPayments',
                    'GetMobileApp',
                ],
            ]
        );

        if (Features::is_enabled('shipping-smart-defaults')) {
            self::add_task(
                'extended',
                new ReviewShippingOptions(
                    self::get_list('extended')
                )
            );

            // Tasklist that will never be shown in homescreen,
            // used for having tasks that are accessed by other means.
            self::add_list(
                [
                    'id'           => 'secret_tasklist',
                    'hidden_id'    => 'setup',
                    'tasks'        => [
                        'ExperimentalShippingRecommendation',
                    ],
                    'event_prefix' => 'secret_tasklist_',
                    'visible'      => false,
                ]
            );
        }

        if (has_filter('woocommerce_admin_experimental_onboarding_tasklists')) {
            /**
             * Filter to override default task lists.
             *
             * @since 7.4
             * @param array     $lists Array of tasklists.
             */
            self::$lists = apply_filters('woocommerce_admin_experimental_onboarding_tasklists', self::$lists);
        }
    }

    /**
     * Initialize tasks.
     */
    public static function init_tasks(): void
    {
        foreach (self::DEFAULT_TASKS as $task) {
            $class = 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\\' . $task;
            if (! method_exists($class, 'init')) {
                continue;
            }
            $class::init();
        }
    }

    /**
     * Temporarily store the active task to persist across page loads when necessary.
     * Most tasks do not need this.
     */
    public static function set_active_task(): void
    {
        if (! isset($_GET[ Task::ACTIVE_TASK_TRANSIENT ]) || ! current_user_can('manage_woocommerce')) { // phpcs:ignore csrf ok.
            return;
        }
        $referer = wp_get_referer();
        if (! $referer || !str_starts_with($referer, wc_admin_url())) {
            return;
        }

        $task_id = sanitize_title_with_dashes(wp_unslash($_GET[ Task::ACTIVE_TASK_TRANSIENT ])); // phpcs:ignore csrf ok.

        $task = self::get_task($task_id);

        if (! $task) {
            return;
        }

        $task->set_active();
    }

    /**
     * Add a task list.
     *
     * @param array $args Task list properties.
     */
    public static function add_list(array $args): \WP_Error|\Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList
    {
        if (isset(self::$lists[ $args['id'] ])) {
            return new \WP_Error(
                'woocommerce_task_list_exists',
                __('Task list ID already exists', 'woocommerce')
            );
        }

        self::$lists[ $args['id'] ] = new TaskList($args);
        return self::$lists[ $args['id'] ];
    }

    /**
     * Add task to a given task list.
     *
     * @param string $list_id List ID to add the task to.
     * @param Task   $task Task object.
     *
     * @return \WP_Error|Task
     */
    public static function add_task($list_id, $task)
    {
        if (! isset(self::$lists[ $list_id ])) {
            return new \WP_Error(
                'woocommerce_task_list_invalid_list',
                __('Task list ID does not exist', 'woocommerce')
            );
        }

        self::$lists[ $list_id ]->add_task($task);
    }

    /**
     * Add default extended task lists.
     *
     * @param array $extended_tasks list of extended tasks.
     */
    public static function maybe_add_extended_tasks($extended_tasks): void
    {
        $tasks = $extended_tasks ?? [];

        foreach (self::$lists as $task_list) {
            if (!str_starts_with((string) $task_list->id, 'extended')) {
                continue;
            }
            foreach ($tasks as $args) {
                $task = new DeprecatedExtendedTask($task_list, $args);
                $task_list->add_task($task);
            }
        }
    }

    /**
     * Get all task lists.
     *
     * @return array
     */
    public static function get_lists()
    {
        return self::$lists;
    }

    /**
     * Get all task lists.
     *
     * @param array $ids list of task list ids.
     */
    public static function get_lists_by_ids($ids): array
    {
        return array_filter(
            self::$lists,
            fn ($task_list) => in_array($task_list->get_list_id(), $ids, true)
        );
    }

    /**
     * Get all task list ids.
     */
    public static function get_list_ids(): array
    {
        return array_keys(self::$lists);
    }

    /**
     * Clear all task lists.
     */
    public static function clear_lists()
    {
        self::$lists = [];
        return self::$lists;
    }

    /**
     * Get visible task lists.
     */
    public static function get_visible(): array
    {
        return array_filter(
            self::get_lists(),
            fn ($task_list) => $task_list->is_visible()
        );
    }

    /**
     * Retrieve a task list by ID.
     *
     * @param String $id Task list ID.
     *
     * @return TaskList|null
     */
    public static function get_list($id)
    {
        return self::$lists[ $id ] ?? null;
    }

    /**
     * Retrieve single task.
     *
     * @param String $id Task ID.
     * @param String $task_list_id Task list ID.
     *
     * @return Object
     */
    public static function get_task($id, $task_list_id = null)
    {
        $task_list = $task_list_id ? self::get_list($task_list_id) : null;

        if ($task_list_id && ! $task_list) {
            return null;
        }

        $tasks_to_search = $task_list ? $task_list->tasks : array_reduce(
            self::get_lists(),
            fn (array $all, $curr) => array_merge($all, $curr->tasks),
            []
        );

        foreach ($tasks_to_search as $task) {
            if ($id === $task->get_id()) {
                return $task;
            }
        }

        return null;
    }

    /**
     * Return number of setup tasks remaining
     *
     * This is not updated immediately when a task is completed, but rather when task is marked as complete in the database to reduce performance impact.
     *
     * @return int|null
     */
    public static function setup_tasks_remaining()
    {
        $setup_list = self::get_list('setup');

        if (! $setup_list || $setup_list->is_hidden() || $setup_list->has_previously_completed()) {
            return;
        }

        $viewable_tasks  = $setup_list->get_viewable_tasks();
        $completed_tasks = get_option(Task::COMPLETED_OPTION, []);
        if (! is_array($completed_tasks)) {
            $completed_tasks = [];
        }

        return count(
            array_filter(
                $viewable_tasks,
                fn ($task) => ! in_array($task->get_id(), $completed_tasks, true)
            )
        );
    }

    /**
     * Add badge to homescreen menu item for remaining tasks
     */
    public static function menu_task_count(): void
    {
        global $submenu;

        $tasks_count = self::setup_tasks_remaining();

        if (! $tasks_count || ! isset($submenu['woocommerce'])) {
            return;
        }

        foreach ($submenu['woocommerce'] as $key => $menu_item) {
            if (str_starts_with((string) $menu_item[0], _x('Home', 'Admin menu name', 'woocommerce'))) {
                $submenu['woocommerce'][ $key ][0] .= ' <span class="menu-counter remaining-tasks-badge woocommerce-task-list-remaining-tasks-badge"><span class="count-' . esc_attr($tasks_count) . '">' . absint($tasks_count) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                break;
            }
        }
    }

    /**
     * Add visible list ids to component settings.
     *
     * @param array $settings Component settings.
     */
    public static function task_list_preloaded_settings(array $settings): array
    {
        $settings['visibleTaskListIds']   = self::all_hidden() ? [] : array_keys(self::get_visible());
        $settings['completedTaskListIds'] = get_option(TaskList::COMPLETED_OPTION, []);

        return $settings;
    }

    /**
     * Check if all task lists are hidden.
     */
    public static function all_hidden(): bool
    {
        $hidden_lists = get_option(TaskList::HIDDEN_OPTION, []);
        return count($hidden_lists) === count(self::get_lists());
    }
}
