<?php

/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer as CoreEmailEditorIntegration;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Initializer as WooCommerceEmailEditorIntegration;

/**
 * Bootstrap class for initializing the Email Editor functionality.
 */
class Bootstrap
{
    /**
     * Constructor.
     *
     * @param Email_Editor                      $email_editor Email editor instance.
     * @param CoreEmailEditorIntegration        $core_email_editor_integration  Core email editor integration instance.
     * @param WooCommerceEmailEditorIntegration $woocommerce_email_editor_integration  WooCommerce email editor integration instance.
     */
    public function __construct(
        /**
         * Email editor instance.
         */
        private readonly Email_Editor $email_editor,
        /**
         * Core email editor integration instance.
         */
        private readonly CoreEmailEditorIntegration $core_email_editor_integration,
        /**
         * WooCommerce email editor integration instance.
         */
        private readonly WooCommerceEmailEditorIntegration $woocommerce_email_editor_integration
    ) {
    }

    /**
     * Initialize the email editor functionality.
     */
    public function init(): void
    {
        add_action(
            'init',
            $this->initialize(...)
        );

        add_filter(
            'woocommerce_email_editor_initialized',
            $this->setup_email_editor_integrations(...)
        );
        add_filter(
            'block_type_metadata_settings',
            $this->core_email_editor_integration->update_block_settings(...),
            10,
            1
        );

        if (class_exists('WooCommerce')) {
            add_filter(
                'block_type_metadata_settings',
                $this->woocommerce_email_editor_integration->update_block_settings(...),
                10,
                1
            );
        }
    }

    /**
     * Initialize the email editor.
     */
    public function initialize(): void
    {
        $this->email_editor->initialize();
    }

    /**
     * Setup email editor integrations.
     */
    public function setup_email_editor_integrations(): bool
    {
        $this->core_email_editor_integration->initialize();
        return true; // PHPStan expect returning a value from the filter.
    }
}
