<?php

declare (strict_types=1);
/**
 * Class for parameter-based Order Stats Reports querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'interval'     => 'week',
 *          'categories'   => array(15, 18),
 *          'coupons'      => array(138),
 *          'status_in'    => array('completed'),
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\Query( $args );
 * $mydata = $report->get_data();
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports\Orders\Stats;

use Automattic\Woo_Commerce\Admin\API\Reports\Generic_Query;
defined('ABSPATH') || exit;
/**
 * API\Reports\Orders\Stats\Query
 */
class Query extends Generic_Query
{
    /**
     * Specific query name.
     * Will be used to load the `report-{name}` data store,
     * and to call `woocommerce_analytics_{snake_case(name)}_*` filters.
     *
     * @var string
     */
    protected $name = 'orders-stats';
    /**
     * Valid fields for Orders report.
     */
    protected function get_default_query_vars(): array
    {
        return ['fields' => ['net_revenue', 'avg_order_value', 'orders_count', 'avg_items_per_order', 'num_items_sold', 'coupons', 'coupons_count', 'total_customers']];
    }
}