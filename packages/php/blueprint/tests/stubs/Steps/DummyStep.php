<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Tests\stubs\Steps;

use Automattic\WooCommerce\Blueprint\Steps\Step;

/**
 * Dummy step class.
 */
class DummyStep extends Step
{
    /**
     * Get the step name.
     *
     * @return string The step name.
     */
    public static function get_step_name(): string
    {
        return 'dummy';
    }

    /**
     * Get the schema.
     *
     * @param int $version The version of the schema.
     * @return array The schema.
     */
    public static function get_schema(int $version = 1): array
    {
        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'properties'           => [
                'step' => [
                    'type' => 'string',
                    'enum' => [ static::get_step_name() ],
                ],
            ],
            'required'             => [ 'step' ],
        ];
    }

    /**
     * Prepare the JSON array.
     *
     * @return array The JSON array.
     */
    public function prepare_json_array(): array
    {
        return [
            'step' => static::get_step_name(),
        ];
    }
}
