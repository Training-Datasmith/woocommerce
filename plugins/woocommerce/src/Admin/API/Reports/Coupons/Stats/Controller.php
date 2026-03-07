<?php

declare(strict_types=1);
/**
 * REST API Reports coupons stats controller
 *
 * Handles requests to the /reports/coupons/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports coupons stats controller class.
 *
 * @internal
 * @extends GenericStatsController
 */
class Controller extends GenericStatsController
{
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/coupons/stats';

    /**
     * Maps query arguments from the REST request.
     *
     * @param array $request Request array.
     */
    protected function prepare_reports_query($request): array
    {
        $args                        = [];
        $args['before']              = $request['before'];
        $args['after']               = $request['after'];
        $args['interval']            = $request['interval'];
        $args['page']                = $request['page'];
        $args['per_page']            = $request['per_page'];
        $args['orderby']             = $request['orderby'];
        $args['order']               = $request['order'];
        $args['coupons']             = (array) $request['coupons'];
        $args['segmentby']           = $request['segmentby'];
        $args['fields']              = $request['fields'];
        $args['force_cache_refresh'] = $request['force_cache_refresh'];

        return $args;
    }

    /**
     * Get data from `'coupons-stats'` GenericQuery.
     *
     * @override GenericController::get_datastore_data()
     *
     * @param array $query_args Query arguments.
     * @return mixed Results from the data store.
     */
    protected function get_datastore_data($query_args = [])
    {
        $query = new GenericQuery($query_args, 'coupons-stats');
        return $query->get_data();
    }

    /**
     * Prepare a report data item for serialization.
     *
     * @param mixed           $report  Report data item as returned from Data Store.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function prepare_item_for_response($report, $request)
    {
        $response = parent::prepare_item_for_response($report, $request);

        // Map to `object` for backwards compatibility.
        $report = (object) $report;
        /**
         * Filter a report returned from the API.
         *
         * Allows modification of the report data right before it is returned.
         *
         * @param WP_REST_Response $response The response object.
         * @param object           $report   The original report object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_report_coupons_stats', $response, $report, $request);
    }

    /**
     * Get the Report's item properties schema.
     * Will be used by `get_item_schema` as `totals` and `subtotals`.
     */
    protected function get_item_properties_schema(): array
    {
        return [
            'amount'        => [
                'description' => __('Net discount amount.', 'woocommerce'),
                'type'        => 'number',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'indicator'   => true,
                'format'      => 'currency',
            ],
            'coupons_count' => [
                'description' => __('Number of coupons.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'orders_count'  => [
                'title'       => __('Discounted orders', 'woocommerce'),
                'description' => __('Number of discounted orders.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'indicator'   => true,
            ],
        ];
    }

    /**
     * Get the Report's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema          = parent::get_item_schema();
        $schema['title'] = 'report_coupons_stats';

        return $this->add_additional_fields_schema($schema);
    }

    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params()
    {
        $params                    = parent::get_collection_params();
        $params['orderby']['enum'] = $this->apply_custom_orderby_filters(
            [
                'date',
                'amount',
                'coupons_count',
                'orders_count',
            ]
        );
        $params['coupons']         = [
            'description'       => __('Limit result set to coupons assigned specific coupon IDs.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['segmentby']       = [
            'description'       => __('Segment the response by additional constraint.', 'woocommerce'),
            'type'              => 'string',
            'enum'              => [
                'product',
                'variation',
                'category',
                'coupon',
            ],
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }
}
