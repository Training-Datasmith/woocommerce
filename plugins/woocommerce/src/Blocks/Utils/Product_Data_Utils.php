<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Utils;

/**
 * Utility class to get product data consumable by the blocks.
 *
 * @internal
 */
class Product_Data_Utils
{
    /**
     * Get the product data.
     *
     * @param \WC_Product $product Product object.
     * @return array The product data.
     */
    public static function get_product_data(\WC_Product $product): array
    {
        return ['price_html' => $product->get_price_html()];
    }
}