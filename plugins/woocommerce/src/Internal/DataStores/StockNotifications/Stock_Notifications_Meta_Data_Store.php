<?php

/**
 * StockNotificationsMetaDataStore class file.
 */
declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Data_Stores\Stock_Notifications;

use Automattic\Woo_Commerce\Internal\Data_Stores\Custom_Meta_Data_Store;
defined('ABSPATH') || exit;
/**
 * Mimics a WP metadata (i.e. add_metadata(), get_metadata() and friends) implementation using a custom table.
 */
class Stock_Notifications_Meta_Data_Store extends Custom_Meta_Data_Store
{
    /**
     * Returns the name of the table used for storage.
     */
    public function get_table_name(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'wc_stock_notificationmeta';
    }
    /**
     * Returns the name of the field/column used for identifiying metadata entries.
     */
    protected function get_meta_id_field(): string
    {
        return 'id';
    }
    /**
     * Returns the name of the field/column used for associating meta with objects.
     */
    protected function get_object_id_field(): string
    {
        return 'notification_id';
    }
    /**
     * Delete by notification ID.
     *
     * @param int $notification_id The notification ID.
     * @return bool True if the metadata were deleted, false otherwise.
     */
    public function delete_by_notification_id($notification_id): bool
    {
        global $wpdb;
        $table = $this->get_table_name();
        $result = $wpdb->delete($table, ['notification_id' => $notification_id], ['%d']);
        return false === $result ? false : true;
    }
}