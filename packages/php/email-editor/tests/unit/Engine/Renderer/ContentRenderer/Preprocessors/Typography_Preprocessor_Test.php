<?php

/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Typography_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;

/**
 * Unit test for Typography_Preprocessor
 */
class Typography_Preprocessor_Test extends \Email_Editor_Unit_Test
{
    /**
     * Instance of Typography_Preprocessor
     *
     * @var Typography_Preprocessor
     */
    private $preprocessor;

    /**
     * Layout settings
     *
     * @var array{contentSize: string}
     */
    private array $layout;

    /**
     * Styles settings
     *
     * @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles
     */
    private array $styles;

    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $settings_mock = $this->createMock(Settings_Controller::class);
        $theme_mock    = $this->createMock(\WP_Theme_JSON::class);
        $theme_mock->method('get_data')->willReturn(
            [
                'styles'   => [
                    'color'      => [
                        'text' => '#000000',
                    ],
                    'typography' => [
                        'fontSize'   => '13px',
                        'fontFamily' => 'Arial',
                    ],
                ],
                'settings' => [
                    'typography' => [
                        'fontFamilies' => [
                            [
                                'slug'       => 'arial-slug',
                                'name'       => 'Arial Name',
                                'fontFamily' => 'Arial',
                            ],
                            [
                                'slug'       => 'georgia-slug',
                                'name'       => 'Georgia Name',
                                'fontFamily' => 'Georgia',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $settings_mock->method('get_theme')->willReturn($theme_mock);
        // This slug translate mock expect slugs in format slug-10px and will return 10px.
        $settings_mock->method('translate_slug_to_font_size')->willReturnCallback(
            function ($slug) {
                return str_replace('slug-', '', $slug);
            }
        );
        // This slug translate mock expect slugs in format slug-color and will return color.
        $settings_mock->method('translate_slug_to_color')->willReturnMap(
            [
                [ 'slug-red', '#ff0000' ],
            ]
        );
        $this->preprocessor = new Typography_Preprocessor($settings_mock);
        $this->layout       = [ 'contentSize' => '660px' ];
        $this->styles       = [
            'spacing' => [
                'padding'  => [
                    'left'   => '10px',
                    'right'  => '10px',
                    'top'    => '10px',
                    'bottom' => '10px',
                ],
                'blockGap' => '10px',
            ],
        ];
    }

    /**
     * Test it copies columns typography
     */
    public function testItCopiesColumnsTypography(): void
    {
        $blocks               = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'fontFamily' => 'arial-slug',
                    'style'      => [
                        'color'      => [
                            'text' => '#aa00dd',
                        ],
                        'typography' => [
                            'fontSize'       => '12px',
                            'textDecoration' => 'underline',
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/paragraph',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected_email_attrs = [
            'color'           => '#aa00dd',
            'font-size'       => '12px',
            'text-decoration' => 'underline',
        ];
        $result               = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $result               = $result[0];
        $this->assertCount(2, $result['innerBlocks']);
        $this->assertEquals($expected_email_attrs, $result['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][0]['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][1]['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
    }

    /**
     * Test it replaces font family slugs with values
     */
    public function testItReplacesFontSizeSlugsWithValues(): void
    {
        $blocks               = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'fontSize' => 'slug-20px',
                    'style'    => [],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/paragraph',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected_email_attrs = [
            'color'     => '#000000',
            'font-size' => '20px',
        ];
        $result               = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $result               = $result[0];
        $this->assertCount(2, $result['innerBlocks']);
        $this->assertEquals($expected_email_attrs, $result['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][0]['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][1]['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
    }

    /**
     * Test it replaces text color slugs with values
     */
    public function testItReplacesTextColorSlugsWithValues(): void
    {
        $blocks               = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'textColor' => 'slug-red',
                    'style'     => [],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/paragraph',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected_email_attrs = [
            'color'     => '#ff0000',
            'font-size' => '13px',
        ];
        $result               = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $result               = $result[0];
        $this->assertCount(2, $result['innerBlocks']);
        $this->assertEquals($expected_email_attrs, $result['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][0]['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][1]['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
    }

    /**
     * Test it does not copy columns width
     */
    public function testItDoesNotCopyColumnsWidth(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'email_attrs' => [
                    'width' => '640px',
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/paragraph',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $result = $result[0];
        $this->assertCount(2, $result['innerBlocks']);
        $this->assertEquals(
            [
                'width'     => '640px',
                'color'     => '#000000',
                'font-size' => '13px',
            ],
            $result['email_attrs']
        );
        $default_font_styles = [
            'color'     => '#000000',
            'font-size' => '13px',
        ];
        $this->assertEquals($default_font_styles, $result['innerBlocks'][0]['email_attrs']);
        $this->assertEquals($default_font_styles, $result['innerBlocks'][1]['email_attrs']);
        $this->assertEquals($default_font_styles, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
    }

    /**
     * Test it ignores non-string textColor values
     */
    public function testItIgnoresNonStringTextColor(): void
    {
        $blocks = [
            [
                'blockName'   => 'plugin/custom-block',
                'attrs'       => [
                    // Third-party block might use textColor as an array or number.
                    'textColor' => [
                        'r' => 255,
                        'g' => 0,
                        'b' => 0,
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];
        // Should use theme defaults since textColor is not a string.
        $expected_email_attrs = [
            'color'     => '#000000',
            'font-size' => '13px',
        ];
        $result               = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $result               = $result[0];
        $this->assertEquals($expected_email_attrs, $result['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][0]['email_attrs']);
    }

    /**
     * Test it ignores non-string fontSize values
     */
    public function testItIgnoresNonStringFontSize(): void
    {
        $blocks = [
            [
                'blockName'   => 'plugin/custom-block',
                'attrs'       => [
                    // Third-party block might use fontSize as a number or array.
                    'fontSize' => 16,
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];
        // Should use theme defaults since fontSize is not a string.
        $expected_email_attrs = [
            'color'     => '#000000',
            'font-size' => '13px',
        ];
        $result               = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $result               = $result[0];
        $this->assertEquals($expected_email_attrs, $result['email_attrs']);
        $this->assertEquals($expected_email_attrs, $result['innerBlocks'][0]['email_attrs']);
    }

    /**
     * Test it overrides columns typography
     */
    public function testItOverridesColumnsTypography(): void
    {
        $blocks                = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'fontFamily' => 'arial-slug',
                    'style'      => [
                        'color'      => [
                            'text' => '#aa00dd',
                        ],
                        'typography' => [
                            'fontSize' => '12px',
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'fontFamily' => 'georgia-slug',
                            'style'      => [
                                'color'      => [
                                    'text' => '#cc22aa',
                                ],
                                'typography' => [
                                    'fontSize' => '18px',
                                ],
                            ],
                        ],
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/paragraph',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/paragraph',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'fontFamily' => 'georgia-slug',
                            'style'      => [
                                'color'      => [
                                    'text' => '#cc22aa',
                                ],
                                'typography' => [
                                    'fontSize' => '18px',
                                ],
                            ],
                        ],
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/paragraph',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected_email_attrs1 = [
            'color'     => '#aa00dd',
            'font-size' => '12px',
        ];
        $expected_email_attrs2 = [
            'color'     => '#cc22aa',
            'font-size' => '18px',
        ];
        $result                = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $child1                = $result[0];
        $child2                = $result[1];
        $this->assertCount(2, $child1['innerBlocks']);
        $this->assertEquals($expected_email_attrs1, $child1['email_attrs']);
        $this->assertEquals($expected_email_attrs2, $child1['innerBlocks'][0]['email_attrs']);
        $this->assertEquals($expected_email_attrs2, $child1['innerBlocks'][0]['innerBlocks'][0]['email_attrs']);
        $this->assertEquals($expected_email_attrs1, $child1['innerBlocks'][1]['email_attrs']);
        $this->assertEquals($expected_email_attrs1, $child1['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
        $this->assertCount(1, $child2['innerBlocks']);
        $this->assertEquals(
            [
                'color'     => '#000000',
                'font-size' => '13px',
            ],
            $child2['email_attrs']
        );
        $this->assertEquals($expected_email_attrs2, $child2['innerBlocks'][0]['email_attrs']);
        $this->assertEquals($expected_email_attrs2, $child2['innerBlocks'][0]['innerBlocks'][0]['email_attrs']);
    }
}
