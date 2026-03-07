<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * ExtendStore Task
 */
class ExtendStore extends Task
{
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'extend-store';
    }

    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Enhance your store with extensions', 'woocommerce');
    }

    /**
     * Content.
     */
    public function get_content(): string
    {
        return '';
    }

    /**
     * Additional info.
     */
    public function get_additional_info(): string
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
     *
     * @return bool
     */
    public function is_complete()
    {
        return $this->is_visited();
    }

    /**
     * Always dismissable.
     */
    public function is_dismissable(): bool
    {
        return false;
    }

    /**
     * Action URL.
     *
     * @return string
     */
    public function get_action_url()
    {
        return admin_url('admin.php?page=wc-admin&path=/extensions');
    }
}
