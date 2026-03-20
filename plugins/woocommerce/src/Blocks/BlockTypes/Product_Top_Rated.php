<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Block_Types;

/**
 * ProductTopRated class.
 */
class Product_Top_Rated extends Abstract_Product_Grid
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'product-top-rated';
    /**
     * Force orderby to rating.
     *
     * @param array $query_args Query args.
     */
    protected function set_block_query_args(&$query_args)
    {
        $query_args['orderby'] = 'rating';
    }
}