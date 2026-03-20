<?php

declare (strict_types=1);
/**
 * Register the scripts, and styles used within WooCommerce Admin.
 */
namespace Automattic\Woo_Commerce\Internal\Admin;

use _WP_Dependency;
use Automattic\Woo_Commerce\Admin\Features\Features;
use Automattic\Woo_Commerce\Admin\Page_Controller;
use Automattic\Woo_Commerce\Utilities\Features_Util;
/**
 * WCAdminAssets Class.
 */
class Wc_Admin_Assets
{
    /**
     * Class instance.
     *
     * @var WCAdminAssets instance
     */
    protected static $instance;
    /**
     * An array of dependencies that have been preloaded (to avoid duplicates).
     *
     * @var array
     */
    protected $preloaded_dependencies;
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
     * Hooks added here should be removed in `wc_admin_initialize` via the feature plugin.
     */
    public function __construct()
    {
        Features::get_instance();
        add_action('admin_enqueue_scripts', $this->register_scripts(...));
        add_action('admin_enqueue_scripts', $this->inject_wc_settings_dependencies(...), 14);
        add_action('admin_enqueue_scripts', $this->enqueue_assets(...), 15);
    }
    /**
     * Gets the path for the asset depending on file type.
     *
     * @param  string $ext File extension.
     * @return string Folder path of asset.
     */
    public static function get_path($ext): string
    {
        return $ext === 'css' ? WC_ADMIN_DIST_CSS_FOLDER : WC_ADMIN_DIST_JS_FOLDER;
    }
    /**
     * Determines if a minified JS file should be served.
     *
     * @param  boolean $script_debug Only serve unminified files if script debug is on.
     * @return boolean If js asset should use minified version.
     */
    public static function should_use_minified_js_file($script_debug)
    {
        // minified files are only shipped in non-core versions of wc-admin, return false if minified files are not available.
        if (!Features::exists('minified-js')) {
            return false;
        }
        // Otherwise we will serve un-minified files if SCRIPT_DEBUG is on, or if anything truthy is passed in-lieu of SCRIPT_DEBUG.
        return !$script_debug;
    }
    /**
     * Gets the URL to an asset file.
     *
     * @param  string $file File name (without extension).
     * @param  string $ext File extension.
     * @return string URL to asset.
     */
    public static function get_url(string $file, string $ext)
    {
        $suffix = '';
        // Potentially enqueue minified JavaScript.
        if ($ext === 'js') {
            $script_debug = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;
            $suffix = self::should_use_minified_js_file($script_debug) ? '.min' : '';
        }
        return plugins_url(self::get_path($ext) . $file . $suffix . '.' . $ext, WC_ADMIN_PLUGIN_FILE);
    }
    /**
     * Gets the file modified time as a cache buster if we're in dev mode,
     * or the asset version (file content hash) if exists, or the WooCommerce version.
     *
     * @param string      $ext File extension.
     * @param string|null $asset_version Optional. The version from the asset file.
     * @return string The cache buster value to use for the given file.
     */
    public static function get_file_version($ext, $asset_version = null)
    {
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
            return filemtime(WC_ADMIN_ABSPATH . self::get_path($ext));
        }
        if (!empty($asset_version)) {
            return $asset_version;
        }
        return WC_VERSION;
    }
    /**
     * Gets a script asset registry filename. The asset registry lists dependencies for the given script.
     *
     * @param  string $script_path_name Path to where the script asset registry is contained.
     * @param  string $file File name (without extension).
     * @return string complete asset filename.
     *
     * @throws \Exception Throws an exception when a readable asset registry file cannot be found.
     */
    public static function get_script_asset_filename(string $script_path_name, string $file): string
    {
        $minification_supported = Features::exists('minified-js');
        $script_min_filename = $file . '.min.asset.php';
        $script_nonmin_filename = $file . '.asset.php';
        $script_asset_path = WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . $script_path_name . '/';
        // Check minification is supported first, to avoid multiple is_readable checks when minification is
        // not supported.
        if ($minification_supported && is_readable($script_asset_path . $script_min_filename)) {
            return $script_min_filename;
        }
        if (is_readable($script_asset_path . $script_nonmin_filename)) {
            return $script_nonmin_filename;
        }
        // could not find an asset file, throw an error.
        throw new \Exception('Could not find asset registry for ' . $script_path_name);
    }
    /**
     * Render a preload link tag for a dependency, optionally
     * checked against a provided allowlist.
     *
     * See: https://macarthur.me/posts/preloading-javascript-in-wordpress
     *
     * @param WP_Dependency $dependency The WP_Dependency being preloaded.
     * @param string        $type Dependency type - 'script' or 'style'.
     * @param array         $allowlist Optional. List of allowed dependency handles.
     */
    private function maybe_output_preload_link_tag($dependency, string $type, $allowlist = []): void
    {
        if (!empty($allowlist) && !in_array($dependency->handle, $allowlist, true) || !empty($this->preloaded_dependencies[$type]) && in_array($dependency->handle, $this->preloaded_dependencies[$type], true)) {
            return;
        }
        $this->preloaded_dependencies[$type][] = $dependency->handle;
        $source = $dependency->ver ? add_query_arg('ver', $dependency->ver, $dependency->src) : $dependency->src;
        echo '<link rel="preload" href="', esc_url($source), '" as="', esc_attr($type), '" />', "\n";
    }
    /**
     * Output a preload link tag for dependencies (and their sub dependencies)
     * with an optional allowlist.
     *
     * See: https://macarthur.me/posts/preloading-javascript-in-wordpress
     *
     * @param string $type Dependency type - 'script' or 'style'.
     * @param array  $allowlist Optional. List of allowed dependency handles.
     */
    private function output_header_preload_tags_for_type(string $type, array $allowlist = []): void
    {
        if ($type === 'script') {
            $dependencies_of_type = wp_scripts();
        } elseif ($type === 'style') {
            $dependencies_of_type = wp_styles();
        } else {
            return;
        }
        foreach ($dependencies_of_type->queue as $dependency_handle) {
            $dependency = $dependencies_of_type->query($dependency_handle, 'registered');
            if ($dependency === false) {
                continue;
            }
            // Preload the subdependencies first.
            foreach ($dependency->deps as $sub_dependency_handle) {
                $sub_dependency = $dependencies_of_type->query($sub_dependency_handle, 'registered');
                if ($sub_dependency) {
                    $this->maybe_output_preload_link_tag($sub_dependency, $type, $allowlist);
                }
            }
            $this->maybe_output_preload_link_tag($dependency, $type, $allowlist);
        }
    }
    /**
     * Output preload link tags for all enqueued stylesheets and scripts.
     *
     * See: https://macarthur.me/posts/preloading-javascript-in-wordpress
     */
    private function output_header_preload_tags(): void
    {
        $wc_admin_scripts = [WC_ADMIN_APP, 'wc-components'];
        $wc_admin_styles = [WC_ADMIN_APP, 'wc-components', 'wc-material-icons'];
        // Preload styles.
        $this->output_header_preload_tags_for_type('style', $wc_admin_styles);
        // Preload scripts.
        $this->output_header_preload_tags_for_type('script', $wc_admin_scripts);
    }
    /**
     * Loads the required scripts on the correct pages.
     */
    public function enqueue_assets(): void
    {
        if (!Page_Controller::is_admin_or_embed_page()) {
            return;
        }
        if (!Page_Controller::is_modern_settings_page()) {
            wp_enqueue_script(WC_ADMIN_APP);
            wp_enqueue_style(WC_ADMIN_APP);
        }
        wp_enqueue_style('wc-material-icons');
        wp_enqueue_style('wc-onboarding');
        if (Page_Controller::is_settings_page()) {
            static::register_script('wp-admin-scripts', 'settings-embed', true);
            static::register_style('settings-embed', 'style', ['wp-components']);
        }
        // Preload our assets.
        $this->output_header_preload_tags();
    }
    /**
     * Modify script dependencies based on various conditions to only load the necessary scripts.
     *
     * @param array  $dependencies Array of script dependencies.
     * @param string $script Script name.
     * @return array Modified dependencies.
     */
    private function modify_script_dependencies($dependencies, string $script)
    {
        switch ($script) {
            case WC_ADMIN_APP:
                // Remove wp-editor dependency if we're not on a customize store page since we don't use wp-editor in other pages.
                $is_customize_store_page = Page_Controller::is_admin_page() && isset($_GET['path']) && str_starts_with(wc_clean(wp_unslash($_GET['path'])), '/customize-store');
                if (!$is_customize_store_page) {
                    $dependencies = array_diff($dependencies, ['wp-editor']);
                }
                // Remove product editor dependency from WC_ADMIN_APP when feature is disabled.
                if (!Features_Util::feature_is_enabled('product_block_editor')) {
                    $dependencies = array_diff($dependencies, ['wc-product-editor']);
                }
                break;
            case 'wc-product-editor':
                // Remove wp-editor dependency if the product editor feature is disabled as we don't need it.
                $is_product_data_view_page = \Automattic\Woo_Commerce\Admin\Features\Product_Data_Views\Init::is_product_data_view_page();
                if (!(Features_Util::feature_is_enabled('product_block_editor') || $is_product_data_view_page)) {
                    $dependencies = array_diff($dependencies, ['wp-editor']);
                }
                break;
        }
        return $dependencies;
    }
    /**
     * Registers all the necessary scripts and styles to show the admin experience.
     */
    public function register_scripts(): void
    {
        if (!function_exists('wp_set_script_translations')) {
            return;
        }
        // Register the JS scripts.
        $scripts = [
            'wc-admin-layout',
            'wc-explat',
            'wc-experimental',
            'wc-customer-effort-score',
            // NOTE: This should be removed when Gutenberg is updated and the notices package is removed from WooCommerce Admin.
            'wc-notices',
            'wc-number',
            'wc-tracks',
            'wc-date',
            'wc-components',
            WC_ADMIN_APP,
            'wc-csv',
            'wc-store-data',
            'wc-currency',
            'wc-navigation',
            'wc-block-templates',
            'wc-product-editor',
            'wc-settings-editor',
            'wc-remote-logging',
            'wc-sanitize',
        ];
        $scripts_map = [WC_ADMIN_APP => Page_Controller::is_embed_page() ? 'embed' : 'app', 'wc-csv' => 'csv-export', 'wc-store-data' => 'data'];
        $translated_scripts = ['wc-currency', 'wc-date', 'wc-components', 'wc-customer-effort-score', 'wc-experimental', 'wc-navigation', 'wc-product-editor', WC_ADMIN_APP];
        foreach ($scripts as $script) {
            $script_path_name = $scripts_map[$script] ?? str_replace('wc-', '', $script);
            try {
                $script_assets_filename = self::get_script_asset_filename($script_path_name, 'index');
                $script_assets = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . $script_path_name . '/' . $script_assets_filename;
                $script_version = self::get_file_version('js', $script_assets['version']);
                $script_dependencies = $this->modify_script_dependencies($script_assets['dependencies'], $script);
                wp_register_script($script, self::get_url($script_path_name . '/index', 'js'), $script_dependencies, $script_version, true);
                if (in_array($script, $translated_scripts, true)) {
                    wp_set_script_translations($script, 'woocommerce');
                }
                if (WC_ADMIN_APP === $script) {
                    wp_localize_script(WC_ADMIN_APP, 'wcAdminAssets', ['path' => plugins_url(self::get_path('js'), WC_ADMIN_PLUGIN_FILE), 'version' => $script_version]);
                }
            } catch (\Exception $e) {
                // Avoid crashing WordPress if an asset file could not be loaded.
                wc_caught_exception($e, self::class . '::' . __FUNCTION__, $script_path_name);
            }
        }
        // Register the CSS styles.
        $styles = [['handle' => 'wc-admin-layout'], ['handle' => 'wc-components'], ['handle' => 'wc-block-templates'], ['handle' => 'wc-product-editor'], ['handle' => 'wc-settings-editor'], ['handle' => 'wc-customer-effort-score'], ['handle' => 'wc-experimental'], ['handle' => WC_ADMIN_APP, 'dependencies' => ['wc-components', 'wc-admin-layout', 'wc-customer-effort-score', 'wp-components', 'wc-experimental']], ['handle' => 'wc-onboarding']];
        $css_file_version = self::get_file_version('css');
        foreach ($styles as $style) {
            $handle = $style['handle'];
            $style_path_name = $scripts_map[$handle] ?? str_replace('wc-', '', $handle);
            try {
                $style_assets_filename = self::get_script_asset_filename($style_path_name, 'style');
                $style_assets = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . $style_path_name . '/' . $style_assets_filename;
                $version = $style_assets['version'];
            } catch (\Throwable) {
                // Use the default version if the asset file could not be loaded.
                $version = $css_file_version;
            }
            $dependencies = $style['dependencies'] ?? [];
            wp_register_style($handle, self::get_url($style_path_name . '/style', 'css'), $dependencies, self::get_file_version('css', $version));
            wp_style_add_data($handle, 'rtl', 'replace');
        }
    }
    /**
     * Injects wp-shared-settings as a dependency if it's present.
     */
    public function inject_wc_settings_dependencies(): void
    {
        $wp_scripts = wp_scripts();
        if (wp_script_is('wc-settings', 'registered')) {
            $handles_for_injection = [
                'wc-admin-layout',
                'wc-csv',
                'wc-currency',
                'wc-customer-effort-score',
                'wc-navigation',
                // NOTE: This should be removed when Gutenberg is updated and
                // the notices package is removed from WooCommerce Admin.
                'wc-notices',
                'wc-number',
                'wc-date',
                'wc-components',
                'wc-tracks',
                'wc-block-templates',
                'wc-product-editor',
            ];
            foreach ($handles_for_injection as $handle) {
                $script = $wp_scripts->query($handle, 'registered');
                if ($script instanceof _WP_Dependency) {
                    $script->deps[] = 'wc-settings';
                    $wp_scripts->add_data($handle, 'group', 1);
                }
            }
            foreach ($wp_scripts->registered as $handle => $script) {
                // scripts that are loaded in the footer has extra->group = 1.
                if (array_intersect($handles_for_injection, $script->deps) && !isset($script->extra['group'])) {
                    // Append the script to footer.
                    $wp_scripts->add_data($handle, 'group', 1);
                    // Show a warning.
                    $error_handle = 'wc-settings-dep-in-header';
                    $used_deps = implode(', ', array_intersect($handles_for_injection, $script->deps));
                    $error_message = "Scripts that have a dependency on [{$used_deps}] must be loaded in the footer, {$handle} was registered to load in the header, but has been switched to load in the footer instead. See https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/5059";
                    // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter,WordPress.WP.EnqueuedResourceParameters.MissingVersion
                    wp_register_script($error_handle, '');
                    wp_enqueue_script($error_handle);
                    wp_add_inline_script($error_handle, sprintf('console.warn( "%s" );', $error_message));
                }
            }
        }
    }
    /**
     * Loads a script
     *
     * @param string $script_path_name The script path name.
     * @param string $script_name Filename of the script to load.
     * @param bool   $need_translation Whether the script need translations.
     * @param array  $dependencies Array of any extra dependencies. Note wc-admin and any application JS dependencies are automatically added by Dependency Extraction Webpack Plugin. Use this parameter to designate any extra dependencies.
     */
    public static function register_script(string $script_path_name, string $script_name, $need_translation = false, $dependencies = []): void
    {
        $script_assets_filename = self::get_script_asset_filename($script_path_name, $script_name);
        $script_assets = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_JS_FOLDER . $script_path_name . '/' . $script_assets_filename;
        wp_enqueue_script('wc-admin-' . $script_name, self::get_url($script_path_name . '/' . $script_name, 'js'), array_merge([WC_ADMIN_APP], $script_assets['dependencies'], $dependencies), self::get_file_version('js', $script_assets['version']), true);
        if ($need_translation) {
            wp_set_script_translations('wc-admin-' . $script_name, 'woocommerce');
        }
    }
    /**
     * Loads a style
     *
     * @param string $style_path_name The style path name.
     * @param string $style_name Filename of the style to load.
     * @param array  $dependencies Array of any extra dependencies.
     */
    public static function register_style(string $style_path_name, string $style_name, $dependencies = []): void
    {
        $style_assets_filename = self::get_script_asset_filename($style_path_name, $style_name);
        $style_assets = require WC_ADMIN_ABSPATH . WC_ADMIN_DIST_CSS_FOLDER . $style_path_name . '/' . $style_assets_filename;
        $handle = 'wc-admin-' . $style_name;
        wp_enqueue_style($handle, self::get_url($style_path_name . '/' . $style_name, 'css'), $dependencies, self::get_file_version('css', $style_assets['version']));
        wp_style_add_data($handle, 'rtl', 'replace');
    }
}