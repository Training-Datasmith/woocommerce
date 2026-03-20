<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Domain;

use Automattic\Jetpack\Constants;
use Automattic\Woo_Commerce\Blocks\Assets\Api as AssetApi;
use Automattic\Woo_Commerce\Blocks\Assets\Asset_Data_Registry;
use Automattic\Woo_Commerce\Blocks\Assets_Controller;
use Automattic\Woo_Commerce\Blocks\Block_Patterns;
use Automattic\Woo_Commerce\Blocks\Block_Templates_Controller;
use Automattic\Woo_Commerce\Blocks\Block_Templates_Registry;
use Automattic\Woo_Commerce\Blocks\Block_Types_Controller;
use Automattic\Woo_Commerce\Blocks\Dependency_Detection;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Fields;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Fields_Admin;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Fields_Frontend;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Link;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Draft_Orders;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Google_Analytics;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Hydration;
use Automattic\Woo_Commerce\Blocks\Domain\Services\Notices;
use Automattic\Woo_Commerce\Blocks\Inbox_Notifications;
use Automattic\Woo_Commerce\Blocks\Installer;
use Automattic\Woo_Commerce\Blocks\Patterns\Ai_Patterns;
use Automattic\Woo_Commerce\Blocks\Patterns\Pattern_Registry;
use Automattic\Woo_Commerce\Blocks\Patterns\Ptk_Client;
use Automattic\Woo_Commerce\Blocks\Patterns\Ptk_Patterns_Store;
use Automattic\Woo_Commerce\Blocks\Payments\Api as PaymentsApi;
use Automattic\Woo_Commerce\Blocks\Payments\Integrations\Bank_Transfer;
use Automattic\Woo_Commerce\Blocks\Payments\Integrations\Cash_On_Delivery;
use Automattic\Woo_Commerce\Blocks\Payments\Integrations\Cheque;
use Automattic\Woo_Commerce\Blocks\Payments\Integrations\Pay_Pal;
use Automattic\Woo_Commerce\Blocks\Payments\Payment_Method_Registry;
use Automattic\Woo_Commerce\Blocks\Query_Filters;
use Automattic\Woo_Commerce\Blocks\Registry\Container;
use Automattic\Woo_Commerce\Blocks\Shipping\Shipping_Controller;
use Automattic\Woo_Commerce\Blocks\Template_Options;
use Automattic\Woo_Commerce\Blocks\Templates\Classic_Templates_Compatibility;
use Automattic\Woo_Commerce\Store_Api\Routes_Controller;
use Automattic\Woo_Commerce\Store_Api\Schema_Controller;
use Automattic\Woo_Commerce\Store_Api\Store_Api;
/**
 * Takes care of bootstrapping the plugin.
 *
 * @since 2.5.0
 */
class Bootstrap
{
    /**
     * Holds the Package instance
     *
     * @var Package
     */
    private $package;
    /**
     * Constructor
     *
     * @param Container $container  The Dependency Injection Container.
     */
    public function __construct(
        /**
         * Holds the Dependency Injection Container
         */
        private readonly Container $container
    )
    {
        $this->package = $this->container->get(Package::class);
        $this->init();
        /**
         * Fires when the woocommerce blocks are loaded and ready to use.
         *
         * This hook is intended to be used as a safe event hook for when the plugin
         * has been loaded, and all dependency requirements have been met.
         *
         * To ensure blocks are initialized, you must use the `woocommerce_blocks_loaded`
         * hook instead of the `plugins_loaded` hook. This is because the functions
         * hooked into plugins_loaded on the same priority load in an inconsistent and unpredictable manner.
         *
         * @since 2.5.0
         */
        do_action('woocommerce_blocks_loaded');
    }
    /**
     * Init the package - load the blocks library and define constants.
     */
    protected function init()
    {
        $this->register_dependencies();
        $this->register_payment_methods();
        add_action('admin_init', function (): void {
            // Delete this notification because the blocks are included in WC Core now. This will handle any sites
            // with lingering notices.
            Inbox_Notifications::delete_surface_cart_checkout_blocks_notification();
        }, 10, 0);
        // We need to initialize BlockTemplatesController and BlockTemplatesRegistry at the end of `after_setup_theme`
        // so themes had the opportunity to declare support for template parts.
        add_action('after_setup_theme', function (): void {
            $is_store_api_request = wc()->is_store_api_request();
            if (!$is_store_api_request && (wp_is_block_theme() || current_theme_supports('block-template-parts'))) {
                $this->container->get(Block_Templates_Registry::class)->init();
                $this->container->get(Block_Templates_Controller::class)->init();
            }
        }, 999);
        $is_rest = wc()->is_rest_api_request();
        $is_store_api_request = wc()->is_store_api_request();
        // Initialize Store API in non-admin context.
        if (!is_admin()) {
            $this->container->get(Store_Api::class)->init();
        }
        // Load and init assets.
        $this->container->get(Payments_Api::class)->init();
        $this->container->get(Draft_Orders::class)->init();
        $this->container->get(Shipping_Controller::class)->init();
        $this->container->get(Checkout_Fields::class)->init();
        $this->container->get(Checkout_Link::class)->init();
        $this->container->get(Asset_Data_Registry::class);
        $this->container->get(Assets_Controller::class);
        $this->container->get(Dependency_Detection::class);
        // Load assets in admin and on the frontend.
        if (!$is_rest) {
            $this->add_build_notice();
            $this->container->get(Installer::class)->init();
            $this->container->get(Google_Analytics::class)->init();
            $this->container->get(is_admin() ? Checkout_Fields_Admin::class : Checkout_Fields_Frontend::class)->init();
        }
        // Load assets unless this is a request specifically for the store API.
        if (!$is_store_api_request) {
            // Template related functionality. These won't be loaded for store API requests, but may be loaded for
            // regular rest requests to maintain compatibility with the store editor.
            $this->container->get(Block_Patterns::class);
            $this->container->get(Block_Types_Controller::class);
            $this->container->get(Classic_Templates_Compatibility::class);
            $this->container->get(Notices::class)->init();
            if (is_admin() || $is_rest) {
                $this->container->get(Ai_Patterns::class);
                $this->container->get(Ptk_Patterns_Store::class);
            }
            if (is_admin()) {
                $this->container->get(Template_Options::class)->init();
            }
        }
        $this->container->get(Query_Filters::class)->init();
    }
    /**
     * See if files have been built or not.
     */
    protected function is_built(): bool
    {
        return file_exists($this->package->get_path('assets/client/blocks/featured-product.js'));
    }
    /**
     * Add a notice stating that the build has not been done yet.
     */
    protected function add_build_notice()
    {
        if ($this->is_built()) {
            return;
        }
        add_action('admin_notices', function (): void {
            echo '<div class="error"><p>';
            printf(
                /* translators: %1$s is the node install command, %2$s is the install command, %3$s is the build command, %4$s is the watch command. */
                esc_html__('WooCommerce Blocks development mode requires files to be built. From the root directory, run %1$s to ensure your node version is aligned, run %2$s to install dependencies, %3$s to build the files or %4$s to build the files and watch for changes.', 'woocommerce'),
                '<code>nvm use</code>',
                '<code>pnpm install</code>',
                '<code>pnpm --filter="@woocommerce/plugin-woocommerce" build</code>',
                '<code>pnpm --filter="@woocommerce/plugin-woocommerce" watch:build</code>'
            );
            echo '</p></div>';
        });
    }
    /**
     * Register core dependencies with the container.
     */
    protected function register_dependencies()
    {
        $this->container->register(Asset_Api::class, fn(Container $container) => new Asset_Api($container->get(Package::class)));
        $this->container->register(Asset_Data_Registry::class, fn(Container $container) => new Asset_Data_Registry($container->get(Asset_Api::class)));
        $this->container->register(Assets_Controller::class, fn(Container $container) => new Assets_Controller($container->get(Asset_Api::class)));
        $this->container->register(Dependency_Detection::class, fn() => new Dependency_Detection());
        $this->container->register(Payment_Method_Registry::class, fn() => new Payment_Method_Registry());
        $this->container->register(Installer::class, fn() => new Installer());
        $this->container->register(Block_Types_Controller::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Block_Types_Controller {
            $asset_api = $container->get(Asset_Api::class);
            $asset_data_registry = $container->get(Asset_Data_Registry::class);
            return new Block_Types_Controller($asset_api, $asset_data_registry);
        });
        $this->container->register(Classic_Templates_Compatibility::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Templates\Classic_Templates_Compatibility {
            $asset_data_registry = $container->get(Asset_Data_Registry::class);
            return new Classic_Templates_Compatibility($asset_data_registry);
        });
        $this->container->register(Draft_Orders::class, fn(Container $container) => new Draft_Orders($container->get(Package::class)));
        $this->container->register(Google_Analytics::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Domain\Services\Google_Analytics {
            $asset_api = $container->get(Asset_Api::class);
            return new Google_Analytics($asset_api);
        });
        $this->container->register(Notices::class, fn(Container $container) => new Notices($container->get(Package::class)));
        $this->container->register(Hydration::class, fn(Container $container) => new Hydration($container->get(Asset_Data_Registry::class)));
        $this->container->register(Checkout_Fields::class, fn(Container $container) => new Checkout_Fields($container->get(Asset_Data_Registry::class)));
        $this->container->register(Checkout_Fields_Admin::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Fields_Admin {
            $checkout_fields_controller = $container->get(Checkout_Fields::class);
            return new Checkout_Fields_Admin($checkout_fields_controller);
        });
        $this->container->register(Checkout_Fields_Frontend::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Fields_Frontend {
            $checkout_fields_controller = $container->get(Checkout_Fields::class);
            return new Checkout_Fields_Frontend($checkout_fields_controller);
        });
        $this->container->register(Payments_Api::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Payments\Api {
            $payment_method_registry = $container->get(Payment_Method_Registry::class);
            $asset_data_registry = $container->get(Asset_Data_Registry::class);
            return new Payments_Api($payment_method_registry, $asset_data_registry);
        });
        $this->container->register(Checkout_Link::class, fn() => new Checkout_Link());
        $this->container->register(Store_Api::class, fn() => new Store_Api());
        $this->container->register(Template_Options::class, fn() => new Template_Options());
        // Maintains backwards compatibility with previous Store API namespace.
        $this->container->register('Automattic\WooCommerce\Blocks\StoreApi\Formatters', function (Container $container) {
            $this->deprecated_dependency('Automattic\WooCommerce\Blocks\StoreApi\Formatters', '6.4.0', \Automattic\Woo_Commerce\Store_Api\Formatters::class, '6.5.0');
            return $container->get(Store_Api::class)->container()->get(\Automattic\Woo_Commerce\Store_Api\Formatters::class);
        });
        $this->container->register('Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi', function (Container $container) {
            $this->deprecated_dependency('Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi', '6.4.0', \Automattic\Woo_Commerce\Store_Api\Schemas\Extend_Schema::class, '6.5.0');
            return $container->get(Store_Api::class)->container()->get(\Automattic\Woo_Commerce\Store_Api\Schemas\Extend_Schema::class);
        });
        $this->container->register('Automattic\WooCommerce\Blocks\StoreApi\SchemaController', function (Container $container) {
            $this->deprecated_dependency('Automattic\WooCommerce\Blocks\StoreApi\SchemaController', '6.4.0', \Automattic\Woo_Commerce\Store_Api\Schema_Controller::class, '6.5.0');
            return $container->get(Store_Api::class)->container()->get(Schema_Controller::class);
        });
        $this->container->register('Automattic\WooCommerce\Blocks\StoreApi\RoutesController', function (Container $container) {
            $this->deprecated_dependency('Automattic\WooCommerce\Blocks\StoreApi\RoutesController', '6.4.0', \Automattic\Woo_Commerce\Store_Api\Routes_Controller::class, '6.5.0');
            return $container->get(Store_Api::class)->container()->get(Routes_Controller::class);
        });
        $this->container->register(Ptk_Client::class, fn() => new Ptk_Client());
        $this->container->register(Ptk_Patterns_Store::class, fn() => new Ptk_Patterns_Store($this->container->get(Ptk_Client::class)));
        $this->container->register(Block_Patterns::class, fn() => new Block_Patterns($this->package, new Pattern_Registry(), $this->container->get(Ptk_Patterns_Store::class)));
        $this->container->register(Ai_Patterns::class, fn() => new Ai_Patterns());
        $this->container->register(Shipping_Controller::class, function ($container): \Automattic\Woo_Commerce\Blocks\Shipping\Shipping_Controller {
            $asset_api = $container->get(Asset_Api::class);
            $asset_data_registry = $container->get(Asset_Data_Registry::class);
            return new Shipping_Controller($asset_api, $asset_data_registry);
        });
        $this->container->register(Query_Filters::class, fn() => new Query_Filters());
        $this->container->register(Block_Templates_Registry::class, fn() => new Block_Templates_Registry());
        $this->container->register(Block_Templates_Controller::class, fn() => new Block_Templates_Controller());
    }
    /**
     * Throws a deprecation notice for a dependency without breaking requests.
     *
     * @param string $function Class or function being deprecated.
     * @param string $version Version in which it was deprecated.
     * @param string $replacement Replacement class or function, if applicable.
     * @param string $trigger_error_version Optional version to start surfacing this as a PHP error rather than a log. Defaults to $version.
     */
    protected function deprecated_dependency($function, $version, $replacement = '', $trigger_error_version = '')
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        $trigger_error_version = $trigger_error_version ?: $version;
        $error_message = $replacement ? sprintf('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.', $function, $version, $replacement) : sprintf('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.', $function, $version);
        /**
         * Fires when a deprecated function is called.
         *
         * @since 7.3.0
         */
        do_action('deprecated_function_run', $function, $replacement, $version);
        $log_error = false;
        // If headers have not been sent yet, log to avoid breaking the request.
        if (!headers_sent()) {
            $log_error = true;
        }
        // If the $trigger_error_version was not yet reached, only log the error.
        if (version_compare(Constants::get_constant('WC_VERSION'), $trigger_error_version, '<')) {
            $log_error = true;
        }
        /**
         * Filters whether to trigger an error for deprecated functions. (Same as WP core)
         *
         * @since 7.3.0
         *
         * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
         */
        if (!apply_filters('deprecated_function_trigger_error', true)) {
            $log_error = true;
        }
        if ($log_error) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($error_message);
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
            trigger_error($error_message, E_USER_DEPRECATED);
        }
    }
    /**
     * Register payment method integrations with the container.
     */
    protected function register_payment_methods()
    {
        $this->container->register(Cheque::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Payments\Integrations\Cheque {
            $asset_api = $container->get(Asset_Api::class);
            return new Cheque($asset_api);
        });
        $this->container->register(Pay_Pal::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Payments\Integrations\Pay_Pal {
            $asset_api = $container->get(Asset_Api::class);
            return new Pay_Pal($asset_api);
        });
        $this->container->register(Bank_Transfer::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Payments\Integrations\Bank_Transfer {
            $asset_api = $container->get(Asset_Api::class);
            return new Bank_Transfer($asset_api);
        });
        $this->container->register(Cash_On_Delivery::class, function (Container $container): \Automattic\Woo_Commerce\Blocks\Payments\Integrations\Cash_On_Delivery {
            $asset_api = $container->get(Asset_Api::class);
            return new Cash_On_Delivery($asset_api);
        });
    }
}