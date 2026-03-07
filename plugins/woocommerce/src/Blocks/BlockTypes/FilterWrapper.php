<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * FilterWrapper class.
 */
class FilterWrapper extends AbstractBlock
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'filter-wrapper';

    /**
     * Get the frontend style handle for this block type.
     */
    protected function get_block_type_style(): null
    {
        return null;
    }
}
