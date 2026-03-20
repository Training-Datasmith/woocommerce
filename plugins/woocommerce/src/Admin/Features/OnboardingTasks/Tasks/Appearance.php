<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Tasks;

use Automattic\Woo_Commerce\Admin\Features\Onboarding_Tasks\Task;
/**
 * Appearance Task
 */
class Appearance extends Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!$this->is_complete()) {
            add_action('load-theme-install.php', $this->mark_actioned(...));
        }
    }
    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'appearance';
    }
    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Choose your theme', 'woocommerce');
    }
    /**
     * Content.
     *
     * @return string
     */
    public function get_content()
    {
        return __("Choose a theme that best fits your brand's look and feel, then make it your own. Change the colors, add your logo, and create pages.", 'woocommerce');
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
     * Action label.
     *
     * @return string
     */
    public function get_action_label()
    {
        return __('Choose theme', 'woocommerce');
    }
}