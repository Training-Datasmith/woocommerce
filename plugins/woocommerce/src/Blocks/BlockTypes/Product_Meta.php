<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * ProductMeta class.
 */
class Product_Meta extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-meta';
    /**
     * Get the editor script data for this block type.
     *
     * @param string $key Data to get, or default to everything.
     */
    protected function get_block_type_editor_script($key = null): null
    {
        return null;
    }
    /**
     * Get the editor style handle for this block type.
     */
    protected function get_block_type_editor_style(): null
    {
        return null;
    }
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
     */
    protected function get_block_type_style(): null
    {
        return null;
    }
}