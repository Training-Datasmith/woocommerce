<?php

declare (strict_types=1);
/**
 * REST API Reports products controller
 *
 * Handles requests to the /reports/products endpoint.
 */
namespace Automattic\Woo_Commerce\Admin\API\Reports\Variations;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\API\Reports\Exportable_Interface;
use Automattic\Woo_Commerce\Admin\API\Reports\Exportable_Traits;
use Automattic\Woo_Commerce\Admin\API\Reports\Generic_Controller;
use Automattic\Woo_Commerce\Admin\API\Reports\Generic_Query;
use Automattic\Woo_Commerce\Admin\API\Reports\Order_Aware_Controller_Trait;
/**
 * REST API Reports products controller class.
 *
 * @internal
 * @extends GenericController
 */
class Controller extends Generic_Controller implements Exportable_Interface
{
    // The controller does not use this trait. It's here for API backward compatibility.
    use Order_Aware_Controller_Trait;
    /**
     * Exportable traits.
     */
    use Exportable_Traits;
    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'reports/variations';
    /**
     * Mapping between external parameter name and name used in query class.
     *
     * @var array
     */
    protected $param_mapping = ['variations' => 'variation_includes', 'products' => 'product_includes'];
    /**
     * Get data from `'variations'` GenericQuery.
     *
     * @override GenericController::get_datastore_data()
     *
     * @param array $query_args Query arguments.
     * @return mixed Results from the data store.
     */
    protected function get_datastore_data($query_args = [])
    {
        $query = new Generic_Query($query_args, 'variations');
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
        // Wrap the data in a response object.
        $response = parent::prepare_item_for_response($report, $request);
        $response->add_links($this->prepare_links($report));
        /**
         * Filter a report returned from the API.
         *
         * Allows modification of the report data right before it is returned.
         *
         * @since 6.5.0
         *
         * @param WP_REST_Response $response The response object.
         * @param object           $report   The original report object.
         * @param WP_REST_Request  $request  Request used to generate the response.
         */
        return apply_filters('woocommerce_rest_prepare_report_variations', $response, $report, $request);
    }
    /**
     * Maps query arguments from the REST request.
     *
     * @param array $request Request array.
     */
    protected function prepare_reports_query($request): array
    {
        $args = [];
        /**
         * Experimental: Filter the list of parameters provided when querying data from the data store.
         *
         * @ignore
         *
         * @param array $collection_params List of parameters.
         *
         * @since 6.5.0
         */
        $collection_params = apply_filters('experimental_woocommerce_analytics_variations_collection_params', $this->get_collection_params());
        $registered = array_keys($collection_params);
        foreach ($registered as $param_name) {
            if (isset($request[$param_name])) {
                if (isset($this->param_mapping[$param_name])) {
                    $args[$this->param_mapping[$param_name]] = $request[$param_name];
                } else {
                    $args[$param_name] = $request[$param_name];
                }
            }
        }
        return $args;
    }
    /**
     * Prepare links for the request.
     *
     * @param array $object Object data.
     * @return array        Links for the given post.
     */
    protected function prepare_links($object)
    {
        return ['product' => ['href' => rest_url(sprintf('/%s/%s/%d', $this->namespace, 'products', $object['product_id']))], 'variation' => ['href' => rest_url(sprintf('/%s/%s/%d/%s/%d', $this->namespace, 'products', $object['product_id'], 'variation', $object['variation_id']))]];
    }
    /**
     * Get the Report's schema, conforming to JSON Schema.
     *
     * @return array
     */
    public function get_item_schema()
    {
        $schema = ['$schema' => 'http://json-schema.org/draft-04/schema#', 'title' => 'report_varitations', 'type' => 'object', 'properties' => ['product_id' => ['type' => 'integer', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product ID.', 'woocommerce')], 'variation_id' => ['type' => 'integer', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product ID.', 'woocommerce')], 'items_sold' => ['type' => 'integer', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Number of items sold.', 'woocommerce')], 'net_revenue' => ['type' => 'number', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Total Net sales of all items sold.', 'woocommerce')], 'orders_count' => ['type' => 'integer', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Number of orders product appeared in.', 'woocommerce')], 'extended_info' => ['name' => ['type' => 'string', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product name.', 'woocommerce')], 'price' => ['type' => 'number', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product price.', 'woocommerce')], 'image' => ['type' => 'string', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product image.', 'woocommerce')], 'permalink' => ['type' => 'string', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product link.', 'woocommerce')], 'attributes' => ['type' => 'array', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product attributes.', 'woocommerce')], 'stock_status' => ['type' => 'string', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product inventory status.', 'woocommerce')], 'stock_quantity' => ['type' => 'integer', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product inventory quantity.', 'woocommerce')], 'low_stock_amount' => ['type' => 'integer', 'readonly' => true, 'context' => ['view', 'edit'], 'description' => __('Product inventory threshold for low stock.', 'woocommerce')]]]];
        return $this->add_additional_fields_schema($schema);
    }
    /**
     * Get the query params for collections.
     *
     * @return array
     */
    public function get_collection_params()
    {
        $params = parent::get_collection_params();
        $params['orderby']['enum'] = $this->apply_custom_orderby_filters(['date', 'net_revenue', 'orders_count', 'items_sold', 'sku']);
        $params['match'] = ['description' => __('Indicates whether all the conditions should be true for the resulting set, or if any one of them is sufficient. Match affects the following parameters: status_is, status_is_not, product_includes, product_excludes, coupon_includes, coupon_excludes, customer, categories', 'woocommerce'), 'type' => 'string', 'default' => 'all', 'enum' => ['all', 'any'], 'validate_callback' => 'rest_validate_request_arg'];
        $params['product_includes'] = ['description' => __('Limit result set to items that have the specified parent product(s).', 'woocommerce'), 'type' => 'array', 'items' => ['type' => 'integer'], 'default' => [], 'sanitize_callback' => 'wp_parse_id_list', 'validate_callback' => 'rest_validate_request_arg'];
        $params['product_excludes'] = ['description' => __('Limit result set to items that don\'t have the specified parent product(s).', 'woocommerce'), 'type' => 'array', 'items' => ['type' => 'integer'], 'default' => [], 'validate_callback' => 'rest_validate_request_arg', 'sanitize_callback' => 'wp_parse_id_list'];
        $params['variations'] = ['description' => __('Limit result to items with specified variation ids.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_id_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['type' => 'integer']];
        $params['extended_info'] = ['description' => __('Add additional piece of info about each variation to the report.', 'woocommerce'), 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'wc_string_to_bool', 'validate_callback' => 'rest_validate_request_arg'];
        $params['attribute_is'] = ['description' => __('Limit result set to variations that include the specified attributes.', 'woocommerce'), 'type' => 'array', 'items' => ['type' => 'array'], 'default' => [], 'validate_callback' => 'rest_validate_request_arg'];
        $params['attribute_is_not'] = ['description' => __('Limit result set to variations that don\'t include the specified attributes.', 'woocommerce'), 'type' => 'array', 'items' => ['type' => 'array'], 'default' => [], 'validate_callback' => 'rest_validate_request_arg'];
        $params['category_includes'] = ['description' => __('Limit result set to variations in the specified categories.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_id_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['type' => 'integer']];
        $params['category_excludes'] = ['description' => __('Limit result set to variations not in the specified categories.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_id_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['type' => 'integer']];
        $params['products'] = ['description' => __('Limit result to items with specified product ids.', 'woocommerce'), 'type' => 'array', 'sanitize_callback' => 'wp_parse_id_list', 'validate_callback' => 'rest_validate_request_arg', 'items' => ['type' => 'integer']];
        return $params;
    }
    /**
     * Get stock status column export value.
     *
     * @param array $status Stock status from report row.
     * @return string
     */
    protected function get_stock_status($status)
    {
        $statuses = wc_get_product_stock_status_options();
        return $statuses[$status] ?? '';
    }
    /**
     * Get the column names for export.
     *
     * @return array Key value pair of Column ID => Label.
     */
    public function get_export_columns()
    {
        $export_columns = ['product_name' => __('Product / Variation title', 'woocommerce'), 'sku' => __('SKU', 'woocommerce'), 'items_sold' => __('Items sold', 'woocommerce'), 'net_revenue' => __('N. Revenue', 'woocommerce'), 'orders_count' => __('Orders', 'woocommerce')];
        if ('yes' === get_option('woocommerce_manage_stock')) {
            $export_columns['stock_status'] = __('Status', 'woocommerce');
            $export_columns['stock'] = __('Stock', 'woocommerce');
        }
        return $export_columns;
    }
    /**
     * Get the column values for export.
     *
     * @param array $item Single report item/row.
     * @return array Key value pair of Column ID => Row Value.
     */
    public function prepare_item_for_export($item)
    {
        $product_name = $item['extended_info']['name'];
        /**
         * Filter the separator used in the product variation title.
         *
         * @since 10.2.0
         * @param string $separator The separator.
         * @param \WC_Product $product The product object.
         * @return string The separator.
         */
        $separator = apply_filters('woocommerce_product_variation_title_attributes_separator', ' - ', new \WC_Product());
        if (!empty($item['extended_info']['attributes']) && !str_contains((string) $product_name, (string) $separator)) {
            $attributes = [];
            foreach ($item['extended_info']['attributes'] as $attribute) {
                if (empty($attribute['option'])) {
                    // translators: %s: the attribute name.
                    $attributes[] = sprintf(__('Any %s', 'woocommerce'), ucfirst((string) $attribute['name']));
                } else {
                    $attributes[] = $attribute['option'];
                }
            }
            $product_name .= $separator . implode(', ', $attributes);
        }
        $export_item = ['product_name' => $product_name, 'sku' => $item['extended_info']['sku'], 'items_sold' => $item['items_sold'], 'net_revenue' => self::csv_number_format($item['net_revenue']), 'orders_count' => $item['orders_count']];
        if ('yes' === get_option('woocommerce_manage_stock')) {
            $export_item['stock_status'] = $this->get_stock_status($item['extended_info']['stock_status']);
            $export_item['stock'] = $item['extended_info']['stock_quantity'];
        }
        return $export_item;
    }
}