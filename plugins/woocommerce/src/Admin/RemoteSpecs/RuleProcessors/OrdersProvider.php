<?php

declare (strict_types=1);
/**
 * Provider for order-related queries and operations.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
/**
 * Provider for order-related queries and operations.
 */
class Orders_Provider
{
    /**
     * Allowed order statuses for calculating milestones.
     *
     * @var array
     */
    protected $allowed_statuses = ['pending', 'processing', 'completed'];
    /**
     * Returns the number of orders.
     *
     * @return integer The number of orders.
     */
    public function get_order_count(): float|int
    {
        $status_counts = array_map(wc_orders_count(...), $this->allowed_statuses);
        return array_sum($status_counts);
    }
}