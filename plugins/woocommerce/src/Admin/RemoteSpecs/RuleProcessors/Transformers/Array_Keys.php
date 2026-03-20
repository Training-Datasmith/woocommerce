<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Transformers;

use stdClass;
/**
 * Search array value by one of its key.
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class Array_Keys implements Transformer_Interface
{
    /**
     * Search array value by one of its key.
     *
     * @param mixed         $value a value to transform.
     * @param stdClass|null $arguments arguments.
     * @param string|null   $default_value default value.
     *
     * @return mixed
     */
    public function transform($value, ?stdClass $arguments = null, $default_value = [])
    {
        if (!is_array($value)) {
            return $default_value;
        }
        return array_keys($value);
    }
    /**
     * Validate Transformer arguments.
     *
     * @param stdClass|null $arguments arguments to validate.
     */
    public function validate(?stdClass $arguments = null): bool
    {
        return true;
    }
}