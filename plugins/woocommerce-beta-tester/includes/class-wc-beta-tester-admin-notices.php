<?php

declare(strict_types=1);
/**
 * Admin notices
 *
 * @package WC_Beta_Tester\Admin
 */

defined('ABSPATH') || exit;

/**
 * Admin notices class.
 */
class WC_Beta_Tester_Admin_Notices
{
    /**
     * WooCommerce not installed notice.
     */
    public function woocoommerce_not_installed(): void
    {
        include_once __DIR__ . '/views/html-admin-missing-woocommerce.php';
    }
}
