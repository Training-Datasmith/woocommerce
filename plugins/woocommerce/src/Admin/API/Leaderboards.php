<?php

declare (strict_types=1);
/**
 * REST API Leaderboards Controller
 *
 * Handles requests to /leaderboards
 */
namespace Automattic\Woo_Commerce\Admin\API;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\API\Reports\Categories\Data_Store as CategoriesDataStore;
use Automattic\Woo_Commerce\Admin\API\Reports\Coupons\Data_Store as CouponsDataStore;
use Automattic\Woo_Commerce\Admin\API\Reports\Customers\Data_Store as CustomersDataStore;
use Automattic\Woo_Commerce\Admin\API\Reports\Products\Data_Store as ProductsDataStore;
/**
 * Leaderboards controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Leaderboards extends \WC_REST_Data_Controller
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
    protected $rest_base = 'leaderboards';
    /**
     * Register routes.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_items(...), 'permission_callback' => $this->get_items_permissions_check(...), 'args' => $this->get_collection_params()], 'schema' => [$this, 'get_public_item_schema']]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/allowed', [['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_allowed_items(...), 'permission_callback' => $this->get_items_permissions_check(...)], 'schema' => $this->get_public_allowed_item_schema(...)]);
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<leaderboard>\w+)', ['args' => ['leaderboard' => ['type' => 'string', 'enum' => ['customers', 'coupons', 'categories', 'products']]], ['methods' => \WP_REST_Server::READABLE, 'callback' => $this->get_items(...), 'permission_callback' => $this->get_items_permissions_check(...), 'args' => $this->get_collection_params()], 'schema' => [$this, 'get_public_item_schema']]);
    }
    /**
     * Get the data for the coupons leaderboard.
     *
     * @param int    $per_page Number of rows.
     * @param string $after Items after date.
     * @param string $before Items before date.
     * @param string $persisted_query URL query string.
     */
    protected function get_coupons_leaderboard($per_page, $after, $before, $persisted_query)
    {
        $coupons_data_store = new Coupons_Data_Store();
        $coupons_data = $per_page > 0 ? $coupons_data_store->get_data(apply_filters('woocommerce_analytics_coupons_query_args', ['orderby' => 'orders_count', 'order' => 'desc', 'after' => $after, 'before' => $before, 'per_page' => $per_page, 'extended_info' => true]))->data : [];
        $rows = [];
        foreach ($coupons_data as $coupon) {
            $url_query = wp_parse_args(['filter' => 'single_coupon', 'coupons' => $coupon['coupon_id']], $persisted_query);
            $coupon_url = wc_admin_url('/analytics/coupons', $url_query);
            $coupon_code = isset($coupon['extended_info']) && isset($coupon['extended_info']['code']) ? $coupon['extended_info']['code'] : '';
            $rows[] = [['display' => "<a href='{$coupon_url}'>{$coupon_code}</a>", 'value' => $coupon_code], ['display' => wc_admin_number_format($coupon['orders_count']), 'value' => $coupon['orders_count'], 'format' => 'number'], ['display' => wc_price($coupon['amount']), 'value' => $coupon['amount'], 'format' => 'currency']];
        }
        return ['id' => 'coupons', 'label' => __('Top Coupons - Number of Orders', 'woocommerce'), 'headers' => [['label' => __('Coupon code', 'woocommerce')], ['label' => __('Orders', 'woocommerce')], ['label' => __('Amount discounted', 'woocommerce')]], 'rows' => $rows];
    }
    /**
     * Get the data for the categories leaderboard.
     *
     * @param int    $per_page Number of rows.
     * @param string $after Items after date.
     * @param string $before Items before date.
     * @param string $persisted_query URL query string.
     */
    protected function get_categories_leaderboard($per_page, $after, $before, $persisted_query)
    {
        $categories_data_store = new Categories_Data_Store();
        $categories_data = $per_page > 0 ? $categories_data_store->get_data(apply_filters('woocommerce_analytics_categories_query_args', ['orderby' => 'items_sold', 'order' => 'desc', 'after' => $after, 'before' => $before, 'per_page' => $per_page, 'extended_info' => true]))->data : [];
        $rows = [];
        foreach ($categories_data as $category) {
            $url_query = wp_parse_args(['filter' => 'single_category', 'categories' => $category['category_id']], $persisted_query);
            $category_url = wc_admin_url('/analytics/categories', $url_query);
            $category_name = isset($category['extended_info']) && isset($category['extended_info']['name']) ? $category['extended_info']['name'] : '';
            $rows[] = [['display' => "<a href='{$category_url}'>{$category_name}</a>", 'value' => $category_name], ['display' => wc_admin_number_format($category['items_sold']), 'value' => $category['items_sold'], 'format' => 'number'], ['display' => wc_price($category['net_revenue']), 'value' => $category['net_revenue'], 'format' => 'currency']];
        }
        return ['id' => 'categories', 'label' => __('Top categories - Items sold', 'woocommerce'), 'headers' => [['label' => __('Category', 'woocommerce')], ['label' => __('Items sold', 'woocommerce')], ['label' => __('Net sales', 'woocommerce')]], 'rows' => $rows];
    }
    /**
     * Get the data for the customers leaderboard.
     *
     * @param int    $per_page Number of rows.
     * @param string $after Items after date.
     * @param string $before Items before date.
     * @param string $persisted_query URL query string.
     */
    protected function get_customers_leaderboard($per_page, $after, $before, $persisted_query)
    {
        $customers_data_store = new Customers_Data_Store();
        $customers_data = $per_page > 0 ? $customers_data_store->get_data(apply_filters('woocommerce_analytics_customers_query_args', ['orderby' => 'total_spend', 'order' => 'desc', 'order_after' => $after, 'order_before' => $before, 'per_page' => $per_page]))->data : [];
        $rows = [];
        foreach ($customers_data as $customer) {
            $url_query = wp_parse_args(['filter' => 'single_customer', 'customers' => $customer['id']], $persisted_query);
            $customer_url = wc_admin_url('/analytics/customers', $url_query);
            $rows[] = [['display' => "<a href='{$customer_url}'>{$customer['name']}</a>", 'value' => $customer['name']], ['display' => wc_admin_number_format($customer['orders_count']), 'value' => $customer['orders_count'], 'format' => 'number'], ['display' => wc_price($customer['total_spend']), 'value' => $customer['total_spend'], 'format' => 'currency']];
        }
        return ['id' => 'customers', 'label' => __('Top Customers - Total Spend', 'woocommerce'), 'headers' => [['label' => __('Customer Name', 'woocommerce')], ['label' => __('Orders', 'woocommerce')], ['label' => __('Total Spend', 'woocommerce')]], 'rows' => $rows];
    }
    /**
     * Get the data for the products leaderboard.
     *
     * @param int    $per_page Number of rows.
     * @param string $after Items after date.
     * @param string $before Items before date.
     * @param string $persisted_query URL query string.
     */
    protected function get_products_leaderboard($per_page, $after, $before, $persisted_query)
    {
        $products_data_store = new Products_Data_Store();
        $products_data = $per_page > 0 ? $products_data_store->get_data(apply_filters('woocommerce_analytics_products_query_args', ['orderby' => 'items_sold', 'order' => 'desc', 'after' => $after, 'before' => $before, 'per_page' => $per_page, 'extended_info' => true]))->data : [];
        $rows = [];
        foreach ($products_data as $product) {
            $url_query = wp_parse_args(['filter' => 'single_product', 'products' => $product['product_id']], $persisted_query);
            $product_url = wc_admin_url('/analytics/products', $url_query);
            $product_name = isset($product['extended_info']) && isset($product['extended_info']['name']) ? $product['extended_info']['name'] : '';
            $rows[] = [['display' => "<a href='{$product_url}'>{$product_name}</a>", 'value' => $product_name], ['display' => wc_admin_number_format($product['items_sold']), 'value' => $product['items_sold'], 'format' => 'number'], ['display' => wc_price($product['net_revenue']), 'value' => $product['net_revenue'], 'format' => 'currency']];
        }
        return ['id' => 'products', 'label' => __('Top products - Items sold', 'woocommerce'), 'headers' => [['label' => __('Product', 'woocommerce')], ['label' => __('Items sold', 'woocommerce')], ['label' => __('Net sales', 'woocommerce')]], 'rows' => $rows];
    }
    /**
     * Get an array of all leaderboards.
     *
     * @param int    $per_page Number of rows.
     * @param string $after Items after date.
     * @param string $before Items before date.
     * @param string $persisted_query URL query string.
     * @return array
     */
    public function get_leaderboards($per_page, $after, $before, $persisted_query)
    {
        $leaderboards = [$this->get_customers_leaderboard($per_page, $after, $before, $persisted_query), $this->get_coupons_leaderboard($per_page, $after, $before, $persisted_query), $this->get_categories_leaderboard($per_page, $after, $before, $persisted_query), $this->get_products_leaderboard($per_page, $after, $before, $persisted_query)];
        return apply_filters('woocommerce_leaderboards', $leaderboards, $per_page, $after, $before, $persisted_query);
    }
    /**
     * Return all leaderboards.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        $persisted_query = json_decode((string) $request['persisted_query'], true);
        $leaderboards = match ($request['leaderboard']) {
            'customers' => [$this->get_customers_leaderboard($request['per_page'], $request['after'], $request['before'], $persisted_query)],
            'coupons' => [$this->get_coupons_leaderboard($request['per_page'], $request['after'], $request['before'], $persisted_query)],
            'categories' => [$this->get_categories_leaderboard($request['per_page'], $request['after'], $request['before'], $persisted_query)],
            'products' => [$this->get_products_leaderboard($request['per_page'], $request['after'], $request['before'], $persisted_query)],
            default => $this->get_leaderboards($request['per_page'], $request['after'], $request['before'], $persisted_query),
        };
        $data = [];
        if (!empty($leaderboards)) {
            foreach ($leaderboards as $leaderboard) {
                $response = $this->prepare_item_for_response($leaderboard, $request);
                $data[] = $this->prepare_response_for_collection($response);
            }
        }
        return rest_ensure_response($data);
    }
    /**
     * Returns a list of allowed leaderboards.
     *
     * @param  WP_REST_Request $request Request data.
     * @return array|WP_Error
     */
    public function get_allowed_items($request)
    {
        $leaderboards = $this->get_leaderboards(0, null, null, null);
        $data = [];
        foreach ($leaderboards as $leaderboard) {
            $data[] = (object) ['id' => $leaderboard['id'], 'label' => $leaderboard['label'], 'headers' => $leaderboard['headers']];
        }
        $objects = [];
        foreach ($data as $item) {
            $prepared = $this->prepare_item_for_response($item, $request);
            $objects[] = $this->prepare_response_for_collection($prepared);
        }
        $response = rest_ensure_response($objects);
        $response->header('X-WP-Total', count($data));
        $response->header('X-WP-TotalPages', 1);
        add_query_arg($request->get_query_params(), rest_url(sprintf('/%s/%s', $this->namespace, $this->rest_base)));
        return $response;
    }
    /**
     * Prepare the data object for response.
     *
     * @param object          $item Data object.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($item, $request)
    {
        $data = $this->add_additional_fields_to_object($item, $request);
        $data = $this->filter_response_by_context($data, 'view');
        $response = rest_ensure_response($data);
        /**
         * Filter the list returned from the API.
         *
         * @param WP_REST_Response $response The response object.
         * @param array            $item     The original item.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_leaderboard', $response, $item, $request);
    }
    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params()
    {
        $params = [];
        $params['page'] = ['description' => __('Current page of the collection.', 'woocommerce'), 'type' => 'integer', 'default' => 1, 'sanitize_callback' => 'absint', 'validate_callback' => 'rest_validate_request_arg', 'minimum' => 1];
        $params['per_page'] = ['description' => __('Maximum number of items to be returned in result set.', 'woocommerce'), 'type' => 'integer', 'default' => 5, 'minimum' => 1, 'maximum' => 20, 'sanitize_callback' => 'absint', 'validate_callback' => 'rest_validate_request_arg'];
        $params['after'] = ['description' => __('Limit response to resources published after a given ISO8601 compliant date.', 'woocommerce'), 'type' => 'string', 'format' => 'date-time', 'validate_callback' => 'rest_validate_request_arg'];
        $params['before'] = ['description' => __('Limit response to resources published before a given ISO8601 compliant date.', 'woocommerce'), 'type' => 'string', 'format' => 'date-time', 'validate_callback' => 'rest_validate_request_arg'];
        $params['persisted_query'] = ['description' => __('URL query to persist across links.', 'woocommerce'), 'type' => 'string', 'validate_callback' => 'rest_validate_request_arg'];
        return $params;
    }
    /**
     * Get the schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'leaderboard', 'type' => 'object', 'properties' => ['id' => ['type' => 'string', 'description' => __('Leaderboard ID.', 'woocommerce'), 'context' => ['view'], 'readonly' => true], 'label' => ['type' => 'string', 'description' => __('Displayed title for the leaderboard.', 'woocommerce'), 'context' => ['view'], 'readonly' => true], 'headers' => ['type' => 'array', 'description' => __('Table headers.', 'woocommerce'), 'context' => ['view'], 'readonly' => true, 'items' => ['type' => 'array', 'properties' => ['label' => ['description' => __('Table column header.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true]]]], 'rows' => ['type' => 'array', 'description' => __('Table rows.', 'woocommerce'), 'context' => ['view'], 'readonly' => true, 'items' => ['type' => 'array', 'properties' => ['display' => ['description' => __('Table cell display.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'value' => ['description' => __('Table cell value.', 'woocommerce'), 'type' => 'string', 'context' => ['view', 'edit'], 'readonly' => true], 'format' => ['description' => __('Table cell format.', 'woocommerce'), 'type' => 'string', 'context' => ['view'], 'enum' => ['currency', 'number'], 'readonly' => true, 'required' => false]]]]]];
        return $this->add_additional_fields_schema($schema);
    }
    /**
     * Get schema for the list of allowed leaderboards.
     *
     * @return array $schema
     */
    public function get_public_allowed_item_schema()
    {
        $schema = $this->get_public_item_schema();
        unset($schema['properties']['rows']);
        return $schema;
    }
}