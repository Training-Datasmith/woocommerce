<?php

declare(strict_types=1);

use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SetSiteOptions class.
 */
class SetSiteOptionsTest extends TestCase
{
    /**
     * Test the constructor and JSON preparation.
     */
    public function testConstructorAndPrepareJsonArray()
    {
        $options = [
            'site_name' => 'My WooCommerce Site',
            'timezone'  => 'UTC',
        ];

        $set_site_options = new SetSiteOptions($options);

        $expected_array = [
            'step'    => 'setSiteOptions',
            'options' => (object) $options,
        ];

        $this->assertEquals($expected_array, $set_site_options->prepare_json_array());
    }

    /**
     * Test the static get_step_name method.
     */
    public function testGetStepName()
    {
        $this->assertEquals('setSiteOptions', SetSiteOptions::get_step_name());
    }

    /**
     * Test the static get_schema method.
     */
    public function testGetSchema()
    {
        $expected_schema = [
            'type'       => 'object',
            'properties' => [
                'step' => [
                    'type' => 'string',
                    'enum' => [ 'setSiteOptions' ],
                ],
            ],
            'required'   => [ 'step' ],
        ];

        $this->assertEquals($expected_schema, SetSiteOptions::get_schema());
    }
}
