<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class InstallTheme
 *
 * This class represents a step in the installation process of a WooCommerce theme.
 * It includes methods to prepare the data for the theme installation step and to provide
 * the schema for the JSON representation of this step.
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class InstallTheme extends Step
{
    /**
     * InstallTheme constructor.
     *
     * @param string $slug The slug of the theme to be installed.
     * @param string $resource The resource URL or path to the theme's ZIP file.
     * @param array  $options Additional options for the theme installation.
     */
    // phpcs:ignore
    public function __construct(
        /**
         * The slug of the theme to be installed.
         */
        private readonly string $slug,
        /**
         * The resource URL or path to the theme's ZIP file.
         */
        private readonly string $resource,
        /**
         * Additional options for the theme installation.
         */
        private readonly array $options = []
    ) {
    }

    /**
     * Prepares an associative array for JSON encoding.
     *
     * @return array The JSON-encoded array representing this installation step.
     */
    public function prepare_json_array(): array
    {
        return [
            'step'      => static::get_step_name(),
            'themeData' => [
                'resource' => $this->resource,
                'slug'     => $this->slug,
            ],
            'options'   => $this->options,
        ];
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
                'step'      => [
                    'type' => 'string',
                    'enum' => [ static::get_step_name() ],
                ],
                'themeData' => [
                    'anyOf' => [
                        require __DIR__ . '/schemas/definitions/VFSReference.php',
                        require __DIR__ . '/schemas/definitions/LiteralReference.php',
                        require __DIR__ . '/schemas/definitions/CorePluginReference.php',
                        require __DIR__ . '/schemas/definitions/CoreThemeReference.php',
                        require __DIR__ . '/schemas/definitions/UrlReference.php',
                        require __DIR__ . '/schemas/definitions/GitDirectoryReference.php',
                        require __DIR__ . '/schemas/definitions/DirectoryLiteralReference.php',
                    ],
                ],
                'options'   => [
                    'type'       => 'object',
                    'properties' => [
                        'activate' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
            'required'   => [ 'step', 'themeData' ],
        ];
    }

    /**
     * Returns the name of this step.
     *
     * @return string The step name.
     */
    public static function get_step_name(): string
    {
        return 'installTheme';
    }
}
