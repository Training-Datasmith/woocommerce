<?php

declare(strict_types=1);
/**
 * REST API Reports Export Controller
 *
 * Handles requests to:
 * - /reports/[report]/export
 * - /reports/[report]/export/[id]/status
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Export;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Admin\ReportExporter;

/**
 * Reports Export controller.
 *
 * @internal
 * @extends \Automattic\WooCommerce\Admin\API\Reports\Controller
 */
class Controller extends \Automattic\WooCommerce\Admin\API\Reports\Controller
{
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/(?P<type>[a-z]+)/export';

    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => $this->export_items(...),
                    'permission_callback' => $this->get_items_permissions_check(...),
                    'args'                => $this->get_export_collection_params(),
                ],
                'schema' => $this->get_export_public_schema(...),
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<export_id>[a-z0-9]+)/status',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => $this->export_status(...),
                    'permission_callback' => $this->get_items_permissions_check(...),
                ],
                'schema' => $this->get_export_status_public_schema(...),
            ]
        );
    }

    /**
     * Get the query params for collections.
     *
     * @return array
     */
    protected function get_export_collection_params()
    {
        $params                = [];
        $params['report_args'] = [
            'description'       => __('Parameters to pass on to the exported report.', 'woocommerce'),
            'type'              => 'object',
            'validate_callback' => 'rest_validate_request_arg', // @todo: use each controller's schema?
        ];
        $params['email']       = [
            'description'       => __('When true, email a link to download the export to the requesting user.', 'woocommerce'),
            'type'              => 'boolean',
            'validate_callback' => 'rest_validate_request_arg',
        ];
        return $params;
    }

    /**
     * Get the Report Export's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_export_public_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report_export',
            'type'       => 'object',
            'properties' => [
                'status'    => [
                    'description' => __('Export status.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'message'   => [
                    'description' => __('Export status message.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'export_id' => [
                    'description' => __('Export ID.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }

    /**
     * Get the Export status schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_export_status_public_schema()
    {
        $schema = [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'report_export_status',
            'type'       => 'object',
            'properties' => [
                'percent_complete' => [
                    'description' => __('Percentage complete.', 'woocommerce'),
                    'type'        => 'int',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'download_url'     => [
                    'description' => __('Export download URL.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];

        return $this->add_additional_fields_schema($schema);
    }

    /**
     * Export data based on user request params.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function export_items($request)
    {
        $report_type = $request['type'];
        $report_args = empty($request['report_args']) ? [] : $request['report_args'];
        $send_email  = $request['email'] ?? false;

        $default_export_id = str_replace('.', '', (string) microtime(true));
        $export_id         = apply_filters('woocommerce_admin_export_id', $default_export_id);
        $export_id         = (string) sanitize_file_name($export_id);

        $total_rows = ReportExporter::queue_report_export($export_id, $report_type, $report_args, $send_email);

        if (0 === $total_rows) {
            return rest_ensure_response(
                [
                    'message' => __('There is no data to export for the given request.', 'woocommerce'),
                ]
            );
        }

        ReportExporter::update_export_percentage_complete($report_type, $export_id, 0);

        $response = rest_ensure_response(
            [
                'message'   => __('Your report file is being generated.', 'woocommerce'),
                'export_id' => $export_id,
            ]
        );

        // Include a link to the export status endpoint.
        $response->add_links(
            [
                'status' => [
                    'href' => rest_url(sprintf('%s/reports/%s/export/%s/status', $this->namespace, $report_type, $export_id)),
                ],
            ]
        );

        $data = $this->prepare_response_for_collection($response);

        return rest_ensure_response($data);
    }

    /**
     * Export status based on user request params.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function export_status($request)
    {
        $report_type = $request['type'];
        $export_id   = $request['export_id'];
        $percentage  = ReportExporter::get_export_percentage_complete($report_type, $export_id);

        if (false === $percentage) {
            return new \WP_Error(
                'woocommerce_admin_reports_export_invalid_id',
                __('Sorry, there is no export with that ID.', 'woocommerce'),
                [ 'status' => 404 ]
            );
        }

        $result = [
            'percent_complete' => $percentage,
        ];

        // @todo - add thing in the links below instead?
        if (100 === $percentage) {
            $query_args = [
                'action'   => ReportExporter::DOWNLOAD_EXPORT_ACTION,
                'filename' => "wc-{$report_type}-report-export-{$export_id}",
            ];

            $result['download_url'] = add_query_arg($query_args, admin_url());
        }

        // Wrap the data in a response object.
        $response = rest_ensure_response($result);
        // Include a link to the export status endpoint.
        $response->add_links(
            [
                'self' => [
                    'href' => rest_url(sprintf('%s/reports/%s/export/%s/status', $this->namespace, $report_type, $export_id)),
                ],
            ]
        );

        $data = $this->prepare_response_for_collection($response);

        return rest_ensure_response($data);
    }
}
