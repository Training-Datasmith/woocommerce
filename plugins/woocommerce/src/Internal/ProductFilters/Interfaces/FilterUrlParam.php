<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\ProductFilters\Interfaces;

/**
 * Interface for filter URL parameters.
 *
 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
 */
interface FilterUrlParam
{
    /**
     * Get the param keys.
     */
    public function get_param_keys(): array;

    /**
     * Get the param.
     *
     * @param string $type The type of param to get.
     */
    public function get_param(string $type): array;
}
