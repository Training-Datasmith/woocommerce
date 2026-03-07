<?php

declare(strict_types=1);
/**
 * A provider for getting the current DateTime.
 */

namespace Automattic\WooCommerce\Admin\DateTimeProvider;

defined('ABSPATH') || exit;

/**
 * Current DateTime Provider.
 *
 * Uses the current DateTime.
 */
class CurrentDateTimeProvider implements DateTimeProviderInterface
{
    /**
     * Returns the current DateTime.
     */
    public function get_now(): \DateTime
    {
        return new \DateTime();
    }
}
