<?php

declare (strict_types=1);
/**
 * AssignDefaultCategory class file.
 */
namespace Automattic\Woo_Commerce\Internal;

defined('ABSPATH') || exit;
/**
 * Class to assign default category to products.
 */
class Assign_Default_Category
{
    /**
     * Class initialization, to be executed when the class is resolved by the container.
     *
     * @internal
     */
    final public function init(): void
    {
        add_action('wc_schedule_update_product_default_cat', $this->maybe_assign_default_product_cat(...));
    }
    /**
     * When a product category is deleted, we need to check
     * if the product has no categories assigned. Then assign
     * it a default category. We delay this with a scheduled
     * action job to not block the response.
     */
    public function schedule_action(): void
    {
        WC()->queue()->schedule_single(time(), 'wc_schedule_update_product_default_cat', [], 'wc_update_product_default_cat');
    }
    /**
     * Assigns default product category for products
     * that have no categories.
     */
    public function maybe_assign_default_product_cat(): void
    {
        global $wpdb;
        $default_category = get_option('default_product_cat', 0);
        if ($default_category) {
            $affected_rows = $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id)\n\t\t\t\t\tSELECT DISTINCT posts.ID, %s FROM {$wpdb->posts} posts\n\t\t\t\t\tLEFT JOIN\n\t\t\t\t\t\t(\n\t\t\t\t\t\t\tSELECT object_id FROM {$wpdb->term_relationships} term_relationships\n\t\t\t\t\t\t\tLEFT JOIN {$wpdb->term_taxonomy} term_taxonomy ON term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id\n\t\t\t\t\t\t\tWHERE term_taxonomy.taxonomy = 'product_cat'\n\t\t\t\t\t\t) AS tax_query\n\t\t\t\t\tON posts.ID = tax_query.object_id\n\t\t\t\t\tWHERE posts.post_type = 'product'\n\t\t\t\t\tAND tax_query.object_id IS NULL", $default_category));
            if ($affected_rows > 0) {
                wp_cache_flush();
                delete_transient('wc_term_counts');
                wp_update_term_count_now([$default_category], 'product_cat');
            }
        }
    }
}