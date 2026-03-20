<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * FilterWrapper class.
 */
class Filter_Wrapper extends Abstract_Block
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