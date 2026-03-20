<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Class ActivatePlugin
 *
 * @package Automattic\WooCommerce\Blueprint\Steps
 */
class ActivatePlugin extends Step
{
    /**
     * ActivatePlugin constructor.
     *
     * @param string $plugin_path Path to the plugin file relative to the plugins directory.
     * @param string $plugin_name The name of the plugin to be activated.
     */
    public function __construct(
        /**
         * The path to the plugin file relative to the plugins directory.
         */
        private readonly string $plugin_path,
        /**
         * The name of the plugin to be activated.
         */
        private readonly string $plugin_name = ''
    ) {
    }

    /**
     * Returns the name of this step.
     *
     * @return string The step name.
     */
    public static function get_step_name(): string
    {
        return 'activatePlugin';
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
                'pluginName' => [
                    'type' => 'string',
                ],
                'pluginPath' => [
                    'type' => 'string',
                ],
            ],
            'required'   => [ 'step', 'pluginPath' ],
        ];
    }

    /**
     * Prepares an associative array for JSON encoding.
     *
     * @return array Array of data to be encoded as JSON.
     */
    public function prepare_json_array(): array
    {
        $data = [
            'step'       => static::get_step_name(),
            'pluginPath' => $this->plugin_path,
        ];

        if (! empty($this->plugin_name)) {
            $data['pluginName'] = $this->plugin_name;
        }

        return $data;
    }
}
