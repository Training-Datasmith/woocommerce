<?php

declare(strict_types=1);

use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InstallTheme class.
 */
class InstallThemeTest extends TestCase
{
    /**
     * Test the constructor and JSON preparation.
     */
    public function testConstructorAndPrepareJsonArray()
    {
        $slug     = 'my-theme';
        $resource = 'https://example.com/my-theme.zip';
        $options  = [ 'activate' => true ];

        $install_theme = new InstallTheme($slug, $resource, $options);

        $expected_array = [
            'step'      => 'installTheme',
            'themeData' => [
                'resource' => $resource,
                'slug'     => $slug,
            ],
            'options'   => $options,
        ];

        $this->assertEquals($expected_array, $install_theme->prepare_json_array());
    }

    /**
     * Test the static get_step_name method.
     */
    public function testGetStepName()
    {
        $this->assertEquals('installTheme', InstallTheme::get_step_name());
    }

    /**
     * Test the static get_schema method.
     */
    public function testGetSchema()
    {
        $expected_schema = [
            'type'       => 'object',
            'properties' => [
                'step'      => [
                    'type' => 'string',
                    'enum' => [ 'installTheme' ],
                ],
                'themeData' => [
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

        $this->assertEquals($expected_schema, InstallTheme::get_schema());
    }
}
