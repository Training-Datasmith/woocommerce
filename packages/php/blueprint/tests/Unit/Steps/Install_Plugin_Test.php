<?php

declare(strict_types=1);

use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InstallPlugin class.
 */
class InstallPluginTest extends TestCase
{
    /**
     * Test the constructor and JSON preparation.
     */
    public function testConstructorAndPrepareJsonArray()
    {
        $slug     = 'sample-plugin';
        $resource = 'https://example.com/sample-plugin.zip';
        $options  = [ 'activate' => true ];

        $install_plugin = new InstallPlugin($slug, $resource, $options);

        $expected_array = [
            'step'       => 'installPlugin',
            'pluginData' => [
                'resource' => $resource,
                'slug'     => $slug,
            ],
            'options'    => $options,
        ];

        $this->assertEquals($expected_array, $install_plugin->prepare_json_array());
    }

    /**
     * Test the static get_step_name method.
     */
    public function testGetStepName()
    {
        $this->assertEquals('installPlugin', InstallPlugin::get_step_name());
    }

    /**
     * Test the static get_schema method.
     */
    public function testGetSchema()
    {
        $expected_schema = [
            'type'       => 'object',
            'properties' => [
                'step'       => [
                    'type' => 'string',
                    'enum' => [ 'installPlugin' ],
                ],
                'pluginData' => [
                    'anyOf' => [
                        require __DIR__ . '/../../../src/Steps/schemas/definitions/VFSReference.php',
                        require __DIR__ . '/../../../src/Steps/schemas/definitions/LiteralReference.php',
                        require __DIR__ . '/../../../src/Steps/schemas/definitions/CorePluginReference.php',
                        require __DIR__ . '/../../../src/Steps/schemas/definitions/CoreThemeReference.php',
                        require __DIR__ . '/../../../src/Steps/schemas/definitions/UrlReference.php',
                        require __DIR__ . '/../../../src/Steps/schemas/definitions/GitDirectoryReference.php',
                        require __DIR__ . '/../../../src/Steps/schemas/definitions/DirectoryLiteralReference.php',
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

        $this->assertEquals($expected_schema, InstallPlugin::get_schema());
    }
}
