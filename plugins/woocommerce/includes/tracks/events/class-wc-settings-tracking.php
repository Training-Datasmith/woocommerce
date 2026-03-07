<?php

declare(strict_types=1);
/**
 * WooCommerce Settings Tracking
 *
 * @package WooCommerce\Tracks
 */

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

defined('ABSPATH') || exit;

/**
 * This class adds actions to track usage of WooCommerce Settings.
 */
class WC_Settings_Tracking
{
    /**
     * List of allowed WooCommerce settings to potentially track updates for.
     *
     * @var array
     */
    protected $allowed_options = [];

    /**
     * WooCommerce settings that have been updated (and will be tracked).
     *
     * @var array
     */
    protected $updated_options = [];

    /**
     * List of option names that are dropdown menus.
     *
     * @var array
     */
    protected $dropdown_menu_options = [];

    /**
     * List of options that have been modified.
     *
     * @var array
     */
    protected $modified_options = [];

    /**
     * List of options that have been deleted.
     *
     * @var array
     */
    protected $deleted_options = [];

    /**
     * List of options that have been added.
     *
     * @var array
     */
    protected $added_options = [];

    /**
     * Toggled options.
     *
     * @var array
     */
    protected $toggled_options = [
        'enabled'  => [],
        'disabled' => [],
    ];

    /**
     * Init tracking.
     */
    public function init(): void
    {
        add_action('woocommerce_settings_page_init', $this->track_settings_page_view(...));
        add_action('woocommerce_update_option', $this->add_option_to_list(...));
        add_action('woocommerce_update_non_option_setting', $this->add_option_to_list_and_track_setting_change(...));
        add_action('woocommerce_update_options', $this->send_settings_change_event(...));
        add_action('admin_enqueue_scripts', $this->possibly_add_settings_tracking_scripts(...));
    }

    /**
     * Adds the option to the allowed and updated options directly.
     * Currently used for settings that don't use update_option.
     *
     * @param array $option WooCommerce option that should be updated.
     */
    public function add_option_to_list_and_track_setting_change(array $option): void
    {
        if (! in_array($option['id'], $this->allowed_options, true)) {
            $this->allowed_options[] = $option['id'];
        }
        if (isset($option['action'])) {
            if ('add' === $option['action']) {
                $this->added_options[] = $option['id'];
            } elseif ('delete' === $option['action']) {
                $this->deleted_options[] = $option['id'];
            } elseif (! in_array($option['id'], $this->updated_options, true)) {
                $this->updated_options[] = $option['id'];
            }
        } elseif (isset($option['value'])) {
            if ('select' === $option['type']) {
                $this->modified_options[ $option['id'] ] = $option['value'];

            } elseif ('checkbox' === $option['type']) {
                $option_state                             = 'yes' === $option['value'] ? 'enabled' : 'disabled';
                $this->toggled_options[ $option_state ][] = $option['id'];
            }
            $this->updated_options[] = $option['id'];
        }

    }

    /**
     * Add a WooCommerce option name to our allowed options list and attach
     * the `update_option` hook. Rather than inspecting every updated
     * option and pattern matching for "woocommerce", just build a dynamic
     * list for WooCommerce options that might get updated.
     *
     * See `woocommerce_update_option` hook.
     *
     * @param array $option WooCommerce option (config) that might get updated.
     */
    public function add_option_to_list(array $option): void
    {
        $this->allowed_options[] = $option['id'];

        if (isset($option['options'])) {
            $this->dropdown_menu_options[] = $option['id'];
        }

        // Delay attaching this action since it could get fired a lot.
        if (false === has_action('update_option', $this->track_setting_change(...))) {
            add_action('update_option', $this->track_setting_change(...), 10, 3);
        }
    }

    /**
     * Add WooCommerce option to a list of updated options.
     *
     * @param string $option_name Option being updated.
     * @param mixed  $old_value Old value of option.
     * @param mixed  $new_value New value of option.
     */
    public function track_setting_change($option_name, $old_value, $new_value): void
    {
        // Make sure this is a WooCommerce option.
        if (! in_array($option_name, $this->allowed_options, true)) {
            return;
        }

        // Check to make sure the new value is truly different.
        // `woocommerce_price_num_decimals` tends to trigger this
        // because form values aren't coerced (e.g. '2' vs. 2).
        if (
            is_scalar($old_value) &&
            is_scalar($new_value) &&
            (string) $old_value === (string) $new_value
        ) {
            return;
        }

        if (in_array($option_name, $this->dropdown_menu_options, true)) {
            $this->modified_options[ $option_name ] = $new_value;
        } elseif (in_array($new_value, [ 'yes', 'no' ], true) && in_array($old_value, [ 'yes', 'no' ], true)) {
            // Save toggled options.
            $option_state                             = 'yes' === $new_value ? 'enabled' : 'disabled';
            $this->toggled_options[ $option_state ][] = $option_name;
        }

        $this->updated_options[] = $option_name;
    }

    /**
     * Send a Tracks event for WooCommerce options that changed values.
     */
    public function send_settings_change_event(): void
    {
        global $current_tab, $current_section;

        if (empty($this->updated_options) && empty($this->deleted_options) && empty($this->added_options)) {
            return;
        }

        $properties = [];

        if (! empty($this->updated_options)) {
            $properties['settings'] = implode(',', $this->updated_options);
        }

        if (! empty($this->deleted_options)) {
            $properties['deleted'] = implode(',', $this->deleted_options);
        }

        if (! empty($this->added_options)) {
            $properties['added'] = implode(',', $this->added_options);
        }

        foreach ($this->toggled_options as $state => $options) {
            if (! empty($options)) {
                $properties[ $state ] = implode(',', $options);
            }
        }

        foreach ($this->modified_options as $option_name => $selected_option) {
            $properties[ $option_name ] = $selected_option ?? '';
        }

        $properties['tab']     = $current_tab ?? '';
        $properties['section'] = $current_section ?? '';

        WC_Tracks::record_event('settings_change', $properties);
    }

    /**
     * Send a Tracks event for WooCommerce settings page views.
     */
    public function track_settings_page_view(): void
    {
        global $current_tab, $current_section;

        $properties = [
            'tab'     => $current_tab,
            'section' => empty($current_section) ? null : $current_section,
        ];

        WC_Tracks::record_event('settings_view', $properties);
    }

    /**
     * Adds the tracking scripts for product setting pages.
     *
     * @param string $hook Page hook.
     */
    public function possibly_add_settings_tracking_scripts($hook): void
    {
        if (! is_wc_admin_settings_page()) {
            return;
        }

        WCAdminAssets::register_script('wp-admin-scripts', 'settings-tracking', false);
    }
}
