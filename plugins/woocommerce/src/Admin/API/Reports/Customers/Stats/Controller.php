<?php

declare(strict_types=1);
/**
 * REST API Reports customers stats controller
 *
 * Handles requests to the /reports/customers/stats endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Customers\Stats;

use Automattic\WooCommerce\Admin\API\Reports\Customers\Query;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * REST API Reports customers stats controller class.
 *
 * @internal
 * @extends WC_REST_Reports_Controller
 */
class Controller extends \WC_REST_Reports_Controller
{
    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wc-analytics';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/customers/stats';

    /**
     * Maps query arguments from the REST request.
     *
     * @param array $request Request array.
     * @return array
     */
    protected function prepare_reports_query($request)
    {
        $args                        = [];
        $args['registered_before']   = $request['registered_before'];
        $args['registered_after']    = $request['registered_after'];
        $args['match']               = $request['match'];
        $args['search']              = $request['search'];
        $args['name_includes']       = $request['name_includes'];
        $args['name_excludes']       = $request['name_excludes'];
        $args['username_includes']   = $request['username_includes'];
        $args['username_excludes']   = $request['username_excludes'];
        $args['email_includes']      = $request['email_includes'];
        $args['email_excludes']      = $request['email_excludes'];
        $args['country_includes']    = $request['country_includes'];
        $args['country_excludes']    = $request['country_excludes'];
        $args['last_active_before']  = $request['last_active_before'];
        $args['last_active_after']   = $request['last_active_after'];
        $args['orders_count_min']    = $request['orders_count_min'];
        $args['orders_count_max']    = $request['orders_count_max'];
        $args['total_spend_min']     = $request['total_spend_min'];
        $args['total_spend_max']     = $request['total_spend_max'];
        $args['avg_order_value_min'] = $request['avg_order_value_min'];
        $args['avg_order_value_max'] = $request['avg_order_value_max'];
        $args['last_order_before']   = $request['last_order_before'];
        $args['last_order_after']    = $request['last_order_after'];
        $args['customers']           = $request['customers'];
        $args['fields']              = $request['fields'];
        $args['force_cache_refresh'] = $request['force_cache_refresh'];

        $between_params_numeric    = [ 'orders_count', 'total_spend', 'avg_order_value' ];
        $normalized_params_numeric = TimeInterval::normalize_between_params($request, $between_params_numeric, false);
        $between_params_date       = [ 'last_active', 'registered' ];
        $normalized_params_date    = TimeInterval::normalize_between_params($request, $between_params_date, true);

        return array_merge($args, $normalized_params_numeric, $normalized_params_date);
    }

    /**
     * Get all reports.
     *
     * @param WP_REST_Request $request Request data.
     * @return array|WP_Error
     */
    public function get_items($request)
    {
        $query_args      = $this->prepare_reports_query($request);
        $customers_query = new Query($query_args, 'customers-stats');
        $report_data     = $customers_query->get_data();
        $out_data        = [
            'totals' => $report_data,
        ];

        return rest_ensure_response($out_data);
    }

    /**
     * Prepare a report data item for serialization.
     *
     * @param array            $report  Report data item as returned from Data Store.
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function prepare_item_for_response($report, $request)
    {
        $data = $report;

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
        return apply_filters('woocommerce_rest_prepare_report_customers_stats', $response, $report, $request);
    }

    /**
     * Get the Report's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        // @todo Should any of these be 'indicator's?
        $totals = [
            'customers_count'     => [
                'description' => __('Number of customers.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'avg_orders_count'    => [
                'description' => __('Average number of orders.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'avg_total_spend'     => [
                'description' => __('Average total spend per customer.', 'woocommerce'),
                'type'        => 'number',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'format'      => 'currency',
            ],
            'avg_avg_order_value' => [
                'description' => __('Average AOV per customer.', 'woocommerce'),
                'type'        => 'number',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'format'      => 'currency',
            ],
        ];

        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report_customers_stats',
            'type'       => 'object',
            'properties' => [
                'totals' => [
                    'description' => __('Totals data.', 'woocommerce'),
                    'type'        => 'object',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                    'properties'  => $totals,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }

    /**
     * Get the query params for collections.
     */
    public function get_collection_params(): array
    {
        $params                            = [];
        $params['context']                 = $this->get_context_param([ 'default' => 'view' ]);
        $params['registered_before']       = [
            'description'       => __('Limit response to objects registered before (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['registered_after']        = [
            'description'       => __('Limit response to objects registered after (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['match']                   = [
            'description'       => __('Indicates whether all the conditions should be true for the resulting set, or if any one of them is sufficient. Match affects the following parameters: status_is, status_is_not, product_includes, product_excludes, coupon_includes, coupon_excludes, customer, categories', 'woocommerce'),
            'type'              => 'string',
            'default'           => 'all',
            'enum'              => [
                'all',
                'any',
            ],
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['search']                  = [
            'description'       => __('Limit response to objects with a customer field containing the search term. Searches the field provided by `searchby`.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['searchby']                = [
            'description' => 'Limit results with `search` and `searchby` to specific fields containing the search term.',
            'type'        => 'string',
            'default'     => 'name',
            'enum'        => [
                'name',
                'username',
                'email',
                'all',
            ],
        ];
        $params['name_includes']           = [
            'description'       => __('Limit response to objects with specific names.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['name_excludes']           = [
            'description'       => __('Limit response to objects excluding specific names.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['username_includes']       = [
            'description'       => __('Limit response to objects with specific usernames.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['username_excludes']       = [
            'description'       => __('Limit response to objects excluding specific usernames.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['email_includes']          = [
            'description'       => __('Limit response to objects including emails.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['email_excludes']          = [
            'description'       => __('Limit response to objects excluding emails.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['country_includes']        = [
            'description'       => __('Limit response to objects with specific countries.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['country_excludes']        = [
            'description'       => __('Limit response to objects excluding specific countries.', 'woocommerce'),
            'type'              => 'string',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['last_active_before']      = [
            'description'       => __('Limit response to objects last active before (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['last_active_after']       = [
            'description'       => __('Limit response to objects last active after (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['last_active_between']     = [
            'description'       => __('Limit response to objects last active between two given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'array',
            'validate_callback' => \Automattic\WooCommerce\Admin\API\Reports\TimeInterval::rest_validate_between_date_arg(...),
            'items'             => [
                'type' => 'string',
            ],
        ];
        $params['registered_before']       = [
            'description'       => __('Limit response to objects registered before (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['registered_after']        = [
            'description'       => __('Limit response to objects registered after (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['registered_between']      = [
            'description'       => __('Limit response to objects last active between two given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'array',
            'validate_callback' => \Automattic\WooCommerce\Admin\API\Reports\TimeInterval::rest_validate_between_date_arg(...),
            'items'             => [
                'type' => 'string',
            ],
        ];
        $params['orders_count_min']        = [
            'description'       => __('Limit response to objects with an order count greater than or equal to given integer.', 'woocommerce'),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['orders_count_max']        = [
            'description'       => __('Limit response to objects with an order count less than or equal to given integer.', 'woocommerce'),
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['orders_count_between']    = [
            'description'       => __('Limit response to objects with an order count between two given integers.', 'woocommerce'),
            'type'              => 'array',
            'validate_callback' => \Automattic\WooCommerce\Admin\API\Reports\TimeInterval::rest_validate_between_numeric_arg(...),
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['total_spend_min']         = [
            'description'       => __('Limit response to objects with a total order spend greater than or equal to given number.', 'woocommerce'),
            'type'              => 'number',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['total_spend_max']         = [
            'description'       => __('Limit response to objects with a total order spend less than or equal to given number.', 'woocommerce'),
            'type'              => 'number',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['total_spend_between']     = [
            'description'       => __('Limit response to objects with a total order spend between two given numbers.', 'woocommerce'),
            'type'              => 'array',
            'validate_callback' => \Automattic\WooCommerce\Admin\API\Reports\TimeInterval::rest_validate_between_numeric_arg(...),
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['avg_order_value_min']     = [
            'description'       => __('Limit response to objects with an average order spend greater than or equal to given number.', 'woocommerce'),
            'type'              => 'number',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['avg_order_value_max']     = [
            'description'       => __('Limit response to objects with an average order spend less than or equal to given number.', 'woocommerce'),
            'type'              => 'number',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['avg_order_value_between'] = [
            'description'       => __('Limit response to objects with an average order spend between two given numbers.', 'woocommerce'),
            'type'              => 'array',
            'validate_callback' => \Automattic\WooCommerce\Admin\API\Reports\TimeInterval::rest_validate_between_numeric_arg(...),
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['last_order_before']       = [
            'description'       => __('Limit response to objects with last order before (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['last_order_after']        = [
            'description'       => __('Limit response to objects with last order after (or at) a given ISO8601 compliant datetime.', 'woocommerce'),
            'type'              => 'string',
            'format'            => 'date-time',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        $params['customers']               = [
            'description'       => __('Limit result to items with specified customer ids.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['fields']                  = [
            'description'       => __('Limit stats fields to the specified items.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_slug_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'string',
            ],
        ];
        $params['force_cache_refresh']     = [
            'description'       => __('Force retrieval of fresh data instead of from the cache.', 'woocommerce'),
            'type'              => 'boolean',
            'sanitize_callback' => 'wp_validate_boolean',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }
}
