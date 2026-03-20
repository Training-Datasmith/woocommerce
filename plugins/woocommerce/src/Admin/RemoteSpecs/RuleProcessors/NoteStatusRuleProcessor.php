<?php

declare (strict_types=1);
/**
 * Rule processor that compares against the status of another note. For
 * example, this could be used to conditionally create a note only if another
 * note has not been actioned.
 */
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

defined('ABSPATH') || exit;
use Automattic\Woo_Commerce\Admin\Notes\Notes;
/**
 * Rule processor that compares against the status of another note.
 */
class Note_Status_Rule_Processor implements Rule_Processor_Interface
{
    /**
     * Compare against the status of another note.
     *
     * @param object $rule         The rule being processed by this rule processor.
     * @param object $stored_state Stored state.
     *
     * @return bool The result of the operation.
     */
    public function process($rule, $stored_state)
    {
        $status = Notes::get_note_status($rule->note_name);
        if (!$status) {
            return false;
        }
        return Comparison_Operation::compare($status, $rule->status, $rule->operation);
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
        if (!isset($rule->note_name)) {
            return false;
        }
        if (!isset($rule->status)) {
            return false;
        }
        if (!isset($rule->operation)) {
            return false;
        }
        return true;
    }
}