<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Launch Your Store Task
 */
class LaunchYourStore extends Task
{
    /**
     * Constructor
     *
     * @param TaskList $task_list Parent task list.
     */
    public function __construct($task_list)
    {
        parent::__construct($task_list);

        add_action('show_admin_bar', $this->possibly_hide_wp_admin_bar(...));
    }

    /**
     * ID.
     */
    public function get_id(): string
    {
        return 'launch-your-store';
    }

    /**
     * Title.
     *
     * @return string
     */
    public function get_title()
    {
        return __('Launch your store', 'woocommerce');
    }

    /**
     * Content.
     *
     * @return string
     */
    public function get_content()
    {
        return __(
            "It's time to celebrate – you're ready to launch your store! Woo! Hit the button to preview your store and make it public.",
            'woocommerce'
        );
    }

    /**
     * Time.
     */
    public function get_time(): string
    {
        return '';
    }

    /**
     * Action URL.
     *
     * @return string
     */
    public function get_action_url()
    {
        return admin_url('admin.php?page=wc-admin&path=%2Flaunch-your-store');
    }

    /**
     * Task completion.
     */
    public function is_complete(): bool
    {
        return 'yes' !== get_option('woocommerce_coming_soon');
    }

    /**
     * Task visibility.
     */
    public function can_view(): bool
    {
        return Features::is_enabled('launch-your-store');
    }

    /**
     * Hide the WP admin bar when the user is previewing the site.
     *
     * @param bool $show Whether to show the admin bar.
     */
    public function possibly_hide_wp_admin_bar($show)
    {
        if (isset($_GET['site-preview'])) { // @phpcs:ignore
            return false;
        }

        global $wp;
        $http_referer = wp_get_referer() ?? '';
        $parsed_url   = wp_parse_url($http_referer, PHP_URL_QUERY);
        $query_string = is_string($parsed_url) ? $parsed_url : '';

        // Check if the user is coming from the site preview link.
        if (str_contains($query_string, 'site-preview')) {
            if (! isset($_SERVER['REQUEST_URI'])) {
                return $show;
            }

            // Redirect to the current URL with the site-preview query string.
            $current_url =
                add_query_arg(
                    [
                        'site-preview' => 1,
                    ],
                    esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))
                );
            wp_safe_redirect($current_url);
            exit;
        }

        return $show;
    }
}
