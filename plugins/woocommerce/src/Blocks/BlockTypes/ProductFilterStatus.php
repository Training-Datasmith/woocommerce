<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

use Automattic\Woo_Commerce\Blocks\Block_Types\Product_Collection\Utils as ProductCollectionUtils;
use Automattic\Woo_Commerce\Internal\Product_Filters\Filter_Data_Provider;
use Automattic\Woo_Commerce\Internal\Product_Filters\Query_Clauses;
/**
 * Product Filter: Status Block.
 */
final class Product_Filter_Status extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-filter-status';
    public const STOCK_STATUS_QUERY_VAR = 'filter_stock_status';
    /**
     * Initialize this block type.
     *
     * - Hook into WP lifecycle.
     * - Register the block with WordPress.
     */
    protected function initialize(): void
    {
        parent::initialize();
        add_filter('woocommerce_blocks_product_filters_selected_items', $this->prepare_selected_filters(...), 10, 2);
    }
    /**
     * Prepare the active filter items.
     *
     * @param array $items  The active filter items.
     * @param array $params The query param parsed from the URL.
     * @return array Active filters items.
     */
    public function prepare_selected_filters($items, array $params)
    {
        $status_options = array_merge(
            wc_get_product_stock_status_options(),
            // On sale and Featured status are declared here.
            []
        );
        if (empty($params[self::STOCK_STATUS_QUERY_VAR])) {
            return $items;
        }
        $active_statuses = array_filter(array_map(trim(...), explode(',', (string) $params[self::STOCK_STATUS_QUERY_VAR])), fn($status) => array_key_exists($status, $status_options));
        if (empty($active_statuses)) {
            return $items;
        }
        foreach ($active_statuses as $status) {
            $items[] = [
                'type' => 'status',
                'value' => $status,
                // translators: %s: status.
                'activeLabel' => sprintf(__('Status: %s', 'woocommerce'), $status_options[$status]),
            ];
        }
        return $items;
    }
    /**
     * Extra data passed through from server to client for block.
     *
     * @param array $stock_statuses  Any stock statuses that currently are available from the block.
     *                               Note, this will be empty in the editor context when the block is
     *                               not in the post content on editor load.
     */
    protected function enqueue_data(array $stock_statuses = []): void
    {
        parent::enqueue_data($stock_statuses);
        $this->asset_data_registry->add('stockStatusOptions', wc_get_product_stock_status_options());
        $this->asset_data_registry->add('hideOutOfStockItems', 'yes' === get_option('woocommerce_hide_out_of_stock_items'));
    }
    /**
     * Include and render the block.
     *
     * @param array    $attributes Block attributes. Default empty array.
     * @param string   $content    Block content. Default empty string.
     * @param WP_Block $block      Block instance.
     * @return string Rendered block type output.
     */
    protected function render($attributes, $content, $block): string
    {
        // don't render if its admin, or ajax in progress.
        if (is_admin() || wp_doing_ajax()) {
            return '';
        }
        $stock_status_data = $this->get_stock_status_counts($block);
        $stock_statuses = wc_get_product_stock_status_options();
        $filter_params = $block->context['filterParams'] ?? [];
        $query = $filter_params[self::STOCK_STATUS_QUERY_VAR] ?? '';
        $selected_stock_statuses = array_filter(explode(',', $query));
        $filter_options = array_map(fn(array $item) => ['label' => $stock_statuses[$item['status']], 'value' => $item['status'], 'selected' => in_array($item['status'], $selected_stock_statuses, true), 'count' => $item['count'], 'type' => 'status'], $stock_status_data);
        $filter_context = ['items' => array_values($filter_options), 'showCounts' => $attributes['showCounts'] ?? false, 'groupLabel' => __('Status', 'woocommerce')];
        $wrapper_attributes = ['data-wp-interactive' => 'woocommerce/product-filters', 'data-wp-key' => wp_unique_prefixed_id($this->get_full_block_name()), 'data-wp-context' => wp_json_encode([
            /* translators: {{label}} is the status filter item label. */
            'activeLabelTemplate' => __('Status: {{label}}', 'woocommerce'),
            'filterType' => 'status',
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)];
        if (empty($filter_options)) {
            $wrapper_attributes['hidden'] = true;
            $wrapper_attributes['class'] = 'wc-block-product-filter--hidden';
        }
        return sprintf('<div %1$s>%2$s</div>', get_block_wrapper_attributes($wrapper_attributes), array_reduce($block->parsed_block['innerBlocks'], fn(string $carry, $parsed_block): string => $carry . (new \WP_Block($parsed_block, ['filterData' => $filter_context]))->render(), ''));
    }
    /**
     * Retrieve the status filter data for current block.
     *
     * @param WP_Block $block Block instance.
     * @return array{status: mixed, count: int<1, max>}[]
     */
    private function get_stock_status_counts($block): array
    {
        if (!isset($block->context['filterParams'])) {
            return [];
        }
        $query_vars = Product_Collection_Utils::get_query_vars($block, 1);
        unset($query_vars['filter_stock_status']);
        if (isset($query_vars['taxonomy']) && str_contains($query_vars['taxonomy'], 'pa_')) {
            unset($query_vars['taxonomy'], $query_vars['term']);
        }
        if (!empty($query_vars['meta_query'])) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            $query_vars['meta_query'] = Product_Collection_Utils::remove_query_array($query_vars['meta_query'], 'key', '_stock_status');
        }
        $container = wc_get_container();
        $counts = $container->get(Filter_Data_Provider::class)->with($container->get(Query_Clauses::class))->get_stock_status_counts($query_vars, array_keys(wc_get_product_stock_status_options()));
        $data = [];
        foreach ($counts as $key => $value) {
            $data[] = ['status' => $key, 'count' => intval($value)];
        }
        return array_filter($data, fn(array $stock_count) => $stock_count['count'] > 0);
    }
    /**
     * Disable the editor style handle for this block type.
     */
    protected function get_block_type_editor_style(): null
    {
        return null;
    }
    /**
     * Disable the script handle for this block type. We use block.json to load the script.
     *
     * @param string|null $key The key of the script to get.
     */
    protected function get_block_type_script($key = null): null
    {
        return null;
    }
}