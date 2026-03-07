<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Set site options step.
 */
class SetSiteOptions extends Step
{
    /**
     * Constructor.
     *
     * @param array $options site options.
     */
    public function __construct(
        /**
         * Site options.
         */
        private readonly array $options = []
    ) {
    }

    /**
     * Get the name of the step.
     *
     * @return string step name
     */
    public static function get_step_name(): string
    {
        return 'setSiteOptions';
    }

    /**
     * Get the schema for the step.
     *
     * @param int $version schema version.
     *
     * @return array schema for the step
     */
    public static function get_schema(int $version = 1): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'step' => [
                    'type' => 'string',
                    'enum' => [ static::get_step_name() ],
                ],

            ],
            'required'   => [ 'step' ],
        ];
    }

    /**
     * Prepare the step for JSON serialization.
     *
     * @return array array representation of the step
     */
    public function prepare_json_array(): array
    {
        return [
            'step'    => static::get_step_name(),
            'options' => (object) $this->options,
        ];
    }
}
