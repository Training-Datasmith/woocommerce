<?php

declare (strict_types=1);
/**
 * WCAdmin active for provider.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

use Automattic\Woo_Commerce\Admin\Wc_Admin_Helper;
defined('ABSPATH') || exit;
/**
 * WCAdminActiveForProvider class
 */
class Wc_Admin_Active_For_Provider
{
    /**
     * Get the number of seconds that the store has been active.
     *
     * @return number Number of seconds.
     */
    public function get_wcadmin_active_for_in_seconds()
    {
        return Wc_Admin_Helper::get_wcadmin_active_for_in_seconds();
    }
}