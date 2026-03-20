<?php

declare (strict_types=1);
/**
 * A provider for getting the current DateTime.
 */
namespace Automattic\Woo_Commerce\Admin\Date_Time_Provider;

defined('ABSPATH') || exit;
/**
 * Current DateTime Provider.
 *
 * Uses the current DateTime.
 */
class Current_Date_Time_Provider implements Date_Time_Provider_Interface
{
    /**
     * Returns the current DateTime.
     */
    public function get_now(): \DateTime
    {
        return new \DateTime();
    }
}