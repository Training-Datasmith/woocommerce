<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * ProductBestSellers class.
 */
class Product_Best_Sellers extends Abstract_Product_Grid
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-best-sellers';
    /**
     * Set args specific to this block
     *
     * @param array $query_args Query args.
     */
    protected function set_block_query_args(&$query_args)
    {
        $query_args['orderby'] = 'popularity';
    }
}