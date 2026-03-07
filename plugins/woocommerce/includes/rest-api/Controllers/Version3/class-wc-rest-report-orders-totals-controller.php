<?php

declare(strict_types=1);
/**
 * REST API Reports Orders Totals controller
 *
 * Handles requests to the /reports/orders/count endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   3.5.0
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

defined('ABSPATH') || exit;

/**
 * REST API Reports Orders Totals controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Reports_Controller
 */
class WC_REST_Report_Orders_Totals_Controller extends WC_REST_Reports_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc/v3';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/orders/totals';

    /**
     * Get reports list.
     *
     * @since 3.5.0
     */
    protected function get_reports(): array
    {
        $totals = OrderUtil::get_count_for_type('shop_order');
        $data   = [];

        foreach (wc_get_order_statuses() as $slug => $name) {
            if (! array_key_exists($slug, $totals)) {
                continue;
            }

            $data[] = [
                'slug'  => str_replace('wc-', '', $slug),
                'name'  => $name,
                'total' => $totals[ $slug ],
            ];
        }

        return $data;
    }

    /**
     * Prepare a report object for serialization.
     *
     * @param  stdClass        $report Report data.
     * @param  WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($report, $request)
    {
        $data = [
            'slug'  => $report->slug,
            'name'  => $report->name,
            'total' => $report->total,
        ];

        $context = ! empty($request['context']) ? $request['context'] : 'view';
        $data    = $this->add_additional_fields_to_object($data, $request);
        $data    = $this->filter_response_by_context($data, $context);

        // Wrap the data in a response object.
        $response = rest_ensure_response($data);

        /**
         * Filter a report returned from the API.
         *
         * Allows modification of the report data right before it is returned.
         *
         * @param WP_REST_Response $response The response object.
         * @param object           $report   The original report object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_report_orders_count', $response, $report, $request);
    }

    /**
     * Get the Report's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report_order_total',
            'type'       => 'object',
            'properties' => [
                'slug'  => [
                    'description' => __('An alphanumeric identifier for the resource.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'name'  => [
                    'description' => __('Order status name.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'total' => [
                    'description' => __('Amount of orders.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }
}
