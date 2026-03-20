<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

trait Enable_Block_Json_Assets_Trait
{
    /**
     * Disable the script handle for this block type. We use block.json to load the script.
     *
     * @param string|null $key The key of the script to get.
     */
    // phpcs:ignore
    protected function get_block_type_script($key = null): null
    {
        return null;
    }
    /**
     * Disable the style handle for this block type. We use block.json to load the style.
     */
    protected function get_block_type_style(): null
    {
        return null;
    }
    /**
     * Disable the editor style handle for this block type. We use block.json to load the style.
     */
    protected function get_block_type_editor_style(): null
    {
        return null;
    }
}