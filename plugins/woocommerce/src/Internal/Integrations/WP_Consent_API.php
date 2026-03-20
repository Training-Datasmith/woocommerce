<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Integrations;

use Automattic\Jetpack\Constants;
use Automattic\Woo_Commerce\Internal\Traits\Script_Debug;
use WP_CONSENT_API;
/**
 * Class WPConsentAPI
 *
 * @since 8.5.0
 */
class Wp_Consent_Api
{
    use Script_Debug;
    /**
     * Identifier of the consent category used for order attribution.
     *
     * @var string
     */
    public static $consent_category = 'marketing';
    /**
     * Register the consent API.
     */
    public function register(): void
    {
        add_action('init', function (): void {
            $this->on_init();
        }, 20);
    }
    /**
     * Register our hooks on init.
     *
     * @return void
     */
    protected function on_init()
    {
        // Include integration to WP Consent Level API if available.
        if (!$this->is_wp_consent_api_active()) {
            return;
        }
        $plugin = plugin_basename(WC_PLUGIN_FILE);
        add_filter("wp_consent_api_registered_{$plugin}", '__return_true');
        add_action('wp_enqueue_scripts', function (): void {
            $this->enqueue_consent_api_scripts();
        });
        /**
         * Modify the "allowTracking" flag consent if the user has consented to marketing.
         *
         * Wp-consent-api will initialize the modules on "init" with priority 9,
         * So this code needs to be run after that.
         */
        add_filter('wc_order_attribution_allow_tracking', fn() => function_exists('wp_has_consent') && wp_has_consent(self::$consent_category));
    }
    /**
     * Check if WP Cookie Consent API is active
     */
    protected function is_wp_consent_api_active(): bool
    {
        return class_exists(WP_CONSENT_API::class);
    }
    /**
     * Enqueue JS for integration with WP Consent Level API
     */
    private function enqueue_consent_api_scripts(): void
    {
        wp_enqueue_script('wp-consent-api-integration', plugins_url("assets/js/frontend/wp-consent-api-integration{$this->get_script_suffix()}.js", WC_PLUGIN_FILE), ['wp-consent-api', 'wc-order-attribution'], Constants::get_constant('WC_VERSION'), true);
        // Add data for the script above. `wp_enqueue_script` API does not allow data attributes,
        // so we need a separate script tag and pollute the global scope.
        wp_add_inline_script('wp-consent-api-integration', sprintf('window.wc_order_attribution.params.consentCategory = %s;', wp_json_encode(self::$consent_category, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES)), 'before');
    }
}