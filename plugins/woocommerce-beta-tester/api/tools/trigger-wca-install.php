<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

register_woocommerce_admin_test_helper_rest_route(
    '/tools/trigger-wca-install/v1',
    'tools_trigger_wca_install'
);

/**
 * A tool to trigger the WooCommerce install.
 */
function tools_trigger_wca_install(): bool
{
    \WC_Install::install();

    return true;
}
