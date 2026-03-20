<?php

declare (strict_types=1);
/**
 * Rule processor that performs a comparison operation against the number of
 * products.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

use Automattic\Woo_Commerce\Enums\Product_Status;
defined('ABSPATH') || exit;
/**
 * Rule processor that performs a comparison operation against the number of
 * products.
 */
class Product_Count_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * The product query.
     *
     * @var WC_Product_Query
     */
    protected $product_query;
    /**
     * Constructor.
     *
     * @param object $product_query The product query.
     */
    public function __construct($product_query = null)
    {
        $this->product_query = $product_query ?? new \WC_Product_Query(['limit' => 1, 'paginate' => true, 'return' => 'ids', 'status' => [Product_Status::PUBLISH]]);
    }
    /**
     * Performs a comparison operation against the number of products.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state)
    {
        $products = $this->product_query->get_products();
        return Comparison_Operation::compare($products->total, $rule->value, $rule->operation);
    }
    /**
     * Validates the rule.
     *
     * @param object $rule The rule to validate.
     *
     * @return bool Pass/fail.
     */
    public function validate($rule): bool
    {
        if (!isset($rule->value)) {
            return false;
        }
        if (!isset($rule->operation)) {
            return false;
        }
        return true;
    }
}