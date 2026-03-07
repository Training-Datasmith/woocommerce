<?php

declare(strict_types=1);
/**
 * Class for WPPost To order table migrator.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\MetaToCustomTableMigrator;

/**
 * Helper class to migrate records from the WordPress post table
 * to the custom order table (and only that table - PostsToOrdersMigrationController
 * is used for fully migrating orders).
 */
class PostToOrderTableMigrator extends MetaToCustomTableMigrator
{
    /**
     * Get schema config for wp_posts and wc_order table.
     *
     * @return array Config.
     */
    protected function get_schema_config(): array
    {
        global $wpdb;

        $table_names = [
            'orders'    => $wpdb->prefix . 'wc_orders',
            'addresses' => $wpdb->prefix . 'wc_order_addresses',
            'op_data'   => $wpdb->prefix . 'wc_order_operational_data',
            'meta'      => $wpdb->prefix . 'wc_orders_meta',
        ];

        return [
            'source'      => [
                'entity' => [
                    'table_name'             => $wpdb->posts,
                    'meta_rel_column'        => 'ID',
                    'destination_rel_column' => 'ID',
                    'primary_key'            => 'ID',
                ],
                'meta'   => [
                    'table_name'        => $wpdb->postmeta,
                    'meta_id_column'    => 'meta_id',
                    'meta_key_column'   => 'meta_key',
                    'meta_value_column' => 'meta_value',
                    'entity_id_column'  => 'post_id',
                ],
            ],
            'destination' => [
                'table_name'        => $table_names['orders'],
                'source_rel_column' => 'id',
                'primary_key'       => 'id',
                'primary_key_type'  => 'int',
            ],
        ];
    }

    /**
     * Get columns config.
     *
     * @return \string[][] Config.
     */
    protected function get_core_column_mapping(): array
    {
        return [
            'ID'                => [
                'type'        => 'int',
                'destination' => 'id',
            ],
            'post_status'       => [
                'type'        => 'string',
                'destination' => 'status',
            ],
            'post_date_gmt'     => [
                'type'        => 'date',
                'destination' => 'date_created_gmt',
            ],
            'post_modified_gmt' => [
                'type'        => 'date',
                'destination' => 'date_updated_gmt',
            ],
            'post_parent'       => [
                'type'        => 'int',
                'destination' => 'parent_order_id',
            ],
            'post_type'         => [
                'type'        => 'string',
                'destination' => 'type',
            ],
            'post_excerpt'      => [
                'type'        => 'string',
                'destination' => 'customer_note',
            ],
        ];
    }

    /**
     * Get meta data config.
     *
     * @return \string[][] Config.
     */
    public function get_meta_column_config(): array
    {
        return [
            '_order_currency'       => [
                'type'        => 'string',
                'destination' => 'currency',
            ],
            '_order_tax'            => [
                'type'        => 'decimal',
                'destination' => 'tax_amount',
            ],
            '_order_total'          => [
                'type'        => 'decimal',
                'destination' => 'total_amount',
            ],
            '_customer_user'        => [
                'type'        => 'int',
                'destination' => 'customer_id',
            ],
            '_billing_email'        => [
                'type'        => 'string',
                'destination' => 'billing_email',
            ],
            '_payment_method'       => [
                'type'        => 'string',
                'destination' => 'payment_method',
            ],
            '_payment_method_title' => [
                'type'        => 'string',
                'destination' => 'payment_method_title',
            ],
            '_customer_ip_address'  => [
                'type'        => 'string',
                'destination' => 'ip_address',
            ],
            '_customer_user_agent'  => [
                'type'        => 'string',
                'destination' => 'user_agent',
            ],
            '_transaction_id'       => [
                'type'        => 'string',
                'destination' => 'transaction_id',
            ],
        ];
    }
}
