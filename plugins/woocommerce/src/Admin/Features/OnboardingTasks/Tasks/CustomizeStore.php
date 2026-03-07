<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use WP_Post;

/**
 * Customize Your Store Task
 *
 * @internal
 */
class CustomizeStore extends Task
{
    /**
     * Constructor
     *
     * @param TaskList $task_list Parent task list.
     */
    public function __construct($task_list)
    {
        parent::__construct($task_list);

        add_action('save_post_wp_global_styles', $this->mark_task_as_complete_block_theme(...), 10, 3);
        add_action('save_post_wp_template', $this->mark_task_as_complete_block_theme(...), 10, 3);
        add_action('save_post_wp_template_part', $this->mark_task_as_complete_block_theme(...), 10, 3);
        add_action('customize_save_after', $this->mark_task_as_complete_classic_theme(...));
    }

    /**
     * Mark the CYS task as complete whenever the user updates their global styles.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post Post object.
     * @param bool    $update Whether this is an existing post being updated.
     */
    public function mark_task_as_complete_block_theme($post_id, $post, $update): void
    {
        if ($post instanceof WP_Post) {
            $is_cys_complete = $this->has_custom_global_styles($post) || $this->has_custom_template($post);

            if ($is_cys_complete) {
                update_option('woocommerce_admin_customize_store_completed', 'yes');
            }
        }
    }

    /**
     * Mark the CYS task as complete whenever the user saves the customizer changes.
     */
    public function mark_task_as_complete_classic_theme(): void
    {
        update_option('woocommerce_admin_customize_store_completed', 'yes');
    }

    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'customize-store';
    }

    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Customize your store ', 'woocommerce');
    }

    /**
     * Content.
     */
    public function get_content(): string
    {
        return '';
    }

    /**
     * Time.
     */
    public function get_time(): string
    {
        return '';
    }

    /**
     * Task completion.
     */
    public function is_complete(): bool
    {
        return get_option('woocommerce_admin_customize_store_completed') === 'yes';
    }

    /**
     * Task visibility.
     */
    public function can_view(): bool
    {
        return true;
    }

    /**
     * Action URL.
     *
     * @return string
     */
    public function get_action_url()
    {
        return admin_url('admin.php?page=wc-admin&path=%2Fcustomize-store');
    }

    /**
     * Checks if the post has custom global styles stored (if it is different from the default global styles).
     *
     * @param WP_Post $post The post object.
     * @return bool
     */
    private function has_custom_global_styles(WP_Post $post)
    {
        $required_keys = [ 'version', 'isGlobalStylesUserThemeJSON' ];

        $json_post_content = json_decode($post->post_content, true);
        if (is_null($json_post_content)) {
            return false;
        }

        $post_content_keys = array_keys($json_post_content);

        return ! empty(array_diff($post_content_keys, $required_keys)) || ! empty(array_diff($required_keys, $post_content_keys));
    }

    /**
     * Checks if the post is a template or a template part.
     *
     * @param WP_Post $post The post object.
     * @return bool Whether the post is a template or a template part.
     */
    private function has_custom_template(WP_Post $post): bool
    {
        return in_array($post->post_type, [ 'wp_template', 'wp_template_part' ], true);
    }
}
