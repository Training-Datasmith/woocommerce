<?php

declare (strict_types=1);
/**
 * Interface for a rule processor.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
/**
 * Rule processor interface
 */
interface Rule_Processor_Interface
{
    /**
     * Processes a rule, returning the boolean result of the processing.
     *
     * @param object $rule         The rule to process.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the processing.
     */
    public function process($rule, $stored_state);
    /**
     * Validates the rule.
     *
     * @param object $rule The rule to validate.
     *
     * @return bool Pass/fail.
     */
    public function validate($rule);
}