<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors;

/**
 * A custom GetRuleProcessor class to support context_vars and context_plugins rule types.
 *
 * GetRuleProcessor class.
 */
class Get_Rule_Processor_For_Context
{
    /**
     * Constructor.
     *
     * @param array $context The context variables.
     */
    public function __construct(
        /**
         * Contains the context variables.
         */
        protected array $context = []
    )
    {
    }
    /**
     * Get the processor for the specified rule type.
     *
     * @param string $rule_type The rule type.
     *
     * @return RuleProcessorInterface The matching processor for the specified rule type, or a FailRuleProcessor if no matching processor is found.
     */
    public function get_processor($rule_type)
    {
        return match ($rule_type) {
            'context_plugins' => new Context_Plugins_Rule_Processor($this->context['plugins'] ?? []),
            default => Get_Rule_Processor::get_processor($rule_type),
        };
    }
}