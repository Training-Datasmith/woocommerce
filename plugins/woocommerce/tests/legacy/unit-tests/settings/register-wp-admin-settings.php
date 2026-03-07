<?php

declare(strict_types=1);
/**
 * WC_Tests_Register_WP_Admin_Settings class file.
 *
 * @package @package WooCommerce\Tests\Settings
 */

/**
 * Settings API: WP-Admin Helper Tests
 * Tests the helper class that makes settings (currently present in wp-admin)
 * available to the REST API.
 *
 * @package WooCommerce\Tests\Settings
 * @since 3.0.0
 */
class WC_Tests_Register_WP_Admin_Settings extends WC_Unit_Test_Case
{
    /**
     * @var WC_Settings_Page $page
     */
    protected $page;

    /**
     * Initialize a WC_Settings_Page for testing
     */
    public function setUp(): void
    {
        parent::setUp();

        $mock_page = $this->getMockBuilder('WC_Settings_General')->getMock();

        $mock_page
            ->expects($this->any())
            ->method('get_id')
            ->will($this->returnValue('page-id'));

        $mock_page
            ->expects($this->any())
            ->method('get_label')
            ->will($this->returnValue('Page Label'));

        $this->page = $mock_page;
    }

    /**
     * @since 3.0.0
     * @covers WC_Register_WP_Admin_Settings::__construct
     */
    public function test_constructor()
    {
        $settings = new WC_Register_WP_Admin_Settings($this->page, 'page');

        $this->assertEquals(has_filter('woocommerce_settings_groups', [ $settings, 'register_page_group' ]), 10);
        $this->assertEquals(has_filter('woocommerce_settings-' . $this->page->get_id(), [ $settings, 'register_page_settings' ]), 10);
    }

    /**
     * @since 3.0.0
     * @covers WC_Register_WP_Admin_Settings::register_page_group
     */
    public function test_register_group()
    {
        $settings = new WC_Register_WP_Admin_Settings($this->page, 'page');

        $existing = [
            'id'    => 'existing-id',
            'label' => 'Existing Group',
        ];
        $initial  = [ $existing ];
        $expected = [
            $existing,
            [
                'id'    => $this->page->get_id(),
                'label' => $this->page->get_label(),
            ],
        ];
        $actual   = $settings->register_page_group($initial);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @since 3.0.0
     */
    public function register_setting_provider()
    {
        return [
            // No "id" case.
            [
                [
                    'type'       => 'some-type-with-no-id',
                    'option_key' => '',
                ],
                false,
            ],
            // All optional properties except 'desc_tip'.
            [
                [
                    'id'         => 'setting-id',
                    'type'       => 'select',
                    'title'      => 'Setting Name',
                    'desc'       => 'Setting Description',
                    'default'    => 'one',
                    'options'    => [ 'one', 'two' ],
                    'option_key' => '',
                ],
                [
                    'id'          => 'setting-id',
                    'type'        => 'select',
                    'label'       => 'Setting Name',
                    'description' => 'Setting Description',
                    'default'     => 'one',
                    'options'     => [ 'one', 'two' ],
                    'option_key'  => '',
                ],
            ],
            // Boolean 'desc_tip' defaulting to 'desc' value.
            [
                [
                    'id'         => 'setting-id',
                    'type'       => 'select',
                    'title'      => 'Setting Name',
                    'desc'       => 'Setting Description',
                    'desc_tip'   => true,
                    'option_key' => '',
                ],
                [
                    'id'          => 'setting-id',
                    'type'        => 'select',
                    'label'       => 'Setting Name',
                    'description' => 'Setting Description',
                    'tip'         => 'Setting Description',
                    'option_key'  => '',
                ],
            ],
            // String 'desc_tip'.
            [
                [
                    'id'         => 'setting-id',
                    'type'       => 'select',
                    'title'      => 'Setting Name',
                    'desc'       => 'Setting Description',
                    'desc_tip'   => 'Setting Tip',
                    'option_key' => '',
                ],
                [
                    'id'          => 'setting-id',
                    'type'        => 'select',
                    'label'       => 'Setting Name',
                    'description' => 'Setting Description',
                    'tip'         => 'Setting Tip',
                    'option_key'  => '',
                ],
            ],
            // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            // Empty 'title' and empty 'desc'.
            [
                [
                    'id'         => 'setting-id',
                    'type'       => 'select',
                    'option_key' => '',
                ],
                [
                    'id'          => 'setting-id',
                    'type'        => 'select',
                    'label'       => '',
                    'description' => '',
                    'option_key'  => '',
                ],
            ],
        ];
    }

    /**
     * @since 3.0.0
     * @dataProvider register_setting_provider
     * @covers WC_Register_WP_Admin_Settings::register_setting
     * @param array $input Array of settings.
     * @param bool  $expected Expected result of the setting registering operation.
     */
    public function test_register_setting($input, $expected)
    {
        $settings = new WC_Register_WP_Admin_Settings($this->page, 'page');

        $actual = $settings->register_setting($input);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @since 3.0.0
     * @covers WC_Register_WP_Admin_Settings::register_page_settings
     */
    public function test_register_settings_default_section_no_settings()
    {
        $this->page
            ->expects($this->any())
            ->method('get_sections')
            ->will($this->returnValue([]));

        $this->page
            ->expects($this->once())
            ->method('get_settings')
            ->with($this->equalTo(''))
            ->will($this->returnValue([]));

        $settings = new WC_Register_WP_Admin_Settings($this->page, 'page');

        $expected = [];
        $actual   = $settings->register_page_settings([]);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @since 3.0.0
     * @covers WC_Register_WP_Admin_Settings::register_page_settings
     */
    public function test_register_settings_default_section_with_settings()
    {
        $this->page
            ->expects($this->any())
            ->method('get_sections')
            ->will($this->returnValue([]));

        $settings = [
            [
                'id'         => 'setting-1',
                'type'       => 'text',
                'option_key' => '',
            ],
            [
                'type'       => 'no-id',
                'option_key' => '',
            ],
            [
                'id'         => 'setting-2',
                'type'       => 'textarea',
                'option_key' => '',
            ],
        ];

        $this->page
            ->expects($this->any())
            ->method('get_settings')
            ->will($this->returnValue($settings));

        $settings = new WC_Register_WP_Admin_Settings($this->page, 'page');

        $expected = [
            [
                'id'          => 'setting-1',
                'type'        => 'text',
                'label'       => '',
                'description' => '',
                'option_key'  => 'setting-1',
            ],
            [
                'id'          => 'setting-2',
                'type'        => 'textarea',
                'label'       => '',
                'description' => '',
                'option_key'  => 'setting-2',
            ],
        ];
        $actual   = $settings->register_page_settings([]);

        $this->assertEquals($expected, $actual);
    }
}
