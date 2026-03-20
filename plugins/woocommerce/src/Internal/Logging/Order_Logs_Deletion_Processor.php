<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Logging;

use Automattic\Woo_Commerce\Internal\Batch_Processing\Batch_Processor_Interface;
use Automattic\Woo_Commerce\Internal\Data_Stores\Orders\Custom_Orders_Table_Controller;
use Automattic\Woo_Commerce\Internal\Data_Stores\Orders\Data_Synchronizer;
use Automattic\Woo_Commerce\Proxies\Legacy_Proxy;
use Automattic\Woo_Commerce\Utilities\String_Util;
/**
 * Batch processor for deleting log entries of completed orders.
 * It only works when either HPOS is enabled or the orders data store is the old CPT-based one,
 * because otherwise the ability to query orders by meta key is not guaranteed.
 */
class Order_Logs_Deletion_Processor implements Batch_Processor_Interface
{
    /**
     * Constant representing the default size of the batches to process.
     */
    public const DEFAULT_BATCH_SIZE = 1000;
    /**
     * True if HPOS is enabled.
     */
    private bool $hpos_in_use = false;
    /**
     * True if HPOS is disabled and the orders data store in use is the old CPT one.
     */
    private bool $cpt_in_use = false;
    /**
     * The instance of LegacyProxy to use.
     */
    private Legacy_Proxy $legacy_proxy;
    /**
     * The instance of DataSynchronizer to use.
     */
    private Data_Synchronizer $data_synchronizer;
    /**
     * Initialize the instance.
     * This is invoked by the dependency injection container.
     *
     * @param CustomOrdersTableController $hpos_controller The instance of CustomOrdersTableController to use.
     * @param LegacyProxy                 $legacy_proxy The instance of LegacyProxy to use.
     * @param DataSynchronizer            $data_synchronizer The instance of DataSynchronizer to use.
     *
     * @internal
     */
    final public function init(Custom_Orders_Table_Controller $hpos_controller, Legacy_Proxy $legacy_proxy, Data_Synchronizer $data_synchronizer): void
    {
        $this->hpos_in_use = $hpos_controller->custom_orders_table_usage_is_enabled();
        if (!$this->hpos_in_use) {
            $this->cpt_in_use = \WC_Order_Data_Store_CPT::class === \WC_Data_Store::load('order')->get_current_class_name();
        }
        $this->legacy_proxy = $legacy_proxy;
        $this->data_synchronizer = $data_synchronizer;
    }
    /**
     * Get the name of the processor.
     */
    public function get_name(): string
    {
        return 'Order logs deletion process';
    }
    /**
     * Get a description of the processor.
     */
    public function get_description(): string
    {
        return 'Deletes debug logs of completed orders.';
    }
    /**
     * Get the default batch size for this processor.
     */
    public function get_default_batch_size(): int
    {
        return self::DEFAULT_BATCH_SIZE;
    }
    /**
     * Get the total count of entries pending processing.
     */
    public function get_total_pending_count(): int
    {
        if ($this->hpos_in_use) {
            return $this->get_total_pending_count_hpos();
        }
        if ($this->cpt_in_use) {
            return $this->get_total_pending_count_cpt();
        }
        $this->throw_doing_it_wrong(String_Util::class_name_without_namespace(self::class) . '::' . __FUNCTION__);
        return 0;
    }
    /**
     * Get the total count of entries pending processing, HPOS version.
     */
    private function get_total_pending_count_hpos(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*)\n                 FROM {$wpdb->prefix}wc_orders_meta\n                 WHERE meta_key = %s", '_debug_log_source_pending_deletion'));
    }
    /**
     * Get the total count of entries pending processing, CPT datastore version.
     */
    private function get_total_pending_count_cpt(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*)\n                 FROM {$wpdb->postmeta} pm\n                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n                 WHERE pm.meta_key = %s\n                 AND p.post_type = %s", '_debug_log_source_pending_deletion', 'shop_order'));
    }
    /**
     * Get the next batch of items to process.
     * An item will be an associative array of 'order_id' and 'meta_value'.
     *
     * @param int $size Maximum size of the batch to return.
     */
    public function get_next_batch_to_process(int $size): array
    {
        if ($this->hpos_in_use) {
            return $this->get_next_batch_to_process_hpos($size);
        }
        if ($this->cpt_in_use) {
            return $this->get_next_batch_to_process_cpt($size);
        }
        $this->throw_doing_it_wrong(String_Util::class_name_without_namespace(self::class) . '::' . __FUNCTION__);
        return [];
    }
    /**
     * Get the next batch of items to process, HPOS version.
     *
     * @param int $size Maximum size of the batch to return.
     */
    private function get_next_batch_to_process_hpos(int $size): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT order_id, meta_value\n                 FROM {$wpdb->prefix}wc_orders_meta\n                 WHERE meta_key = %s\n                 ORDER BY order_id\n                 LIMIT %d", '_debug_log_source_pending_deletion', $size), ARRAY_A);
    }
    /**
     * Get the next batch of items to process, CPT datastore version.
     *
     * @param int $size Maximum size of the batch to return.
     */
    private function get_next_batch_to_process_cpt(int $size): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT p.ID as order_id, pm.meta_value\n                 FROM {$wpdb->postmeta} pm\n                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n                 WHERE pm.meta_key = %s\n                 AND p.post_type = 'shop_order'\n                 ORDER BY p.ID\n                 LIMIT %d", '_debug_log_source_pending_deletion', $size), ARRAY_A);
    }
    /**
     * Process a batch of items.
     * Items are expected to be in the format returned by get_next_batch_to_process.
     *
     * @param array $batch Batch of items to process.
     * @throws \Exception Invalid input.
     */
    public function process_batch(array $batch): void
    {
        if (empty($batch)) {
            return;
        }
        if (!$this->hpos_in_use && !$this->cpt_in_use) {
            $this->throw_doing_it_wrong(String_Util::class_name_without_namespace(self::class) . '::' . __FUNCTION__);
            return;
        }
        $logger = $this->legacy_proxy->call_function('wc_get_logger');
        foreach ($batch as $item) {
            if (!is_array($item) || !isset($item['meta_value']) || !isset($item['order_id'])) {
                throw new \Exception("\$batch must be an array of arrays, each having a 'meta_value' key and an 'order_id' key");
            }
            if ($logger instanceof \WC_Logger) {
                $logger->clear($item['meta_value']);
            }
        }
        $order_ids = array_map(absint(...), array_column($batch, 'order_id'));
        // Delete from the authoritative meta table.
        $this->delete_debug_log_source_meta_entries(true, $order_ids);
        if ($this->data_synchronizer->data_sync_is_enabled()) {
            // When HPOS data sync is enabled we need to manually delete the entries in the backup meta table too,
            // otherwise the next sync process will restore the rows we just deleted from the authoritative meta table.
            $this->delete_debug_log_source_meta_entries(false, $order_ids);
        }
    }
    /**
     * Delete meta entries for the given order IDs.
     *
     * @param bool  $from_authoritative_table True to delete from the authoritative table, false for the backup table.
     * @param array $order_ids Array of order IDs to delete.
     */
    private function delete_debug_log_source_meta_entries(bool $from_authoritative_table, array $order_ids): void
    {
        global $wpdb;
        $use_hpos_table = $this->hpos_in_use === $from_authoritative_table;
        $table_name = $use_hpos_table ? "{$wpdb->prefix}wc_orders_meta" : $wpdb->postmeta;
        $id_column_name = $use_hpos_table ? 'order_id' : 'post_id';
        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare("DELETE FROM {$table_name}\n\t\t\t\t WHERE {$id_column_name} IN ({$placeholders})\n\t\t\t\t AND meta_key = %s", array_merge($order_ids, ['_debug_log_source_pending_deletion'])));
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }
    /**
     * Throw a "doing it wrong" error.
     *
     * @param string $function_name Class and function name to include in the error.
     */
    private function throw_doing_it_wrong(string $function_name): void
    {
        $this->legacy_proxy->call_function('wc_doing_it_wrong', $function_name, "This processor shouldn't be enqueued when the orders data store in use is neither the HPOS one nor the CPT one. Just delete the order debug logs directly.", '10.3.0');
    }
}