<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class RunSql
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class RunSql extends Step
{
    /**
     * Constructor.
     *
     * @param string $sql Sql code to run.
     * @param string $name Name of the sql file.
     */
    public function __construct(
        /**
         * Sql code to run.
         */
        protected string $sql,
        /**
         * Name of the sql file.
         */
        protected string $name = 'schema.sql'
    ) {
    }

    /**
     * Returns the name of this step.
     *
     * @return string The step name.
     */
    public static function get_step_name(): string
    {
        return 'runSql';
    }

    /**
     * Returns the schema for the JSON representation of this step.
     *
     * @param int $version The version of the schema to return.
     * @return array The schema array.
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
                'sql'  => [
                    'type'       => 'object',
                    'required'   => [ 'contents', 'resource', 'name' ],
                    'properties' => [
                        'resource' => [
                            'type' => 'string',
                            'enum' => [ 'literal' ],
                        ],
                        'name'     => [
                            'type' => 'string',
                        ],
                        'contents' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
            'required'   => [ 'step', 'sql' ],
        ];
    }

    /**
     * Prepares an associative array for JSON encoding.
     *
     * @return array Array of data to be encoded as JSON.
     */
    public function prepare_json_array(): array
    {
        return [
            'step' => static::get_step_name(),
            'sql'  => [
                'resource' => 'literal',
                'name'     => $this->name,
                'contents' => $this->sql,
            ],
        ];
    }
}
