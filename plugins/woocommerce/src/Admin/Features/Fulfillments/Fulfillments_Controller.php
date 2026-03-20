<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Fulfillments;

use Automattic\Woo_Commerce\Admin\Features\Fulfillments\Data_Store\Fulfillments_Data_Store;
use Automattic\Woo_Commerce\Internal\Features\Features_Controller;
use Automattic\Woo_Commerce\Internal\Utilities\Database_Util;
/**
 * Class FulfillmentsController
 *
 * Base controller for fulfillments management.
 */
class Fulfillments_Controller
{
    /**
     * Provides the list of classes that this controller provides.
     *
     * @var string[]
     */
    private array $provides = [Fulfillments_Manager::class, Fulfillments_Renderer::class, Fulfillments_Settings::class, Order_Fulfillments_Rest_Controller::class];
    /**
     * Initialize the controller.
     */
    public function register(): void
    {
        add_filter('woocommerce_data_stores', $this->register_data_stores(...));
        add_action('init', $this->initialize_fulfillments(...), 10, 0);
    }
    /**
     * Register the fulfillments data store via the woocommerce_data_stores filter.
     *
     * This allows extensions to replace the data store with a custom implementation
     * by filtering 'woocommerce_data_stores' or 'woocommerce_order-fulfillment_data_store'.
     *
     * @param array $data_stores Data stores.
     * @return array
     */
    public function register_data_stores($data_stores)
    {
        if (!is_array($data_stores)) {
            return $data_stores;
        }
        $container = wc_get_container();
        $features_controller = $container->get(Features_Controller::class);
        // If fulfillments feature is not enabled, don't register the data store.
        if (!$features_controller->feature_is_enabled('fulfillments')) {
            return $data_stores;
        }
        $data_stores['order-fulfillment'] = Fulfillments_Data_Store::class;
        return $data_stores;
    }
    /**
     * Initialize the fulfillments controller.
     */
    public function initialize_fulfillments(): void
    {
        $container = wc_get_container();
        $features_controller = $container->get(Features_Controller::class);
        // If fulfillments feature is not enabled, do not add the DB tables, and don't register the controller.
        if (!$features_controller->feature_is_enabled('fulfillments')) {
            return;
        }
        // Create the database tables if they do not exist.
        $this->maybe_create_db_tables();
        // Register the classes that this controller provides.
        foreach ($this->provides as $class) {
            $class = $container->get($class);
            if (method_exists($class, 'register')) {
                $class->register();
            }
        }
    }
    /**
     * Create the database tables if they do not exist.
     */
    private function maybe_create_db_tables(): void
    {
        global $wpdb;
        if (get_option('woocommerce_fulfillments_db_tables_created', false)) {
            // The tables already exist, no need to create them again.
            return;
        }
        // Drop the tables if they exist, to ensure a clean slate.
        // If one table exists and the other does not, it will be an issue.
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wc_order_fulfillments");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wc_order_fulfillment_meta");
        // Bulk delete order fulfillment status meta from legacy and HPOS order tables.
        $this->bulk_delete_order_fulfillment_status_meta();
        $collate = '';
        $container = wc_get_container();
        $database_util = $container->get(Database_Util::class);
        $max_index_length = $database_util->get_max_index_length();
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        $schema = "CREATE TABLE {$wpdb->prefix}wc_order_fulfillments (\n\t\t\tfulfillment_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n\t\t\tentity_type varchar(255) NOT NULL,\n\t\t\tentity_id bigint(20) unsigned NOT NULL,\n\t\t\tstatus varchar(255) NOT NULL,\n\t\t\tis_fulfilled tinyint(1) NOT NULL DEFAULT 0,\n\t\t\tdate_updated datetime NOT NULL,\n\t\t\tdate_deleted datetime NULL,\n\t\t\tPRIMARY KEY (fulfillment_id),\n\t\t\tKEY entity_type_id (entity_type({$max_index_length}), entity_id)\n\t\t) {$collate};\n\t\tCREATE TABLE {$wpdb->prefix}wc_order_fulfillment_meta (\n\t\t\tmeta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,\n\t\t\tfulfillment_id bigint(20) unsigned NOT NULL,\n\t\t\tmeta_key varchar(255) NULL,\n\t\t\tmeta_value longtext NULL,\n\t\t\tdate_updated datetime NOT NULL,\n\t\t\tdate_deleted datetime NULL,\n\t\t\tPRIMARY KEY (meta_id),\n\t\t\tKEY meta_key (meta_key({$max_index_length})),\n\t\t\tKEY fulfillment_id (fulfillment_id)\n\t\t) {$collate};";
        $database_util->dbdelta($schema);
        // Update the option to indicate that the tables have been created.
        update_option('woocommerce_fulfillments_db_tables_created', true);
    }
    /**
     * Bulk delete fulfillment status meta for specific order IDs, or all orders if no order ID specified.
     *
     * This method deletes the fulfillment status meta for the specified order IDs from both the legacy postmeta table
     * and the HPOS meta table.
     *
     * @param array<int> $order_ids Array of order IDs to delete fulfillment status meta for.
     */
    private function bulk_delete_order_fulfillment_status_meta($order_ids = []): void
    {
        $this->delete_legacy_order_fulfillment_meta($order_ids);
        $this->delete_hpos_order_fulfillment_meta($order_ids);
    }
    /**
     * Delete fulfillment status meta from legacy postmeta table.
     *
     * @param array<int> $order_ids Array of order IDs to delete fulfillment status meta for.
     */
    private function delete_legacy_order_fulfillment_meta($order_ids = []): void
    {
        global $wpdb;
        if (!empty($order_ids)) {
            $order_params = array_merge(['_fulfillment_status'], $order_ids);
            $wpdb->query($wpdb->prepare("DELETE pm FROM {$wpdb->postmeta} pm\n\t\t\t\t\tINNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n\t\t\t\t\tWHERE p.post_type = 'shop_order'\n\t\t\t\t\tAND pm.meta_key = %s\n\t\t\t\t\tAND pm.post_id IN (" . implode(',', array_fill(0, count($order_ids), '%d')) . ')', ...$order_params));
        } else {
            $wpdb->query($wpdb->prepare("DELETE pm FROM {$wpdb->postmeta} pm\n\t\t\t\t\tINNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID\n\t\t\t\t\tWHERE p.post_type = 'shop_order'\n\t\t\t\t\tAND pm.meta_key = %s", '_fulfillment_status'));
        }
    }
    /**
     * Delete fulfillment status meta from HPOS meta table.
     *
     * @param array<int> $order_ids Array of order IDs to delete fulfillment status meta for.
     */
    private function delete_hpos_order_fulfillment_meta($order_ids = []): void
    {
        global $wpdb;
        // Check if HPOS meta table exists.
        $table_name = $wpdb->prefix . 'wc_orders_meta';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) !== $table_name) {
            return;
        }
        if (!empty($order_ids)) {
            $order_params = array_merge(['_fulfillment_status'], $order_ids);
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wc_orders_meta\n\t\t\t\t\tWHERE meta_key = %s\n\t\t\t\t\tAND order_id IN (" . implode(',', array_fill(0, count($order_ids), '%d')) . ')', ...$order_params));
        } else {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}wc_orders_meta\n\t\t\t\t\tWHERE meta_key = %s", '_fulfillment_status'));
        }
    }
}