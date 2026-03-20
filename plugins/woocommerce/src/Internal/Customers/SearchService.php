<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Customers;

use Automattic\Woo_Commerce\Internal\Data_Stores\Orders\Orders_Table_Data_Store;
use Automattic\Woo_Commerce\Utilities\Order_Util;
/**
 * Internal API for searching users/customers: no backward compatibility obligation.
 */
final class Search_Service
{
    /**
     * Searches users having the billing email (when applicable lookup orders as well) as specified and returns their id.
     *
     * @param string[] $emails Emails to search for.
     *
     * @return int[]
     */
    public function find_user_ids_by_billing_email_for_coupons_usage_lookup(array $emails): array
    {
        $emails = array_unique(array_map(strtolower(...), array_map(sanitize_email(...), $emails)));
        $include_user_ids = [];
        if (Order_Util::custom_orders_table_usage_is_enabled()) {
            global $wpdb;
            // phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $placeholders = implode(', ', array_fill(0, count($emails), '%s'));
            $include_user_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT customer_id FROM %i WHERE billing_email IN ({$placeholders})", Orders_Table_Data_Store::get_orders_table_name(), ...$emails));
            // phpcs:enable
            if ([] === $include_user_ids) {
                return [];
            }
        }
        $users_query = new \WP_User_Query(['fields' => 'ID', 'include' => $include_user_ids, 'meta_query' => [
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
            ['key' => 'billing_email', 'value' => $emails, 'compare' => 'IN'],
        ]]);
        return array_map(intval(...), array_unique($users_query->get_results()));
    }
}