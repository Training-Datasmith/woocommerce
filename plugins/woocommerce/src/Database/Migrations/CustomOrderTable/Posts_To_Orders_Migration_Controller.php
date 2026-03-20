<?php

declare (strict_types=1);
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */
namespace Automattic\Woo_Commerce\Database\Migrations\Custom_Order_Table;

use Automattic\Woo_Commerce\Internal\Data_Stores\Orders\Custom_Orders_Table_Controller;
use Automattic\Woo_Commerce\Internal\Data_Stores\Orders\Orders_Table_Data_Store;
use Automattic\Woo_Commerce\Utilities\Array_Util;
use Automattic\Woo_Commerce\Utilities\Order_Util;
/**
 * This is the main class used to perform the complete migration of orders
 * from the posts table to the custom orders table.
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
 */
class Posts_To_Orders_Migration_Controller
{
    /**
     * Error logger for migration errors.
     *
     * @var \WC_Logger
     */
    private object $error_logger;
    /**
     * Array of objects used to perform the migration.
     *
     * @var \Automattic\WooCommerce\Database\Migrations\TableMigrator[]
     */
    private array $all_migrators;
    /**
     * The source name to use for logs.
     */
    public const LOGS_SOURCE_NAME = 'posts-to-orders-migration';
    /**
     * PostsToOrdersMigrationController constructor.
     */
    public function __construct()
    {
        $this->all_migrators = [];
        $this->all_migrators['order'] = new Post_To_Order_Table_Migrator();
        $this->all_migrators['order_address_billing'] = new Post_To_Order_Address_Table_Migrator('billing');
        $this->all_migrators['order_address_shipping'] = new Post_To_Order_Address_Table_Migrator('shipping');
        $this->all_migrators['order_operational_data'] = new Post_To_Order_Op_Table_Migrator();
        $this->all_migrators['order_meta'] = new Post_Meta_To_Order_Meta_Migrator($this->get_migrated_meta_keys());
        $this->error_logger = wc_get_logger();
    }
    /**
     * Helper method to get migrated keys for all the tables in this controller.
     *
     * @return string[] Array of meta keys.
     */
    public function get_migrated_meta_keys(): array
    {
        $migrated_meta_keys = [];
        foreach ($this->all_migrators as $migrator) {
            if (method_exists($migrator, 'get_meta_column_config')) {
                $migrated_meta_keys = array_merge($migrated_meta_keys, $migrator->get_meta_column_config());
            }
        }
        return array_keys($migrated_meta_keys);
    }
    /**
     * Migrates a set of orders from the posts table to the custom orders tables.
     *
     * @param array $order_post_ids List of post IDs of the orders to migrate.
     */
    public function migrate_orders(array $order_post_ids): void
    {
        $this->error_logger = WC()->call_function('wc_get_logger');
        $data = [];
        try {
            foreach ($this->all_migrators as $name => $migrator) {
                $data[$name] = $migrator->fetch_sanitized_migration_data($order_post_ids);
                if (!empty($data[$name]['errors'])) {
                    $this->handle_migration_error($order_post_ids, $data[$name]['errors'], null, null, $name);
                    return;
                }
            }
        } catch (\Exception $e) {
            $this->handle_migration_error($order_post_ids, $data, $e, null, 'Fetching data');
            return;
        }
        $using_transactions = $this->maybe_start_transaction();
        foreach ($this->all_migrators as $name => $migrator) {
            $results = $migrator->process_migration_data($data[$name]);
            $errors = array_unique($results['errors']);
            $exception = $results['exception'];
            if (null === $exception && empty($errors)) {
                continue;
            }
            $this->handle_migration_error($order_post_ids, $errors, $exception, $using_transactions, $name);
            return;
        }
        if ($using_transactions) {
            $this->commit_transaction();
        }
        $this->maybe_clear_order_datastore_cache_for_ids($order_post_ids);
    }
    /**
     * Log migration errors if any.
     *
     * @param array           $order_post_ids List of post IDs of the orders to migrate.
     * @param array           $errors List of errors to log.
     * @param \Exception|null $exception Exception to log.
     * @param bool|null       $using_transactions Whether transactions were used.
     * @param string          $name Name of the migrator.
     */
    private function handle_migration_error(array $order_post_ids, array $errors, ?\Exception $exception, ?bool $using_transactions, string $name): void
    {
        $batch = Array_Util::to_ranges_string($order_post_ids);
        if (null !== $exception) {
            $exception_class = $exception::class;
            $this->error_logger->error("{$name}: when processing ids {$batch}: ({$exception_class}) {$exception->get_message()}, {$exception->get_trace_as_string()}", ['source' => self::LOGS_SOURCE_NAME, 'ids' => $order_post_ids, 'exception' => $exception]);
        }
        foreach ($errors as $error) {
            $this->error_logger->error("{$name}: when processing ids {$batch}: {$error}", ['source' => self::LOGS_SOURCE_NAME, 'ids' => $order_post_ids, 'error' => $error]);
        }
        if ($using_transactions) {
            $this->rollback_transaction();
        } else {
            $this->maybe_clear_order_datastore_cache_for_ids($order_post_ids);
        }
    }
    /**
     * Clear the cache of order data for modified orders during migration if cache is enabled.
     *
     * @param array $order_post_ids List of order IDs of the orders to clear from cache.
     */
    private function maybe_clear_order_datastore_cache_for_ids(array $order_post_ids): void
    {
        if (Order_Util::custom_orders_table_datastore_cache_enabled()) {
            $orders_table_datastore = wc_get_container()->get(Orders_Table_Data_Store::class);
            if (is_callable($orders_table_datastore->clear_cached_data(...))) {
                $orders_table_datastore->clear_cached_data($order_post_ids);
            }
        }
    }
    /**
     * Start a database transaction if the configuration mandates so.
     *
     * @return bool|null True if transaction started, false if transactions won't be used, null if transaction failed to start.
     *
     * @throws \Exception If the transaction isolation level is invalid.
     */
    private function maybe_start_transaction(): ?bool
    {
        $use_transactions = get_option(Custom_Orders_Table_Controller::USE_DB_TRANSACTIONS_OPTION, 'yes');
        if ('yes' !== $use_transactions) {
            return null;
        }
        $transaction_isolation_level = get_option(Custom_Orders_Table_Controller::DB_TRANSACTIONS_ISOLATION_LEVEL_OPTION, Custom_Orders_Table_Controller::DEFAULT_DB_TRANSACTIONS_ISOLATION_LEVEL);
        $valid_transaction_isolation_levels = ['READ UNCOMMITTED', 'READ COMMITTED', 'REPEATABLE READ', 'SERIALIZABLE'];
        if (!in_array($transaction_isolation_level, $valid_transaction_isolation_levels, true)) {
            throw new \Exception("Invalid database transaction isolation level name {$transaction_isolation_level}");
        }
        $set_transaction_isolation_level_command = "SET TRANSACTION ISOLATION LEVEL {$transaction_isolation_level}";
        // We suppress errors in transaction isolation level setting because it's not supported by all DB engines, additionally, this might be executing in context of another transaction with a different isolation level.
        if (!$this->db_query($set_transaction_isolation_level_command, true)) {
            return null;
        }
        return $this->db_query('START TRANSACTION') ? true : null;
    }
    /**
     * Commit the current database transaction.
     *
     * @return bool True on success, false on error.
     */
    private function commit_transaction(): bool
    {
        return $this->db_query('COMMIT');
    }
    /**
     * Rollback the current database transaction.
     *
     * @return bool True on success, false on error.
     */
    private function rollback_transaction(): bool
    {
        return $this->db_query('ROLLBACK');
    }
    /**
     * Execute a database query and log any errors.
     *
     * @param string $query          The SQL query to execute.
     * @param bool   $supress_errors Whether to suppress errors.
     *
     * @return bool True if the query succeeded, false if there were errors.
     */
    private function db_query(string $query, bool $supress_errors = false): bool
    {
        $wpdb = WC()->get_global('wpdb');
        try {
            if ($supress_errors) {
                $suppress = $wpdb->suppress_errors(true);
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query($query);
            if ($supress_errors) {
                $wpdb->suppress_errors($suppress);
            }
        } catch (\Exception $exception) {
            $exception_class = $exception::class;
            $this->error_logger->error("PostsToOrdersMigrationController: when executing {$query}: ({$exception_class}) {$exception->get_message()}, {$exception->get_trace_as_string()}", ['source' => self::LOGS_SOURCE_NAME, 'exception' => $exception]);
            return false;
        }
        $error = $wpdb->last_error;
        if ('' !== $error) {
            $this->error_logger->error("PostsToOrdersMigrationController: when executing {$query}: {$error}", ['source' => self::LOGS_SOURCE_NAME, 'error' => $error]);
            return false;
        }
        return true;
    }
    /**
     * Verify whether the given order IDs were migrated properly or not.
     *
     * @param array $order_post_ids Order IDs.
     *
     * @return array Array of failed IDs along with columns.
     */
    public function verify_migrated_orders(array $order_post_ids): array
    {
        $errors = [];
        foreach ($this->all_migrators as $migrator) {
            if (method_exists($migrator, 'verify_migrated_data')) {
                $errors = $errors + $migrator->verify_migrated_data($order_post_ids);
            }
        }
        return $errors;
    }
    /**
     * Migrates an order from the posts table to the custom orders tables.
     *
     * @param int $order_post_id Post ID of the order to migrate.
     */
    public function migrate_order(int $order_post_id): void
    {
        $this->migrate_orders([$order_post_id]);
    }
}