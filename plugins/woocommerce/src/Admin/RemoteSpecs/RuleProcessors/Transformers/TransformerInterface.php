<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Remote_Specs\Rule_Processors\Transformers;

use stdClass;
/**
 * An interface to define a transformer.
 *
 * Interface TransformerInterface
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
interface Transformer_Interface
{
    /**
     * Transform given value to a different value.
     *
     * @param mixed         $value a value to transform.
     * @param stdClass|null $arguments arguments.
     * @param string|null   $default_value default value.
     *
     * @return mixed|null
     */
    public function transform($value, ?stdClass $arguments = null, $default_value = null);
    /**
     * Validate Transformer arguments.
     *
     * @param stdClass|null $arguments arguments to validate.
     *
     * @return mixed
     */
    public function validate(?stdClass $arguments = null);
}