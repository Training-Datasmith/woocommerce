<?php

/**
 * Rule processor that passes when a store's payments volume exceeds a provided amount.
 */
declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\API\Reports\Revenue\Query as RevenueQuery;
use Automattic\Woo_Commerce\Admin\API\Reports\Time_Interval;
/**
 * Rule processor that passes when a store's payments volume exceeds a provided amount.
 */
class Total_Payments_Volume_Processor implements Rule_Processor_Interface
{
    /**
     * Compare against the store's total payments volume.
     *
     * @param object $rule         The rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state)
    {
        $dates = Time_Interval::get_timeframe_dates($rule->timeframe);
        $reports_revenue = $this->get_reports_query(['before' => $dates['end'], 'after' => $dates['start'], 'interval' => 'year', 'fields' => ['total_sales']]);
        $report_data = $reports_revenue->get_data();
        if (!$report_data || !isset($report_data->totals->total_sales)) {
            return false;
        }
        $value = $report_data->totals->total_sales;
        return Comparison_Operation::compare($value, $rule->value, $rule->operation);
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
        $allowed_timeframes = ['last_week', 'last_month', 'last_quarter', 'last_6_months', 'last_year'];
        if (!isset($rule->timeframe) || !in_array($rule->timeframe, $allowed_timeframes, true)) {
            return false;
        }
        if (!isset($rule->value)) {
            return false;
        }
        if (!isset($rule->operation)) {
            return false;
        }
        // If the operation is range, the value must be an array of two numbers.
        if ('range' === $rule->operation) {
            if (!is_array($rule->value) || count($rule->value) !== 2) {
                return false;
            }
            if (!is_numeric($rule->value[0]) || !is_numeric($rule->value[1])) {
                return false;
            }
        } elseif (!is_numeric($rule->value)) {
            return false;
        }
        return true;
    }
    /**
     * Get the report query.
     *
     * @param array $args The query args.
     *
     * @return RevenueQuery The report query.
     */
    protected function get_reports_query($args): \Automattic\Woo_Commerce\Admin\API\Reports\Revenue\Query
    {
        return new Revenue_Query($args);
    }
}