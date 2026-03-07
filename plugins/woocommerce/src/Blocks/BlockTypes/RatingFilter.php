<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * PriceFilter class.
 */
class RatingFilter extends AbstractBlock
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name  = 'rating-filter';
    public const RATING_QUERY_VAR = 'rating_filter';

    /**
     * Get the frontend script handle for this block type.
     *
     * @param string $key Data to get, or default to everything.
     */
    protected function get_block_type_script($key = null): null
    {
        return null;
    }

    /**
     * Get the frontend style handle for this block type.
     *
     * @return string[]
     */
    protected function get_block_type_style(): array
    {
        return array_merge(parent::get_block_type_style(), [ 'wc-blocks-packages-style' ]);
    }
}
