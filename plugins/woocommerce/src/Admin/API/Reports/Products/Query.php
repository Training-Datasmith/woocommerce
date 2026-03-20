<?php

declare (strict_types=1);
/**
 * Class for parameter-based Products Report querying
 *
 * Example usage:
 * $args = array(
 *          'before'       => '2018-07-19 00:00:00',
 *          'after'        => '2018-07-05 00:00:00',
 *          'page'         => 2,
 *          'categories'   => array(15, 18),
 *          'products'     => array(1,2,3)
 *         );
 * $report = new \Automattic\WooCommerce\Admin\API\Reports\Products\Query( $args );
 * $mydata = $report->get_data();
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports\Products;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\API\Reports\Query as ReportsQuery;
/**
 * API\Reports\Products\Query
 *
 * @deprecated 9.3.0 Products\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
 */
class Query extends Reports_Query
{
    /**
     * Valid fields for Products report.
     *
     * @deprecated 9.3.0 Products\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
     */
    protected function get_default_query_vars(): array
    {
        wc_deprecated_function(self::class . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use');
        return [];
    }
    /**
     * Get product data based on the current query vars.
     *
     * @deprecated 9.3.0 Products\Query class is deprecated. Please use `GenericQuery`, \WC_Object_Query`, or use `DataStore` directly.
     *
     * @return array
     */
    public function get_data()
    {
        wc_deprecated_function(self::class . '::' . __FUNCTION__, '9.3.0', '`GenericQuery`, `\WC_Object_Query`, or direct `DataStore` use');
        $args = apply_filters('woocommerce_analytics_products_query_args', $this->get_query_vars());
        $data_store = \WC_Data_Store::load('report-products');
        $results = $data_store->get_data($args);
        return apply_filters('woocommerce_analytics_products_select_query', $results, $args);
    }
}