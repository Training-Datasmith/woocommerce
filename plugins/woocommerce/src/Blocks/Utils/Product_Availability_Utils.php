<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Utils;

use Automattic\Woo_Commerce\Enums\Product_Type;
/**
 * Utility functions for product availability.
 */
class Product_Availability_Utils
{
    /**
     * Get product availability information.
     *
     * @param \WC_Product $product Product object.
     * @return string[] The product availability class and text.
     */
    public static function get_product_availability($product)
    {
        $product_availability = ['availability' => '', 'class' => ''];
        if (!$product) {
            return $product_availability;
        }
        $product_availability = $product->get_availability();
        // If the product is a variable product, make sure at least one of its
        // variations is purchasable.
        if (isset($product_availability['class']) && ('in-stock' === $product_availability['class'] || 'available-on-backorder' === $product_availability['class']) && Product_Type::VARIABLE === $product->get_type()) {
            if (!$product->has_purchasable_variations()) {
                $product_availability['availability'] = __('Out of stock', 'woocommerce');
                $product_availability['class'] = 'out-of-stock';
            }
        }
        return $product_availability;
    }
}