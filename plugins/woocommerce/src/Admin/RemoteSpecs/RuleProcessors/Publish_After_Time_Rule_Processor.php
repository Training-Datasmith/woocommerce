<?php

declare (strict_types=1);
/**
 * Rule processor for sending after a specified date/time.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Date_Time_Provider\Current_Date_Time_Provider;
/**
 * Rule processor for sending after a specified date/time.
 */
class Publish_After_Time_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * The DateTime provider.
     *
     * @var DateTimeProviderInterface
     */
    protected $date_time_provider;
    /**
     * Constructor.
     *
     * @param DateTimeProviderInterface $date_time_provider The DateTime provider.
     */
    public function __construct($date_time_provider = null)
    {
        $this->date_time_provider = $date_time_provider ?? new Current_Date_Time_Provider();
    }
    /**
     * Process the rule.
     *
     * @param object $rule         The specific rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool Whether the rule passes or not.
     */
    public function process($rule, $stored_state): bool
    {
        return $this->date_time_provider->get_now() >= new \DateTime($rule->publish_after);
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
        if (!isset($rule->publish_after)) {
            return false;
        }
        try {
            new \DateTime($rule->publish_after);
        } catch (\Throwable) {
            return false;
        }
        return true;
    }
}