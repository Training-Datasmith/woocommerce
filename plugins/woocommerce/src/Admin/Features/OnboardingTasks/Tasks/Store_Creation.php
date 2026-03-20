<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Tasks;

use Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Task;
/**
 * Store Details Task
 */
class Store_Creation extends Task
{
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'store_creation';
    }
    /**
     * Title.
     */
    public function get_title(): string
    {
        /* translators: Store name */
        return sprintf(__('You created %s', 'woocommerce'), get_bloginfo('name'));
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
     * Time.
     */
    public function get_action_url(): string
    {
        return '';
    }
    /**
     * Task completion.
     */
    public function is_complete(): bool
    {
        return true;
    }
    /**
     * Check if task is disabled.
     */
    public function is_disabled(): bool
    {
        return true;
    }
}