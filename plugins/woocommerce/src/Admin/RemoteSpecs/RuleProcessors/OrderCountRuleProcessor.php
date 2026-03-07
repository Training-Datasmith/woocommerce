<?php

declare(strict_types=1);
/**
 * Rule processor for publishing based on the number of orders.
 */

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

defined('ABSPATH') || exit;

/**
 * Rule processor for publishing based on the number of orders.
 */
class OrderCountRuleProcessor implements RuleProcessorInterface
{
    /**
     * The orders provider.
     *
     * @var OrdersProvider
     */
    protected $orders_provider;

    /**
     * Constructor.
     *
     * @param object $orders_provider The orders provider.
     */
    public function __construct($orders_provider = null)
    {
        $this->orders_provider = $orders_provider ?? new OrdersProvider();
    }

    /**
     * Process the rule.
     *
     * @param object $rule         The rule to process.
     * @param object $stored_state Stored state.
     *
     * @return bool Whether the rule passes or not.
     */
    public function process($rule, $stored_state)
    {
        $count = $this->orders_provider->get_order_count();

        return ComparisonOperation::compare(
            $count,
            $rule->value,
            $rule->operation
        );
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
        if (! isset($rule->value)) {
            return false;
        }

        if (! isset($rule->operation)) {
            return false;
        }

        return true;
    }
}
