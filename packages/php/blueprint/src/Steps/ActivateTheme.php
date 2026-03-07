<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class ActivateTheme
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class ActivateTheme extends Step
{
    /**
     * ActivateTheme constructor.
     *
     * @param string $theme_folder_name The name of the theme to be activated.
     */
    public function __construct(
        /**
         * The name of the theme to be activated.
         */
        private readonly string $theme_folder_name
    ) {
    }

    /**
     * Returns the name of this step.
     *
     * @return string The step name.
     */
    public static function get_step_name(): string
    {
        return 'activateTheme';
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
                'step'            => [
                    'type' => 'string',
                    'enum' => [ static::get_step_name() ],
                ],
                'themeFolderName' => [
                    'type' => 'string',
                ],
            ],
            'required'   => [ 'step', 'themeFolderName' ],
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
            'step'            => static::get_step_name(),
            'themeFolderName' => $this->theme_folder_name,
        ];
    }
}
