<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Cache_Helper;

defined('ABSPATH') || exit;

/**
 * Hooks into WooCommerce actions to register cache invalidation.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
class CacheController implements RegisterHooksInterface
{
    public const CACHE_GROUP = 'filter_data';

    /**
     * Instance of TaxonomyHierarchyData.
     */
    private ?\Automattic\WooCommerce\Internal\ProductFilters\TaxonomyHierarchyData $taxonomy_hierarchy_data = null;

    /**
     * Initialize dependencies.
     *
     * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
     * @param TaxonomyHierarchyData $taxonomy_hierarchy_data Instance of TaxonomyHierarchyData.
     */
    final public function init(TaxonomyHierarchyData $taxonomy_hierarchy_data): void
    {
        $this->taxonomy_hierarchy_data = $taxonomy_hierarchy_data;
    }

    /**
     * Hook into actions and filters.
     */
    public function register(): void
    {
        if (! $this->need_cleanup()) {
            return;
        }

        add_action('woocommerce_after_product_object_save', $this->invalidate_filter_data_cache(...));
        add_action('woocommerce_delete_product_transients', $this->invalidate_filter_data_cache(...));

        // Clear taxonomy hierarchy cache when terms change.
        add_action('created_term', $this->clear_taxonomy_hierarchy_cache(...), 10, 3);
        add_action('edited_term', $this->clear_taxonomy_hierarchy_cache(...), 10, 3);
        add_action('delete_term', $this->clear_taxonomy_hierarchy_cache(...), 10, 3);

        // Clear taxonomy hierarchy cache when term meta (like 'order') is added or updated.
        add_action('added_term_meta', $this->clear_taxonomy_hierarchy_cache_on_meta_update(...), 10, 4);
        add_action('updated_term_meta', $this->clear_taxonomy_hierarchy_cache_on_meta_update(...), 10, 4);
        add_action('deleted_term_meta', $this->clear_taxonomy_hierarchy_cache_on_meta_update(...), 10, 4);
    }

    /**
     * Invalidate all cache under filter data group.
     */
    public function invalidate_filter_data_cache(): void
    {
        WC_Cache_Helper::get_transient_version(self::CACHE_GROUP, true);
        WC_Cache_Helper::invalidate_cache_group(self::CACHE_GROUP);
    }

    /**
     * Clear taxonomy hierarchy cache when terms are created, updated, or deleted.
     *
     * @param int    $term_id          Term ID.
     * @param int    $term_taxonomy_id Term taxonomy ID.
     * @param string $taxonomy         Taxonomy slug.
     */
    public function clear_taxonomy_hierarchy_cache($term_id, $term_taxonomy_id, $taxonomy): void
    {
        // Only clear cache for hierarchical taxonomies.
        if (is_taxonomy_hierarchical($taxonomy)) {
            $this->taxonomy_hierarchy_data->clear_cache($taxonomy);
        }
    }

    /**
     * Clear taxonomy hierarchy cache when term meta is updated.
     * This handles the case when categories are reordered (updates 'order' meta).
     *
     * @param int    $meta_id    Meta ID.
     * @param int    $term_id    Term ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     */
    public function clear_taxonomy_hierarchy_cache_on_meta_update($meta_id, $term_id, $meta_key, $meta_value): void
    {
        // Only clear cache when the 'order' meta key is updated (used for menu ordering).
        if ('order' !== $meta_key) {
            return;
        }

        $term = get_term($term_id);
        if (! $term instanceof \WP_Term) {
            return;
        }

        $this->taxonomy_hierarchy_data->clear_cache($term->taxonomy);
    }

    /**
     * Delete all filter data transients.
     */
    public function delete_filter_data_transients(): void
    {
        if (! $this->need_cleanup()) {
            return;
        }

        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_wc_filter_data_') . '%',
                $wpdb->esc_like('_transient_timeout_wc_filter_data_') . '%'
            )
        );
    }

    /**
     * Check if the filter data cache should be cleaned up.
     * If the cache group is not set, it means that the store is not using
     * the product filters and we don't need to register the hooks.
     */
    public function need_cleanup(): bool
    {
        return ! empty(get_transient(self::CACHE_GROUP . '-transient-version'));
    }
}
