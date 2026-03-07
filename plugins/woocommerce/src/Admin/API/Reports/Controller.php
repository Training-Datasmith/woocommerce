<?php

declare(strict_types=1);
/**
 * REST API Reports controller extended to handle requests to the reports endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined('ABSPATH') || exit;

/**
 * Reports controller class.
 *
 * Controller that handles the endpoint that returns all available analytics endpoints.
 *
 * @internal
 * @extends GenericController
 */
class Controller extends GenericController
{
    use OrderAwareControllerTrait;

    /**
     * Get all reports.
     *
     * @param WP_REST_Request $request Request data.
     * @return array|WP_Error
     */
    public function get_items($request)
    {
        $data    = [];
        $reports = [
            [
                'slug'        => 'performance-indicators',
                'description' => __('Batch endpoint for getting specific performance indicators from `stats` endpoints.', 'woocommerce'),
            ],
            [
                'slug'        => 'revenue/stats',
                'description' => __('Stats about revenue.', 'woocommerce'),
            ],
            [
                'slug'        => 'orders/stats',
                'description' => __('Stats about orders.', 'woocommerce'),
            ],
            [
                'slug'        => 'products',
                'description' => __('Products detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'products/stats',
                'description' => __('Stats about products.', 'woocommerce'),
            ],
            [
                'slug'        => 'variations',
                'description' => __('Variations detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'variations/stats',
                'description' => __('Stats about variations.', 'woocommerce'),
            ],
            [
                'slug'        => 'categories',
                'description' => __('Product categories detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'categories/stats',
                'description' => __('Stats about product categories.', 'woocommerce'),
            ],
            [
                'slug'        => 'coupons',
                'description' => __('Coupons detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'coupons/stats',
                'description' => __('Stats about coupons.', 'woocommerce'),
            ],
            [
                'slug'        => 'taxes',
                'description' => __('Taxes detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'taxes/stats',
                'description' => __('Stats about taxes.', 'woocommerce'),
            ],
            [
                'slug'        => 'downloads',
                'description' => __('Product downloads detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'downloads/files',
                'description' => __('Product download files detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'downloads/stats',
                'description' => __('Stats about product downloads.', 'woocommerce'),
            ],
            [
                'slug'        => 'customers',
                'description' => __('Customers detailed reports.', 'woocommerce'),
            ],
            [
                'slug'        => 'customers/stats',
                'description' => __('Stats about groups of customers.', 'woocommerce'),
            ],
        ];

        /**
         * Filter the list of allowed reports, so that data can be loaded from third party extensions in addition to WooCommerce core.
         * Array items should be in format of array( 'slug' => 'downloads/stats', 'description' =>  '',
         * 'url' => '', and 'path' => '/wc-ext/v1/...'.
         *
         * @param array $endpoints The list of allowed reports..
         */
        $reports = apply_filters('woocommerce_admin_reports', $reports);

        foreach ($reports as $report) {
            // Silently skip non-compliant reports. Like the ones for WC_Admin_Reports::get_reports().
            if (empty($report['slug'])) {
                continue;
            }

            if (empty($report['path'])) {
                $report['path'] = '/' . $this->namespace . '/reports/' . $report['slug'];
            }

            // Allows a different admin page to be loaded here,
            // or allows an empty url if no report exists for a set of performance indicators.
            if (! isset($report['url'])) {
                if (str_ends_with((string) $report['slug'], '/stats')) {
                    $url_slug = substr((string) $report['slug'], 0, -6);
                } else {
                    $url_slug = $report['slug'];
                }

                $report['url'] = '/analytics/' . $url_slug;
            }

            $item   = $this->prepare_item_for_response((object) $report, $request);
            $data[] = $this->prepare_response_for_collection($item);
        }

        return rest_ensure_response($data);
    }

    /**
     * Prepare a report object for serialization.
     *
     * @param stdClass        $report  Report data.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function prepare_item_for_response($report, $request)
    {
        $data = [
            'slug'        => $report->slug,
            'description' => $report->description,
            'path'        => $report->path,
        ];

        // Wrap the data in a response object.
        $response = parent::prepare_item_for_response($data, $request);
        $response->add_links(
            [
                'self'       => [
                    'href' => rest_url($report->path),
                ],
                'report'     => [
                    'href' => $report->url,
                ],
                'collection' => [
                    'href' => rest_url(sprintf('%s/%s', $this->namespace, $this->rest_base)),
                ],
            ]
        );

        /**
         * Filter a report returned from the API.
         *
         * Allows modification of the report data right before it is returned.
         *
         * @param WP_REST_Response $response The response object.
         * @param object           $report   The original report object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_report', $response, $report, $request);
    }

    /**
     * Get the Report's schema, conforming to JSON Schema.
     *
     * @override WP_REST_Controller::get_item_schema()
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report',
            'type'       => 'object',
            'properties' => [
                'slug'        => [
                    'description' => __('An alphanumeric identifier for the resource.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'description' => [
                    'description' => __('A human-readable description of the resource.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
                ],
                'path'        => [
                    'description' => __('API path.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view' ],
                    'readonly'    => true,
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
        return [
            'context' => $this->get_context_param([ 'default' => 'view' ]),
        ];
    }
}
