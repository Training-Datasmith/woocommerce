<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Agentic;

use Automattic\Jetpack\Constants;
use Automattic\Woo_Commerce\Internal\Register_Hooks_Interface;
use Automattic\Woo_Commerce\Utilities\Features_Util;
/**
 * AgenticController class
 *
 * Main controller for Agentic Commerce Protocol features.
 * Manages initialization of webhooks and future settings for the Agentic feature.
 *
 * @since 10.3.0
 */
class Agentic_Controller implements Register_Hooks_Interface
{
    /**
     * Register this class instance to the appropriate hooks.
     *
     * @internal
     */
    public function register(): void
    {
        // Don't register hooks during installation.
        if (Constants::is_true('WC_INSTALLING')) {
            return;
        }
        // We want to run on init for translations but before woocommerce_init so that
        // we can hook the new integration settings page. We should be able to simplify
        // this by just hooking here when we no longer need to check if the feature is enabled.
        add_action('before_woocommerce_init', $this->on_init(...));
    }
    /**
     * Hook into WordPress on init.
     *
     * @internal
     */
    public function on_init(): void
    {
        // Bail if the feature is not enabled.
        if (!Features_Util::feature_is_enabled('agentic_checkout')) {
            return;
        }
        // Resolve webhook manager from container.
        wc_get_container()->get(Agentic_Webhook_Manager::class)->register();
    }
}