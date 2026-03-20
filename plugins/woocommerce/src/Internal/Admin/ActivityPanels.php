<?php

declare (strict_types=1);
/**
 * WooCommerce Activity Panel.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use Automattic\Woo_Commerce\Admin\Notes\Notes;
/**
 * Contains backend logic for the activity panel feature.
 */
class Activity_Panels
{
    /**
     * Class instance.
     *
     * @var ActivityPanels instance
     */
    protected static $instance;
    /**
     * Get class instance.
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Hook into WooCommerce.
     */
    public function __construct()
    {
        add_filter('woocommerce_admin_get_user_data_fields', $this->add_user_data_fields(...));
        // Run after Automattic\WooCommerce\Internal\Admin\Loader.
        add_filter('woocommerce_components_settings', $this->component_settings(...), 20);
        // New settings injection.
        add_filter('woocommerce_admin_shared_settings', $this->component_settings(...), 20);
    }
    /**
     * Adds fields so that we can store activity panel last read and open times.
     *
     * @param array $user_data_fields User data fields.
     */
    public function add_user_data_fields($user_data_fields): array
    {
        return array_merge($user_data_fields, ['activity_panel_inbox_last_read', 'activity_panel_reviews_last_read']);
    }
    /**
     * Add alert count to the component settings.
     *
     * @param array $settings Component settings.
     */
    public function component_settings(array $settings): array
    {
        $settings['alertCount'] = Notes::get_notes_count(['error', 'update'], ['unactioned']);
        return $settings;
    }
}