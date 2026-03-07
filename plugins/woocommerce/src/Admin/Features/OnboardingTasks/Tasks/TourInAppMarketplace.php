<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Tour In-App Marketplace task
 */
class TourInAppMarketplace extends Task
{
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'tour-in-app-marketplace';
    }

    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __(
            'Discover ways of extending your store with a tour of the Woo Marketplace',
            'woocommerce'
        );
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
        return get_option('woocommerce_admin_dismissed_in_app_marketplace_tour') === 'yes';
    }

    /**
     * Action URL.
     *
     * @return string
     */
    public function get_action_url()
    {
        return admin_url('admin.php?page=wc-admin&path=%2Fextensions&tutorial=true');
    }

    /**
     * Check if should record event when task is viewed
     */
    public function get_record_view_event(): bool
    {
        return true;
    }
}
