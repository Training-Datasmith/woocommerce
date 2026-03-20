<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * ProductCategory class.
 */
class Product_Category extends Abstract_Product_Grid
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-category';
    /**
     * Set args specific to this block
     *
     * @param array $query_args Query args.
     */
    protected function set_block_query_args(&$query_args)
    {
    }
    /**
     * Get block attributes.
     */
    protected function get_block_type_attributes(): array
    {
        return array_merge(parent::get_block_type_attributes(), ['className' => $this->get_schema_string(), 'orderby' => $this->get_schema_orderby(), 'editMode' => $this->get_schema_boolean(true)]);
    }
}