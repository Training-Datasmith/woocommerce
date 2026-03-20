<?php

declare (strict_types=1);
/**
 * REST API Performance indicators controller
 *
 * Handles requests to the /reports/store-performance endpoint.
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports\Performance_Indicators;

use Automattic\Woo_Commerce\Admin\API\Reports\Generic_Controller;
use Automattic\Woo_Commerce\Admin\API\Reports\Time_Interval;
use WP_REST_Request;
use WP_REST_Response;
defined('ABSPATH') || exit;
/**
 * REST API Reports Performance indicators controller class.
 *
 * @internal
 * @extends GenericController
 */
class Controller extends Generic_Controller
{
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/performance-indicators';
    /**
     * Contains a list of endpoints by report slug.
     *
     * @var array
     */
    protected $endpoints = [];
    /**
     * Contains a list of active Jetpack module slugs.
     *
     * @var array
     */
    protected $active_jetpack_modules;
    /**
     * Contains a list of allowed stats.
     *
     * @var array
     */
    protected $allowed_stats = [];
    /**
     * Contains a list of stat labels.
     *
     * @var array
     */
    protected $labels = [];
    /**
     * Contains a list of endpoints by url.
     *
     * @var array
     */
    protected $urls = [];
    /**
     * Contains a cache of retrieved stats data, grouped by report slug.
     *
     * @var array
     */
    protected $stats_data = [];
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('woocommerce_rest_performance_indicators_data_value', $this->format_data_value(...), 10, 5);
    }
    /**
     * Register the routes for reports.
     */
    public function register_routes(): void
    {
        parent::register_routes();
        register_rest_route($this->namespace, '/' . $this->rest_base . '/allowed', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_allowed_items(...), 'permission_callback' => $this->get_items_permissions_check(...), 'args' => $this->get_collection_params()], 'schema' => $this->get_public_allowed_item_schema(...)]);
    }
    /**
     * Maps query arguments from the REST request.
     *
     * @param array $request Request array.
     */
    protected function prepare_reports_query($request): array
    {
        $args = [];
        $args['before'] = $request['before'];
        $args['after'] = $request['after'];
        $args['stats'] = $request['stats'];
        return $args;
    }
    /**
     * Get analytics report data and endpoints.
     */
    private function get_analytics_report_data()
    {
        $request = new \WP_REST_Request('GET', '/wc-analytics/reports');
        /**
         * Performance hack to strip the `rel=self` link from the report response as it is built by the Reports/Controller
         * to avoid the expensive calls to WP_REST_Server::get_target_hints_for_link().
         *
         * @param WP_REST_Response $response The response object.
         *
         * @return mixed
         */
        $remove_self_link_from_prepared_internal_response = function ($response) {
            if (is_callable([$response, 'remove_link'])) {
                $response->remove_link('self');
            }
            return $response;
        };
        add_filter('woocommerce_rest_prepare_report', $remove_self_link_from_prepared_internal_response);
        $response = rest_do_request($request);
        remove_filter('woocommerce_rest_prepare_report', $remove_self_link_from_prepared_internal_response);
        if (is_wp_error($response)) {
            return $response;
        }
        if (200 !== $response->get_status()) {
            return new \WP_Error('woocommerce_analytics_performance_indicators_result_failed', __('Sorry, fetching performance indicators failed.', 'woocommerce'));
        }
        $endpoints = $response->get_data();
        foreach ($endpoints as $endpoint) {
            if (str_ends_with((string) $endpoint['slug'], '/stats')) {
                $request = new \WP_REST_Request('OPTIONS', $endpoint['path']);
                $response = rest_do_request($request);
                if (is_wp_error($response)) {
                    return $response;
                }
                $data = $response->get_data();
                $prefix = substr((string) $endpoint['slug'], 0, -6);
                if (empty($data['schema']['properties']['totals']['properties'])) {
                    continue;
                }
                foreach ($data['schema']['properties']['totals']['properties'] as $property_key => $schema_info) {
                    if (empty($schema_info['indicator'])) {
                        continue;
                    }
                    if (!$schema_info['indicator']) {
                        continue;
                    }
                    $stat = $prefix . '/' . $property_key;
                    $this->allowed_stats[] = $stat;
                    $stat_label = empty($schema_info['title']) ? $schema_info['description'] : $schema_info['title'];
                    $this->labels[$stat] = trim((string) $stat_label, '.');
                    $this->formats[$stat] = $schema_info['format'] ?? 'number';
                }
                $this->endpoints[$prefix] = $endpoint['path'];
                $this->urls[$prefix] = $endpoint['_links']['report'][0]['href'];
            }
        }
    }
    /**
     * Get active Jetpack modules.
     *
     * @return array List of active Jetpack module slugs.
     */
    private function get_active_jetpack_modules()
    {
        if (is_null($this->active_jetpack_modules)) {
            if (class_exists('\Jetpack') && method_exists('\Jetpack', 'get_active_modules')) {
                $active_modules = \Jetpack::get_active_modules();
                $this->active_jetpack_modules = is_array($active_modules) ? $active_modules : [];
            } else {
                $this->active_jetpack_modules = [];
            }
        }
        return $this->active_jetpack_modules;
    }
    /**
     * Set active Jetpack modules.
     *
     * @internal
     * @param array $modules List of active Jetpack module slugs.
     */
    public function set_active_jetpack_modules($modules): void
    {
        $this->active_jetpack_modules = $modules;
    }
    /**
     * Get active Jetpack modules and endpoints.
     */
    private function get_jetpack_modules_data(): void
    {
        $active_modules = $this->get_active_jetpack_modules();
        if (empty($active_modules)) {
            return;
        }
        $items = apply_filters('woocommerce_rest_performance_indicators_jetpack_items', ['stats/visitors' => ['label' => __('Visitors', 'woocommerce'), 'permission' => 'view_stats', 'format' => 'number', 'module' => 'stats'], 'stats/views' => ['label' => __('Views', 'woocommerce'), 'permission' => 'view_stats', 'format' => 'number', 'module' => 'stats']]);
        foreach ($items as $item_key => $item) {
            if (!in_array($item['module'], $active_modules, true)) {
                return;
            }
            if ($item['permission'] && !current_user_can($item['permission'])) {
                return;
            }
            $stat = 'jetpack/' . $item_key;
            $endpoint = 'jetpack/' . $item['module'];
            $this->allowed_stats[] = $stat;
            $this->labels[$stat] = $item['label'];
            $this->endpoints[$endpoint] = '/jetpack/v4/module/' . $item['module'] . '/data';
            $this->formats[$stat] = $item['format'];
        }
        $this->urls['jetpack/stats'] = '/jetpack';
    }
    /**
     * Get information such as allowed stats, stat labels, and endpoint data from stats reports.
     *
     * @return WP_Error|True
     */
    private function get_indicator_data(): bool
    {
        // Data already retrieved.
        if (!empty($this->endpoints) && !empty($this->labels) && !empty($this->allowed_stats)) {
            return true;
        }
        $this->get_analytics_report_data();
        $this->get_jetpack_modules_data();
        return true;
    }
    /**
     * Returns a list of allowed performance indicators.
     *
     * @param  WP_REST_Request $request Request data.
     * @return array|WP_Error
     */
    public function get_allowed_items($request)
    {
        $indicator_data = $this->get_indicator_data();
        if (is_wp_error($indicator_data)) {
            return $indicator_data;
        }
        $data = [];
        foreach ($this->allowed_stats as $stat) {
            $pieces = $this->get_stats_parts($stat);
            $report = $pieces[0];
            $chart = $pieces[1];
            $data[] = (object) ['stat' => $stat, 'chart' => $chart, 'label' => $this->labels[$stat]];
        }
        usort($data, $this->sort(...));
        $objects = [];
        foreach ($data as $item) {
            $prepared = $this->prepare_item_for_response($item, $request);
            $objects[] = $this->prepare_response_for_collection($prepared);
        }
        return $this->add_pagination_headers($request, $objects, count($data), 1, 1);
    }
    /**
     * Sorts the list of stats. Sorted by custom arrangement.
     *
     * @internal
     * @see https://github.com/woocommerce/woocommerce-admin/issues/1282
     * @param object $a First item.
     * @param object $b Second item.
     * @return order
     */
    public function sort($a, $b)
    {
        /**
         * Custom ordering for store performance indicators.
         *
         * @see https://github.com/woocommerce/woocommerce-admin/issues/1282
         * @param array $indicators A list of ordered indicators.
         */
        $stat_order = apply_filters('woocommerce_rest_report_sort_performance_indicators', ['revenue/total_sales', 'revenue/net_revenue', 'orders/orders_count', 'orders/avg_order_value', 'products/items_sold', 'revenue/refunds', 'coupons/orders_count', 'coupons/amount', 'taxes/total_tax', 'taxes/order_tax', 'taxes/shipping_tax', 'revenue/shipping', 'downloads/download_count']);
        $a = array_search($a->stat, $stat_order, true);
        $b = array_search($b->stat, $stat_order, true);
        if (false === $a && false === $b) {
            return 0;
        }
        if (false === $a) {
            return 1;
        }
        if (false === $b) {
            return -1;
        }
        return $a - $b;
    }
    /**
     * Get report stats data, avoiding duplicate requests for stats that use the same endpoint.
     *
     * @param string $report Report slug to request data for.
     * @param array  $query_args Report query args.
     * @return WP_REST_Response|WP_Error Report stats data.
     */
    private function get_stats_data($report, array $query_args)
    {
        // Return from cache if we've already requested these report stats.
        if (isset($this->stats_data[$report])) {
            return $this->stats_data[$report];
        }
        // Request the report stats.
        $request_url = $this->endpoints[$report];
        $request = new \WP_REST_Request('GET', $request_url);
        $request->set_param('before', $query_args['before']);
        $request->set_param('after', $query_args['after']);
        $response = rest_do_request($request);
        // Cache the response.
        $this->stats_data[$report] = $response;
        return $response;
    }
    /**
     * Get all reports.
     *
     * @param  WP_REST_Request $request Request data.
     * @return array|WP_Error
     */
    public function get_items($request)
    {
        $indicator_data = $this->get_indicator_data();
        if (is_wp_error($indicator_data)) {
            return $indicator_data;
        }
        $query_args = $this->prepare_reports_query($request);
        if (empty($query_args['stats'])) {
            return new \WP_Error('woocommerce_analytics_performance_indicators_empty_query', __('A list of stats to query must be provided.', 'woocommerce'), 400);
        }
        $stats = [];
        foreach ($query_args['stats'] as $stat) {
            $is_error = false;
            $pieces = $this->get_stats_parts($stat);
            $report = $pieces[0];
            $chart = $pieces[1];
            if (!in_array($stat, $this->allowed_stats, true)) {
                continue;
            }
            $response = $this->get_stats_data($report, $query_args);
            if (is_wp_error($response)) {
                return $response;
            }
            $data = $response->get_data();
            $format = $this->formats[$stat];
            $label = $this->labels[$stat];
            if (200 !== $response->get_status()) {
                $stats[] = (object) ['stat' => $stat, 'chart' => $chart, 'label' => $label, 'format' => $format, 'value' => null];
                continue;
            }
            $stats[] = (object) ['stat' => $stat, 'chart' => $chart, 'label' => $label, 'format' => $format, 'value' => apply_filters('woocommerce_rest_performance_indicators_data_value', $data, $stat, $report, $chart, $query_args)];
        }
        usort($stats, $this->sort(...));
        $objects = [];
        foreach ($stats as $stat) {
            $data = $this->prepare_item_for_response($stat, $request);
            $objects[] = $this->prepare_response_for_collection($data);
        }
        $response = rest_ensure_response($objects);
        $response->header('X-WP-Total', count($stats));
        $response->header('X-WP-TotalPages', 1);
        add_query_arg($request->get_query_params(), rest_url(sprintf('/%s/%s', $this->namespace, $this->rest_base)));
        return $response;
    }
    /**
     * Prepare a report data item for serialization.
     *
     * @param array           $stat_data Report data item as returned from Data Store.
     * @param WP_REST_Request $request   Request object.
     * @return WP_REST_Response
     */
    public function prepare_item_for_response($stat_data, $request)
    {
        $response = parent::prepare_item_for_response($stat_data, $request);
        $response->add_links($this->prepare_links($stat_data));
        /**
         * Filter a report returned from the API.
         *
         * Allows modification of the report data right before it is returned.
         *
         * @param WP_REST_Response $response The response object.
         * @param object           $report   The original report object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_report_performance_indicators', $response, $stat_data, $request);
    }
    /**
     * Prepare links for the request.
     *
     * @param object $object data.
     * @return array
     */
    protected function prepare_links($object)
    {
        $pieces = $this->get_stats_parts($object->stat);
        $endpoint = $pieces[0];
        $url = $this->urls[$endpoint] ?? '';
        return ['api' => ['href' => rest_url($this->endpoints[$endpoint])], 'report' => ['href' => $url]];
    }
    /**
     * Returns the endpoint part of a stat request (prefix) and the actual stat total we want.
     * To allow extensions to namespace (example: fue/emails/sent), we break on the last forward slash.
     *
     * @param string $full_stat A stat request string like orders/avg_order_value or fue/emails/sent.
     * @return array Containing the prefix (endpoint) and suffix (stat).
     */
    private function get_stats_parts($full_stat): array
    {
        $endpoint = substr($full_stat, 0, strrpos($full_stat, '/'));
        $stat = substr($full_stat, strrpos($full_stat, '/') + 1);
        return [$endpoint, $stat];
    }
    /**
     * Format the data returned from the API for given stats.
     *
     * @param array  $data Data from external endpoint.
     * @param string $stat Name of the stat.
     * @param string $report Name of the report.
     * @param string $chart Name of the chart.
     * @param array  $query_args Query args.
     * @return mixed
     */
    public function format_data_value($data, $stat, $report, $chart, $query_args)
    {
        if ('jetpack/stats' === $report) {
            $index = false;
            // Get the index of the field to tally.
            if (isset($data['general']->visits->fields) && is_array($data['general']->visits->fields)) {
                $index = array_search($chart, $data['general']->visits->fields, true);
            }
            if (!$index) {
                return null;
            }
            // Loop over provided data and filter by the queried date.
            // Note that this is currently limited to 30 days via the Jetpack API
            // but the WordPress.com endpoint allows up to 90 days.
            $total = 0;
            $before = gmdate('Y-m-d', strtotime($query_args['before'] ?? Time_Interval::default_before()));
            $after = gmdate('Y-m-d', strtotime($query_args['after'] ?? Time_Interval::default_after()));
            foreach ($data['general']->visits->data as $datum) {
                if ($datum[0] >= $after && $datum[0] <= $before) {
                    $total += $datum[$index];
                }
            }
            return $total;
        }
        if (isset($data['totals']) && isset($data['totals'][$chart])) {
            return $data['totals'][$chart];
        }
        return null;
    }
    /**
     * Get the Report's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $indicator_data = $this->get_indicator_data();
        if (is_wp_error($indicator_data)) {
            $allowed_stats = [];
        } else {
            $allowed_stats = $this->allowed_stats;
        }
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'report_performance_indicator', 'type' => 'object', 'properties' => ['stat' => ['description' => __('Unique identifier for the resource.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true, 'enum' => $allowed_stats], 'chart' => ['description' => __('The specific chart this stat referrers to.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'label' => ['description' => __('Human readable label for the stat.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'format' => ['description' => __('Format of the stat.', 'woocommerce'), 'type' => 'number', 'context' => ['view', 'edit'], 'readonly' => true, 'enum' => ['number', 'currency']], 'value' => ['description' => __('Value of the stat. Returns null if the stat does not exist or cannot be loaded.', 'woocommerce'), 'type' => 'number', 'context' => ['view', 'edit'], 'readonly' => true]]];
        return $this->add_additional_fields_schema($schema);
    }
    /**
     * Get schema for the list of allowed performance indicators.
     *
     * @return array $schema
     */
    public function get_public_allowed_item_schema()
    {
        $schema = $this->get_public_item_schema();
        unset($schema['properties']['value']);
        unset($schema['properties']['format']);
        return $schema;
    }
    /**
     * Get the query params for collections.
     */
    public function get_collection_params(): array
    {
        $indicator_data = $this->get_indicator_data();
        if (is_wp_error($indicator_data)) {
            $allowed_stats = __('There was an issue loading the report endpoints', 'woocommerce');
        } else {
            $allowed_stats = implode(', ', $this->allowed_stats);
        }
        $params = [];
        $params['context'] = $this->get_context_param(['default' => 'view']);
        $params['stats'] = ['description' => sprintf(
            /* translators: Allowed values is a list of stat endpoints. */
            __('Limit response to specific report stats. Allowed values: %s.', 'woocommerce'),
            $allowed_stats
        ), 'type' => 'array', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['type' => 'string', 'enum' => $this->allowed_stats], 'default' => $this->allowed_stats];
        $params['after'] = ['description' => __('Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce'), 'type' => 'string', 'format' => 'date-time', 'validate_callback' => 'rest_validate_request_arg'];
        $params['before'] = ['description' => __('Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce'), 'type' => 'string', 'format' => 'date-time', 'validate_callback' => 'rest_validate_request_arg'];
        return $params;
    }
}