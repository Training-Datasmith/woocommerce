<?php

declare(strict_types=1);
/**
 * Handles storage and retrieval of a task list
 */

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks;

use Automattic\WooCommerce\Admin\WCAdminHelper;

/**
 * Task List class.
 */
class TaskList
{
    /**
     * Task traits.
     */
    use TaskTraits;

    /**
     * Option name hidden task lists.
     */
    public const HIDDEN_OPTION = 'woocommerce_task_list_hidden_lists';

    /**
     * Option name of completed task lists.
     */
    public const COMPLETED_OPTION = 'woocommerce_task_list_completed_lists';

    /**
     * Option name of hidden reminder bar.
     */
    public const REMINDER_BAR_HIDDEN_OPTION = 'woocommerce_task_list_reminder_bar_hidden';

    /**
     * ID.
     *
     * @var string
     */
    public $id = '';

    /**
     * ID.
     *
     * @var string
     */
    public $hidden_id = '';

    /**
     * ID.
     *
     * @var boolean
     */
    public $display_progress_header = false;

    /**
     * Title.
     *
     * @var string
     */
    public $title = '';

    /**
     * Tasks.
     *
     * @var array
     */
    public $tasks = [];

    /**
     * Sort keys.
     *
     * @var array
     */
    public $sort_by = [];

    /**
     * Event prefix.
     *
     * @var string|null
     */
    public $event_prefix;

    /**
     * Task list visibility.
     *
     * @var boolean
     */
    public $visible = true;

    /**
     * Array of custom options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Array of TaskListSection.
     *
     * @deprecated 7.2.0
     */
    private array $sections = [];

    /**
     * Key value map of task class and id used for sections.
     *
     * @deprecated 7.2.0
     *
     * @var array
     */
    public $task_class_id_map = [];

    /**
     * Constructor
     *
     * @param array $data Task list data.
     */
    public function __construct($data = [])
    {
        $defaults = [
            'id'                      => null,
            'hidden_id'               => null,
            'title'                   => '',
            'tasks'                   => [],
            'sort_by'                 => [],
            'event_prefix'            => null,
            'options'                 => [],
            'visible'                 => true,
            'display_progress_header' => false,
        ];

        $data = wp_parse_args($data, $defaults);

        $this->id                      = $data['id'];
        $this->hidden_id               = $data['hidden_id'];
        $this->title                   = $data['title'];
        $this->sort_by                 = $data['sort_by'];
        $this->event_prefix            = $data['event_prefix'];
        $this->options                 = $data['options'];
        $this->visible                 = $data['visible'];
        $this->display_progress_header = $data['display_progress_header'];

        foreach ($data['tasks'] as $task_name) {
            $class = 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\\' . $task_name;
            $task  = new $class($this);
            $this->add_task($task);
        }

        static::possibly_remove_reminder_bar();
    }

    /**
     * Check if the task list is hidden.
     */
    public function is_hidden(): bool
    {
        $hidden = get_option(self::HIDDEN_OPTION, []);
        return in_array($this->hidden_id ?: $this->id, $hidden, true);
    }

    /**
     * Check if the task list is visible.
     */
    public function is_visible(): bool
    {
        // If the task list is explicitly set to not be visible, return false.
        if (! $this->visible) {
            return false;
        }

        // If the task list is hidden, return false.
        if ($this->is_hidden()) {
            return false;
        }

        // If the task list has no viewable tasks, return false.
        $no_viewable_tasks = count($this->get_viewable_tasks()) === 0;
        if ($no_viewable_tasks) {
            return false;
        }

        return true;
    }

    /**
     * Hide the task list.
     *
     * @return bool
     */
    public function hide()
    {
        if ($this->is_hidden()) {
            return;
        }

        $viewable_tasks  = $this->get_viewable_tasks();
        $completed_count = array_reduce(
            $viewable_tasks,
            fn ($total, $task) => $task->is_complete() ? $total + 1 : $total,
            0
        );

        $this->record_tracks_event(
            'completed',
            [
                'action'                => 'remove_card',
                'completed_task_count'  => $completed_count,
                'incomplete_task_count' => count($viewable_tasks) - $completed_count,
                'tasklist_id'           => $this->id,
            ]
        );

        $hidden   = get_option(self::HIDDEN_OPTION, []);
        $hidden[] = $this->hidden_id ?: $this->id;
        $this->maybe_set_default_layout($hidden);
        return update_option(self::HIDDEN_OPTION, array_unique($hidden));
    }

    /**
     * Sets the default homepage layout to two_columns if "setup" tasklist is completed or hidden.
     *
     * @param array $completed_or_hidden_tasklist_ids Array of tasklist ids.
     */
    public function maybe_set_default_layout($completed_or_hidden_tasklist_ids): void
    {
        if (in_array('setup', $completed_or_hidden_tasklist_ids, true)) {
            update_option('woocommerce_default_homepage_layout', 'two_columns');
        }
    }

    /**
     * Undo hiding of the task list.
     *
     * @return bool
     */
    public function unhide()
    {
        $hidden = get_option(self::HIDDEN_OPTION, []);
        $hidden = array_diff($hidden, [ $this->hidden_id ?: $this->id ]);
        return update_option(self::HIDDEN_OPTION, $hidden);
    }

    /**
     * Check if all viewable tasks are complete.
     */
    public function is_complete(): bool
    {
        foreach ($this->get_viewable_tasks() as $viewable_task) {
            if ($viewable_task->is_complete() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a task list has previously been marked as complete.
     */
    public function has_previously_completed(): bool
    {
        $complete = get_option(self::COMPLETED_OPTION, []);
        return in_array($this->get_list_id(), $complete, true);
    }

    /**
     * Add task to the task list.
     *
     * @param Task $task Task class.
     */
    public function add_task($task)
    {
        if (! is_subclass_of($task, \Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task::class)) {
            return new \WP_Error(
                'woocommerce_task_list_invalid_task',
                __('Task is not a subclass of `Task`', 'woocommerce')
            );
        }
        if (array_search($task, $this->tasks, true)) {
            return;
        }

        $this->tasks[] = $task;
    }

    /**
     * Get only visible tasks in list.
     *
     * @param string $task_id id of task.
     * @return Task
     */
    public function get_task($task_id): mixed
    {
        return current(
            array_filter(
                $this->tasks,
                fn ($task) => $task->get_id() === $task_id
            )
        );
    }

    /**
     * Get only visible tasks in list.
     */
    public function get_viewable_tasks(): array
    {
        return array_values(
            array_filter(
                $this->tasks,
                fn ($task) => $task->can_view()
            )
        );
    }

    /**
     * Get task list sections.
     *
     * @deprecated 7.2.0
     *
     * @return array
     */
    public function get_sections()
    {
        wc_deprecated_function(self::class . '::' . __FUNCTION__, '7.2.0');

        return $this->sections;
    }

    /**
     * Track list completion of viewable tasks.
     */
    public function possibly_track_completion(): void
    {
        if ($this->has_previously_completed()) {
            return;
        }

        // If it's hidden, completion is tracked via hide method.
        if ($this->is_hidden()) {
            return;
        }

        // Expensive check, do it last.
        if (! $this->is_complete()) {
            return;
        }

        $completed_lists   = get_option(self::COMPLETED_OPTION, []);
        $completed_lists[] = $this->get_list_id();
        update_option(self::COMPLETED_OPTION, $completed_lists, true);
        $this->maybe_set_default_layout($completed_lists);
        $this->record_tracks_event(
            'tasks_completed',
            [
                'tasklist_id' => $this->id,
            ]
        );
    }

    /**
     * Sorts the attached tasks array.
     *
     * @param array $sort_by list of columns with sort order.
     * @return TaskList returns $this, for chaining.
     */
    public function sort_tasks($sort_by = []): static
    {
        $sort_by = count($sort_by) > 0 ? $sort_by : $this->sort_by;
        if (0 !== count($sort_by)) {
            usort(
                $this->tasks,
                fn ($a, $b) => Task::sort($a, $b, $sort_by)
            );
        }
        return $this;
    }

    /**
     * Prefix event for track event naming.
     *
     * @param string $event_name Event name.
     */
    public function prefix_event(string $event_name): string
    {
        if (null !== $this->event_prefix) {
            return $this->event_prefix . $event_name;
        }
        return $this->get_list_id() . '_tasklist_' . $event_name;
    }

    /**
     * Returns option to keep completed task list.
     *
     * @return string
     */
    public function get_keep_completed_task_list()
    {
        return get_option('woocommerce_task_list_keep_completed', 'no');
    }

    /**
     * Remove reminder bar four weeks after store creation.
     */
    public static function possibly_remove_reminder_bar(): void
    {
        $bar_hidden            = get_option(self::REMINDER_BAR_HIDDEN_OPTION, 'no');
        $active_for_four_weeks = WCAdminHelper::is_wc_admin_active_for(WEEK_IN_SECONDS * 4);

        if ('yes' === $bar_hidden || ! $active_for_four_weeks) {
            return;
        }

        update_option(self::REMINDER_BAR_HIDDEN_OPTION, 'yes');
    }

    /**
     * Get the list for use in JSON.
     */
    public function get_json(): array
    {
        $this->possibly_track_completion();
        $tasks_json = [];

        foreach ($this->tasks as $task) {
            // We have no use for hidden lists, it's expensive to compute individual tasks completion.
            // Exception: Secret tasklist is always hidden, or a task is always accessible.
            $list_is_visible = $this->is_visible() || 'secret_tasklist' === $this->id;
            if ($list_is_visible || (method_exists($task, 'is_always_accessible') && $task->is_always_accessible())) {
                $json = $task->get_json();
                if ($json['canView']) {
                    $tasks_json[] = $json;
                }
            }
        }

        return [
            'id'                    => $this->get_list_id(),
            'title'                 => $this->title,
            'isHidden'              => $this->is_hidden(),
            'isVisible'             => $this->is_visible(),
            'isComplete'            => $this->is_complete(),
            'tasks'                 => $tasks_json,
            'eventPrefix'           => $this->prefix_event(''),
            'displayProgressHeader' => $this->display_progress_header,
            'keepCompletedTaskList' => $this->get_keep_completed_task_list(),
        ];
    }
}
