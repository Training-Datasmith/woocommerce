<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers;

use InvalidArgumentException;
use stdClass;

/**
 * Searches a given a given value in the array.
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class ArraySearch implements TransformerInterface
{
    /**
     * Search a given value in the array.
     *
     * @param mixed         $value a value to transform.
     * @param stdClass|null $arguments required argument 'value'.
     * @param string|null   $default_value default value.
     *
     * @throws InvalidArgumentException Throws when the required 'value' is missing.
     *
     * @return mixed|null
     */
    public function transform($value, ?stdClass $arguments = null, $default_value = null)
    {
        if (! is_array($value)) {
            return $default_value;
        }

        $key = array_search($arguments->value, $value, true);
        if (false !== $key) {
            return $value[ $key ];
        }

        return null;
    }

    /**
     * Validate Transformer arguments.
     *
     * @param stdClass|null $arguments arguments to validate.
     */
    public function validate(?stdClass $arguments = null): bool
    {
        if (! isset($arguments->value)) {
            return false;
        }

        return true;
    }
}
