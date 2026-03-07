<?php

declare(strict_types=1);
/**
 * REST API Reports coupons controller
 *
 * Handles requests to the /reports/coupons endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Coupons;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use Automattic\WooCommerce\Admin\API\Reports\GenericController;
use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports coupons controller class.
 *
 * @internal
 * @extends GenericController
 */
class Controller extends GenericController implements ExportableInterface
{
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/coupons';

    /**
     * Get data from `'coupons'` GenericQuery.
     *
     * @override GenericController::get_datastore_data()
     *
     * @param array $query_args Query arguments.
     * @return mixed Results from the data store.
     */
    protected function get_datastore_data($query_args = [])
    {
        $query = new GenericQuery($query_args, 'coupons');
        return $query->get_data();
    }

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
        $args['page']                = $request['page'];
        $args['per_page']            = $request['per_page'];
        $args['orderby']             = $request['orderby'];
        $args['order']               = $request['order'];
        $args['coupons']             = (array) $request['coupons'];
        $args['extended_info']       = $request['extended_info'];
        $args['force_cache_refresh'] = $request['force_cache_refresh'];
        return $args;
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
        $response = parent::prepare_item_for_response($report, $request);
        $response->add_links($this->prepare_links($report));

        /**
         * Filter a report returned from the API.
         *
         * Allows modification of the report data right before it is returned.
         *
         * @param WP_REST_Response $response The response object.
         * @param object           $report   The original report object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_report_coupons', $response, $report, $request);
    }

    /**
     * Prepare links for the request.
     *
     * @param WC_Reports_Query $object Object data.
     * @return array
     */
    protected function prepare_links($object)
    {
        return [
            'coupon' => [
                'href' => rest_url(sprintf('/%s/coupons/%d', $this->namespace, $object['coupon_id'])),
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
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report_coupons',
            'type'       => 'object',
            'properties' => [
                'coupon_id'     => [
                    'description' => __('Coupon ID.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'amount'        => [
                    'description' => __('Net discount amount.', 'woocommerce'),
                    'type'        => 'number',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'orders_count'  => [
                    'description' => __('Number of orders.', 'woocommerce'),
                    'type'        => 'integer',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'extended_info' => [
                    'code'             => [
                        'type'        => 'string',
                        'readonly'    => true,
                        'context'     => [ 'view', 'edit' ],
                        'description' => __('Coupon code.', 'woocommerce'),
                    ],
                    'date_created'     => [
                        'type'        => 'date-time',
                        'readonly'    => true,
                        'context'     => [ 'view', 'edit' ],
                        'description' => __('Coupon creation date.', 'woocommerce'),
                    ],
                    'date_created_gmt' => [
                        'type'        => 'date-time',
                        'readonly'    => true,
                        'context'     => [ 'view', 'edit' ],
                        'description' => __('Coupon creation date in GMT.', 'woocommerce'),
                    ],
                    'date_expires'     => [
                        'type'        => 'date-time',
                        'readonly'    => true,
                        'context'     => [ 'view', 'edit' ],
                        'description' => __('Coupon expiration date.', 'woocommerce'),
                    ],
                    'date_expires_gmt' => [
                        'type'        => 'date-time',
                        'readonly'    => true,
                        'context'     => [ 'view', 'edit' ],
                        'description' => __('Coupon expiration date in GMT.', 'woocommerce'),
                    ],
                    'discount_type'    => [
                        'type'        => 'string',
                        'readonly'    => true,
                        'context'     => [ 'view', 'edit' ],
                        'enum'        => array_keys(wc_get_coupon_types()),
                        'description' => __('Coupon discount type.', 'woocommerce'),
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
        $params                       = parent::get_collection_params();
        $params['orderby']['default'] = 'coupon_id';
        $params['orderby']['enum']    = $this->apply_custom_orderby_filters(
            [
                'coupon_id',
                'code',
                'amount',
                'orders_count',
            ]
        );
        $params['coupons']            = [
            'description'       => __('Limit result set to coupons assigned specific coupon IDs.', 'woocommerce'),
            'type'              => 'array',
            'sanitize_callback' => 'wp_parse_id_list',
            'validate_callback' => 'rest_validate_request_arg',
            'items'             => [
                'type' => 'integer',
            ],
        ];
        $params['extended_info']      = [
            'description'       => __('Add additional piece of info about each coupon to the report.', 'woocommerce'),
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'wc_string_to_bool',
            'validate_callback' => 'rest_validate_request_arg',
        ];

        return $params;
    }

    /**
     * Get the column names for export.
     *
     * @return array Key value pair of Column ID => Label.
     */
    public function get_export_columns()
    {
        $export_columns = [
            'code'         => __('Coupon code', 'woocommerce'),
            'orders_count' => __('Orders', 'woocommerce'),
            'amount'       => __('Amount discounted', 'woocommerce'),
            'created'      => __('Created', 'woocommerce'),
            'expires'      => __('Expires', 'woocommerce'),
            'type'         => __('Type', 'woocommerce'),
        ];

        /**
         * Filter to add or remove column names from the coupons report for
         * export.
         *
         * @since 1.6.0
         */
        return apply_filters(
            'woocommerce_report_coupons_export_columns',
            $export_columns
        );
    }

    /**
     * Get the column values for export.
     *
     * @param array $item Single report item/row.
     * @return array Key value pair of Column ID => Row Value.
     */
    public function prepare_item_for_export($item)
    {
        $date_expires = empty($item['extended_info']['date_expires'])
            ? __('N/A', 'woocommerce')
            : $item['extended_info']['date_expires'];

        $export_item = [
            'code'         => $item['extended_info']['code'],
            'orders_count' => $item['orders_count'],
            'amount'       => $item['amount'],
            'created'      => $item['extended_info']['date_created'],
            'expires'      => $date_expires,
            'type'         => $item['extended_info']['discount_type'],
        ];

        /**
         * Filter to prepare extra columns in the export item for the coupons
         * report.
         *
         * @since 1.6.0
         */
        return apply_filters(
            'woocommerce_report_coupons_prepare_export_item',
            $export_item,
            $item
        );
    }
}
