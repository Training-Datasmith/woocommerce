<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Transformers;

use stdClass;
/**
 * Flatten nested array.
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class Array_Flatten implements Transformer_Interface
{
    /**
     * Search a given value in the array.
     *
     * @param mixed         $value a value to transform.
     * @param stdClass|null $arguments arguments.
     * @param string|null   $default_value default value.
     *
     * @return mixed|null
     */
    public function transform($value, ?stdClass $arguments = null, $default_value = [])
    {
        if (!is_array($value)) {
            return $default_value;
        }
        $return = [];
        array_walk_recursive($value, function ($item) use (&$return): void {
            $return[] = $item;
        });
        return $return;
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