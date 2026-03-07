<?php

declare(strict_types=1);
/**
 * REST API Reports downloads stats controller
 *
 * Handles requests to the /reports/downloads/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\GenericStatsController;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports downloads stats controller class.
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
    protected $rest_base = 'reports/downloads/stats';

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
        $args['match']               = $request['match'];
        $args['product_includes']    = (array) $request['product_includes'];
        $args['product_excludes']    = (array) $request['product_excludes'];
        $args['customer_includes']   = (array) $request['customer_includes'];
        $args['customer_excludes']   = (array) $request['customer_excludes'];
        $args['order_includes']      = (array) $request['order_includes'];
        $args['order_excludes']      = (array) $request['order_excludes'];
        $args['ip_address_includes'] = (array) $request['ip_address_includes'];
        $args['ip_address_excludes'] = (array) $request['ip_address_excludes'];
        $args['fields']              = $request['fields'];
        $args['force_cache_refresh'] = $request['force_cache_refresh'];

        return $args;
    }

    /**
     * Get data from `'downloads-stats'` GenericQuery.
     *
     * @override GenericController::get_datastore_data()
     *
     * @param array $query_args Query arguments.
     * @return mixed Results from the data store.
     */
    protected function get_datastore_data($query_args = [])
    {
        $query = new GenericQuery($query_args, 'downloads-stats');
        return $query->get_data();
    }

    /**
     * Prepare a report data item for serialization.
     *
     * @param array           $report  Report data item as returned from Data Store.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function prepare_item_for_response($report, $request)
    {
        $response = parent::prepare_item_for_response($report, $request);

        /**
         * Filter a report returned from the API.
         *
         * Allows modification of the report data right before it is returned.
         *
         * @param WP_REST_Response $response The response object.
         * @param object           $report   The original report object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_report_downloads_stats', $response, $report, $request);
    }

    /**
     * Get the Report's item properties schema.
     * Will be used by `get_item_schema` as `totals` and `subtotals`.
     */
    protected function get_item_properties_schema(): array
    {
        return [
            'download_count' => [
                'title'       => __('Downloads', 'woocommerce'),
                'description' => __('Number of downloads.', 'woocommerce'),
                'type'        => 'number',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'indicator'   => true,
            ],
        ];
    }

    /**
     * Get the Report's schema, conforming to JSON Schema.
     * It does not have the segments as in GenericStatsController.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $totals = $this->get_item_properties_schema();

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report_orders_stats',
            'type'       => 'object',
            'properties' => [
                'totals'    => [
                    'description' => __('Totals data.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'properties'  => $totals,
                ],
                'intervals' => [
                    'description' => __('Reports data grouped by intervals.', 'woocommerce'),
                    'type'        => 'array',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'items'       => [
                        'type'       => 'object',
                        'properties' => [
                            'interval'       => [
                                'description' => __('Type of interval.', 'woocommerce'),
                                'type'        => 'string',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'enum'        => [ 'day', 'week', 'month', 'year' ],
                            ],
                            'date_start'     => [
                                'description' => __("The date the report start, in the site's timezone.", 'woocommerce'),
                                'type'        => 'date-time',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'date_start_gmt' => [
                                'description' => __('The date the report start, as GMT.', 'woocommerce'),
                                'type'        => 'date-time',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'date_end'       => [
                                'description' => __("The date the report end, in the site's timezone.", 'woocommerce'),
                                'type'        => 'date-time',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'date_end_gmt'   => [
                                'description' => __('The date the report end, as GMT.', 'woocommerce'),
                                'type'        => 'date-time',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                            ],
                            'subtotals'      => [
                                'description' => __('Interval subtotals.', 'woocommerce'),
                                'type'        => 'object',
                                'context'     => [ 'view', 'edit' ],
                                'readonly'    => true,
                                'properties'  => $totals,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }

    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params()
    {
        $params                     = parent::get_collection_params();
        $params['orderby']['enum']  = $this->apply_custom_orderby_filters(
            [
                'date',
                'download_count',
            ]
        );
        $params['match']            = [
            'description'       => __('Indicates whether all the conditions should be true for the resulting set, or if any one of them is sufficient. Match affects the following parameters: status_is, status_is_not, product_includes, product_excludes, coupon_includes, coupon_excludes, customer, categories', 'woocommerce'),
            'type'              => 'string',
            'default'           => 'all',
            'enum'              => [
                'all',
                'any',
            ],
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['product_includes'] = [
            'description'       => __('Limit result set to items that have the specified product(s) assigned.', 'woocommerce'),
            'type'              => 'array',
            'items'             => [
                'type' => 'integer',
            ],
            'default'           => [],
            'sanitize_callback' => 'wp_parse_id_list',

        ];
        $params['product_excludes']    = [
            'description'       => __('Limit result set to items that don\'t have the specified product(s) assigned.', 'woocommerce'),
            'type'              => 'array',
            'items'             => [
                'type' => 'integer',
            ],
            'default'           => [],
            'sanitize_callback' => 'wp_parse_id_list',
        ];
        $params['order_includes']      = [
            'description'       => __('Limit result set to items that have the specified order ids.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['order_excludes']      = [
            'description'       => __('Limit result set to items that don\'t have the specified order ids.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['customer_includes']   = [
            'description'       => __('Limit response to objects that have the specified customer ids.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['customer_excludes']   = [
            'description'       => __('Limit response to objects that don\'t have the specified customer ids.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['ip_address_includes'] = [
            'description'       => __('Limit response to objects that have a specified ip address.', 'woocommerce'),
            'type'              => 'array',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'string',
            ],
        ];

        $params['ip_address_excludes'] = [
            'description'       => __('Limit response to objects that don\'t have a specified ip address.', 'woocommerce'),
            'type'              => 'array',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'string',
            ],
        ];

        return $params;
    }
}
