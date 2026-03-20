<?php

declare (strict_types=1);
/**
 * Features loader for features developed in WooCommerce Admin.
 */
namespace Automattic\Woo_Commerce\Admin\Features;

use Automattic\Woo_Commerce\Admin\Page_Controller;
use Automattic\Woo_Commerce\Internal\Admin\Loader;
use Automattic\Woo_Commerce\Internal\Admin\Wc_Admin_Assets;
use Automattic\Woo_Commerce\Utilities\Features_Util;
/**
 * Features Class.
 */
class Features
{
    /**
     * Class instance.
     *
     * @var Loader instance
     */
    protected static $instance;
    /**
     * Optional features
     *
     * @var array
     */
    protected static $optional_features = ['analytics' => ['default' => 'yes'], 'remote-inbox-notifications' => ['default' => 'yes']];
    /**
     * Beta features
     *
     * @var array
     */
    protected static $beta_features = ['settings'];
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
     * Constructor.
     */
    public function __construct()
    {
        $this->register_internal_class_aliases();
        if (!self::should_load_features()) {
            return;
        }
        // Load feature before WooCommerce update hooks.
        add_action('init', self::load_features(...), 4);
        add_action('admin_enqueue_scripts', self::maybe_load_beta_features_modal(...));
        add_action('admin_enqueue_scripts', self::load_scripts(...), 15);
        add_filter('admin_body_class', self::add_admin_body_classes(...));
        add_filter('update_option_woocommerce_allow_tracking', self::maybe_disable_features(...), 10, 2);
    }
    /**
     * Gets a build configured array of enabled WooCommerce Admin features/sections, but does not respect optionally disabled features.
     *
     * @return array Enabled Woocommerce Admin features/sections.
     */
    public static function get_features()
    {
        return apply_filters('woocommerce_admin_features', []);
    }
    /**
     * Gets the optional feature options as an associative array that can be toggled on or off.
     */
    public static function get_optional_feature_options(): array
    {
        $features = [];
        foreach (array_keys(self::$optional_features) as $optional_feature_key) {
            $feature_class = self::get_feature_class($optional_feature_key);
            if ($feature_class) {
                $features[$optional_feature_key] = $feature_class::TOGGLE_OPTION_NAME;
            }
        }
        return $features;
    }
    /**
     * Returns if a specific wc-admin feature exists in the current environment.
     *
     * @param  string $feature Feature slug.
     * @return bool Returns true if the feature exists.
     */
    public static function exists($feature): bool
    {
        $features = self::get_features();
        return in_array($feature, $features, true);
    }
    /**
     * Get the feature class as a string.
     *
     * @param string $feature Feature name.
     */
    public static function get_feature_class($feature): ?string
    {
        $feature = str_replace('-', '', ucwords(strtolower($feature), '-'));
        $feature_class = 'Automattic\WooCommerce\Admin\Features\\' . $feature;
        $should_autoload_class = self::should_load_features();
        if (class_exists($feature_class, $should_autoload_class)) {
            return $feature_class;
        }
        // Handle features contained in subdirectory.
        if (class_exists($feature_class . '\Init', $should_autoload_class)) {
            return $feature_class . '\Init';
        }
        return null;
    }
    /**
     * Class loader for enabled WooCommerce Admin features/sections.
     */
    public static function load_features(): void
    {
        if (!self::should_load_features()) {
            return;
        }
        $features = self::get_features();
        foreach ($features as $feature) {
            $feature_class = self::get_feature_class($feature);
            if ($feature_class) {
                new $feature_class();
            }
        }
        if (Features_Util::feature_is_enabled('blueprint')) {
            new \Automattic\Woo_Commerce\Admin\Features\Blueprint\Init();
        }
    }
    /**
     * Gets a build configured array of enabled WooCommerce Admin respecting optionally disabled features.
     *
     * @return array Enabled Woocommerce Admin features/sections.
     */
    public static function get_available_features(): array
    {
        $features = self::get_features();
        $optional_feature_keys = array_keys(self::$optional_features);
        $optional_features_unavailable = [];
        /**
         * Filter allowing WooCommerce Admin optional features to be disabled.
         *
         * @param bool $disabled False.
         */
        if (apply_filters('woocommerce_admin_disabled', false)) {
            return array_values(array_diff($features, $optional_feature_keys));
        }
        foreach ($optional_feature_keys as $optional_feature_key) {
            $feature_class = self::get_feature_class($optional_feature_key);
            if ($feature_class) {
                $default = self::$optional_features[$optional_feature_key]['default'] ?? 'no';
                // Check if the feature is currently being enabled, if it is continue.
                /* phpcs:disable WordPress.Security.NonceVerification */
                $feature_option = $feature_class::TOGGLE_OPTION_NAME;
                if (isset($_POST[$feature_option]) && '1' === $_POST[$feature_option]) {
                    continue;
                }
                if ('yes' !== get_option($feature_class::TOGGLE_OPTION_NAME, $default)) {
                    $optional_features_unavailable[] = $optional_feature_key;
                }
            }
        }
        return array_values(array_diff($features, $optional_features_unavailable));
    }
    /**
     * Check if a feature is enabled.
     *
     * @param string $feature Feature slug.
     */
    public static function is_enabled($feature): bool
    {
        $available_features = self::get_available_features();
        return in_array($feature, $available_features, true);
    }
    /**
     * Enable a toggleable optional feature.
     *
     * @param string $feature Feature name.
     */
    public static function enable($feature): bool
    {
        $features = self::get_optional_feature_options();
        if (isset($features[$feature])) {
            update_option($features[$feature], 'yes');
            return true;
        }
        return false;
    }
    /**
     * Disable a toggleable optional feature.
     *
     * @param string $feature Feature name.
     */
    public static function disable($feature): bool
    {
        $features = self::get_optional_feature_options();
        if (isset($features[$feature])) {
            update_option($features[$feature], 'no');
            return true;
        }
        return false;
    }
    /**
     * Disable features when opting out of tracking.
     *
     * @param string $old_value Old value.
     * @param string $value New value.
     */
    public static function maybe_disable_features($old_value, $value): void
    {
        if ('yes' === $value) {
            return;
        }
        foreach (self::$beta_features as $feature) {
            self::disable($feature);
        }
    }
    /**
     * Adds the Features section to the advanced tab of WooCommerce Settings
     *
     * @deprecated 7.0 The WooCommerce Admin features are now handled by the WooCommerce features engine (see the FeaturesController class).
     *
     * @param array $sections Sections.
     * @return array
     */
    public static function add_features_section($sections)
    {
        return $sections;
    }
    /**
     * Adds the Features settings.
     *
     * @deprecated 7.0 The WooCommerce Admin features are now handled by the WooCommerce features engine (see the FeaturesController class).
     *
     * @param array  $settings Settings.
     * @param string $current_section Current section slug.
     * @return array
     */
    public static function add_features_settings($settings, $current_section)
    {
        return $settings;
    }
    /**
     * Conditionally loads the beta features tracking modal.
     *
     * @param string $hook Page hook.
     */
    public static function maybe_load_beta_features_modal($hook): void
    {
        if ('woocommerce_page_wc-settings' !== $hook || !isset($_GET['tab']) || 'advanced' !== $_GET['tab'] || !isset($_GET['section']) || 'features' !== $_GET['section']) {
            return;
        }
        $tracking_enabled = get_option('woocommerce_allow_tracking', 'no');
        if (empty(self::$beta_features)) {
            return;
        }
        if ('yes' === $tracking_enabled) {
            return;
        }
        Wc_Admin_Assets::register_style('beta-features-tracking-modal', 'style', ['wp-components']);
        Wc_Admin_Assets::register_script('wp-admin-scripts', 'beta-features-tracking-modal', ['wp-i18n', 'wp-element', WC_ADMIN_APP]);
    }
    /**
     * Loads the required scripts on the correct pages.
     */
    public static function load_scripts(): void
    {
        if (!Page_Controller::is_admin_or_embed_page()) {
            return;
        }
        $features = self::get_features();
        $enabled_features = [];
        foreach ($features as $key) {
            $enabled_features[$key] = self::is_enabled($key);
        }
        wp_add_inline_script(WC_ADMIN_APP, 'window.wcAdminFeatures = ' . wp_json_encode($enabled_features, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES), 'before');
    }
    /**
     * Adds body classes to the main wp-admin wrapper, allowing us to better target elements in specific scenarios.
     *
     * @param string $admin_body_class Body class to add.
     */
    public static function add_admin_body_classes($admin_body_class = '')
    {
        if (!Page_Controller::is_admin_or_embed_page()) {
            return $admin_body_class;
        }
        $classes = explode(' ', trim($admin_body_class));
        $features = self::get_features();
        foreach ($features as $feature_key) {
            $classes[] = sanitize_html_class('woocommerce-feature-enabled-' . $feature_key);
        }
        $admin_body_class = implode(' ', array_unique($classes));
        return " {$admin_body_class} ";
    }
    /**
     * Alias internal features classes to make them backward compatible.
     * We've moved our feature classes to src-internal as part of merging this
     * repository with WooCommerce Core to form a monorepo.
     * See https://wp.me/p90Yrv-2HY for details.
     */
    private function register_internal_class_aliases(): void
    {
        $aliases = [
            // new class => original class (this will be aliased).
            \Automattic\Woo_Commerce\Internal\Admin\Wc_Pay_Promotion\Init::class => 'Automattic\WooCommerce\Admin\Features\WcPayPromotion\Init',
            \Automattic\Woo_Commerce\Internal\Admin\Remote_Free_Extensions\Init::class => 'Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions\Init',
            \Automattic\Woo_Commerce\Internal\Admin\Activity_Panels::class => 'Automattic\WooCommerce\Admin\Features\ActivityPanels',
            \Automattic\Woo_Commerce\Internal\Admin\Analytics::class => 'Automattic\WooCommerce\Admin\Features\Analytics',
            \Automattic\Woo_Commerce\Internal\Admin\Coupons::class => 'Automattic\WooCommerce\Admin\Features\Coupons',
            \Automattic\Woo_Commerce\Internal\Admin\Coupons_Moved_Trait::class => 'Automattic\WooCommerce\Admin\Features\CouponsMovedTrait',
            \Automattic\Woo_Commerce\Internal\Admin\Customer_Effort_Score_Tracks::class => 'Automattic\WooCommerce\Admin\Features\CustomerEffortScoreTracks',
            \Automattic\Woo_Commerce\Internal\Admin\Homescreen::class => 'Automattic\WooCommerce\Admin\Features\Homescreen',
            \Automattic\Woo_Commerce\Internal\Admin\Marketing::class => 'Automattic\WooCommerce\Admin\Features\Marketing',
            \Automattic\Woo_Commerce\Internal\Admin\Mobile_App_Banner::class => 'Automattic\WooCommerce\Admin\Features\MobileAppBanner',
            \Automattic\Woo_Commerce\Internal\Admin\Remote_Inbox_Notifications::class => 'Automattic\WooCommerce\Admin\Features\RemoteInboxNotifications',
            \Automattic\Woo_Commerce\Internal\Admin\Shipping_Label_Banner::class => 'Automattic\WooCommerce\Admin\Features\ShippingLabelBanner',
            \Automattic\Woo_Commerce\Internal\Admin\Shipping_Label_Banner_Display_Rules::class => 'Automattic\WooCommerce\Admin\Features\ShippingLabelBannerDisplayRules',
            \Automattic\Woo_Commerce\Internal\Admin\Wc_Pay_Welcome_Page::class => 'Automattic\WooCommerce\Admin\Features\WcPayWelcomePage',
        ];
        foreach ($aliases as $new_class => $orig_class) {
            class_alias($new_class, $orig_class);
        }
    }
    /**
     * Check if we're in an admin context where features should be loaded.
     *
     * @return boolean
     */
    private static function should_load_features()
    {
        $should_load = is_admin() || wp_doing_ajax() || wp_doing_cron() || defined('WP_CLI') && WP_CLI || WC()->is_rest_api_request() && !WC()->is_store_api_request() || current_user_can('manage_woocommerce');
        /**
         * Filter to determine if admin features should be loaded.
         *
         * @since 9.6.0
         * @param boolean $should_load Whether admin features should be loaded. It defaults to true when the current request is in an admin context.
         */
        return apply_filters('woocommerce_admin_should_load_features', $should_load);
    }
}