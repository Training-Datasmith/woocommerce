<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class InstallPlugin
 *
 * This class represents a step in the installation process of a WooCommerce plugin.
 * It includes methods to prepare the data for the plugin installation step and to provide
 * the schema for the JSON representation of this step.
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class InstallPlugin extends Step
{
    /**
     * InstallPlugin constructor.
     *
     * @param string $slug The slug of the plugin to be installed.
     * @param string $resource The resource URL or path to the plugin's ZIP file.
     * @param array  $options Additional options for the plugin installation.
     */
    // phpcs:ignore
    public function __construct(
        /**
         * The slug of the plugin to be installed.
         */
        private readonly string $slug,
        /**
         * The resource URL or path to the plugin's ZIP file.
         */
        private readonly string $resource,
        /**
         * Additional options for the plugin installation.
         */
        private readonly array $options = []
    ) {
    }

    /**
     * Prepares an associative array for JSON encoding.
     *
     * @return array Array representing this installation step.
     */
    public function prepare_json_array(): array
    {
        return [
            'step'       => static::get_step_name(),
            'pluginData' => [
                'resource' => $this->resource,
                'slug'     => $this->slug,
            ],
            'options'    => $this->options,
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
                'step'       => [
                    'type' => 'string',
                    'enum' => [ static::get_step_name() ],
                ],
                'pluginData' => [
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
                'options'    => [
                    'type'       => 'object',
                    'properties' => [
                        'activate' => [
                            'type' => 'boolean',
                        ],
                    ],
                ],
            ],
            'required'   => [ 'step', 'pluginData' ],
        ];
    }

    /**
     * Returns the name of this step.
     *
     * @return string The step name.
     */
    public static function get_step_name(): string
    {
        return 'installPlugin';
    }
}
