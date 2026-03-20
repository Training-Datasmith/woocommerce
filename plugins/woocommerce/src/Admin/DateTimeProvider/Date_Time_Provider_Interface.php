<?php

declare (strict_types=1);
/**
 * Interface for a provider for getting the current DateTime,
 * designed to be mockable for unit tests.
 */
namespace Automattic\Woo_Commerce\Admin\Date_Time_Provider;

defined('ABSPATH') || exit;
/**
 * DateTime Provider Interface.
 */
interface Date_Time_Provider_Interface
{
    /**
     * Returns the current DateTime.
     *
     * @return DateTime
     */
    public function get_now();
}