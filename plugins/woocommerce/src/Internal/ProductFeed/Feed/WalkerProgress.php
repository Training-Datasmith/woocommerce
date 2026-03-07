<?php

/**
 * Walker Progress class.
 *
 * @package Automattic\WooCommerce\Internal\ProductFeed
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFeed\Feed;

/**
 * Simple class that tracks/indicates the progress of a walker.
 *
 * @since 10.5.0
 */
final class WalkerProgress
{
    /**
     * Total number of items to process.
     */
    public int $total_count;

    /**
     * Total number of batches to process.
     */
    public int $total_batch_count;

    /**
     * Number of items processed so far.
     */
    public int $processed_items = 0;

    /**
     * Number of batches processed so far.
     */
    public int $processed_batches = 0;

    /**
     * Creates a WalkerProgress instance from a WooCommerce products query result.
     *
     * @since 10.5.0
     *
     * @param \stdClass $result The result object from wc_get_products() with total and max_num_pages properties.
     */
    public static function from_wc_get_products_result(\stdClass $result): self
    {
        $progress = new self();

        $progress->total_count       = $result->total;
        $progress->total_batch_count = $result->max_num_pages;
        $progress->processed_items   = 0;
        $progress->processed_batches = 0;

        return $progress;
    }
}
