<?php

declare(strict_types=1);

use Automattic\WooCommerce\Blueprint\Steps\ActivateTheme;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ActivateTheme class.
 */
class ActivateThemeTest extends TestCase
{
    /**
     * Test the constructor and JSON preparation.
     */
    public function testConstructorAndPrepareJsonArray()
    {
        $theme_name     = 'my-theme';
        $activate_theme = new ActivateTheme($theme_name);

        $expected_array = [
            'step'            => 'activateTheme',
            'themeFolderName' => $theme_name,
        ];

        $this->assertEquals($expected_array, $activate_theme->prepare_json_array());
    }

    /**
     * Test the static get_step_name method.
     */
    public function testGetStepName()
    {
        $this->assertEquals('activateTheme', ActivateTheme::get_step_name());
    }

    /**
     * Test the static get_schema method.
     */
    public function testGetSchema()
    {
        $expected_schema = [
            'type'       => 'object',
            'properties' => [
                'step'            => [
                    'type' => 'string',
                    'enum' => [ 'activateTheme' ],
                ],
                'themeFolderName' => [
                    'type' => 'string',
                ],
            ],
            'required'   => [ 'step', 'themeFolderName' ],
        ];

        $this->assertEquals($expected_schema, ActivateTheme::get_schema());
    }
}
