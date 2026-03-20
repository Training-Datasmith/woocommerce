<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Tasks;

use Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Task;
/**
 * Review Shipping Options Task
 */
class Review_Shipping_Options extends Task
{
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'review-shipping';
    }
    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Review shipping options', 'woocommerce');
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
        return get_option('woocommerce_admin_reviewed_default_shipping_zones') === 'yes';
    }
    /**
     * Task visibility.
     */
    public function can_view(): bool
    {
        return get_option('woocommerce_admin_created_default_shipping_zones') === 'yes';
    }
    /**
     * Action URL.
     *
     * @return string
     */
    public function get_action_url()
    {
        return admin_url('admin.php?page=wc-settings&tab=shipping');
    }
}