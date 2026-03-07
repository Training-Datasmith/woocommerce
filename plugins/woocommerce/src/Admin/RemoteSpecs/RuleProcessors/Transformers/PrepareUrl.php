<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers;

use stdClass;

/**
 * Prepare site URL for comparison.
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class PrepareUrl implements TransformerInterface
{
    /**
     * Prepares the site URL by removing the protocol and trailing slash.
     *
     * @param string        $value a value to transform.
     * @param stdClass|null $arguments arguments.
     * @param string|null   $default_value default value.
     *
     * @return mixed|null
     */
    public function transform($value, ?stdClass $arguments = null, $default_value = null)
    {
        if (! is_string($value)) {
            return $default_value;
        }

        $url_parts = wp_parse_url(rtrim($value, '/'));

        if (! $url_parts) {
            return $default_value;
        }

        if (! isset($url_parts['host'])) {
            return $default_value;
        }

        if (isset($url_parts['path'])) {
            return $url_parts['host'] . $url_parts['path'];
        }

        return $url_parts['host'];
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
