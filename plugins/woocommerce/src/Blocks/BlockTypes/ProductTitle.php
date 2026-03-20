<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * ProductTitle class.
 */
class Product_Title extends Abstract_Block
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-title';
    /**
     * API version name.
     *
     * @var string
     */
    protected $api_version = '3';
    /**
     * Register script and style assets for the block type before it is registered.
     *
     * This registers the scripts; it does not enqueue them.
     */
    protected function register_block_type_assets()
    {
        parent::register_block_type_assets();
        $this->register_chunk_translations([$this->block_name]);
    }
}