<?php

/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;

/**
 * Unit test for Spacing_Preprocessor
 */
class Spacing_Preprocessor_Test extends \Email_Editor_Unit_Test
{
    /**
     * Spacing_Preprocessor instance
     *
     * @var Spacing_Preprocessor
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
        $this->preprocessor = new Spacing_Preprocessor();
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
     * Test it adds default horizontal spacing
     */
    public function testItAddsDefaultVerticalSpacing(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/list',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                            [
                                'blockName'   => 'core/img',
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
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];

        $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $this->assertCount(2, $result);
        $first_columns             = $result[0];
        $second_columns            = $result[1];
        $nested_column             = $first_columns['innerBlocks'][0];
        $nested_column_first_item  = $nested_column['innerBlocks'][0];
        $nested_column_second_item = $nested_column['innerBlocks'][1];

        // First elements should not have margin-top, but others should.
        $this->assertArrayNotHasKey('margin-top', $first_columns['email_attrs']);
        $this->assertEquals('10px', $nested_column_second_item['email_attrs']['margin-top']);
        $this->assertArrayNotHasKey('margin-top', $nested_column['email_attrs']);
        $this->assertArrayNotHasKey('margin-top', $nested_column_first_item['email_attrs']);
        $this->assertArrayHasKey('margin-top', $nested_column_second_item['email_attrs']);
        $this->assertEquals('10px', $nested_column_second_item['email_attrs']['margin-top']);
    }

    /**
     * Test it adds padding-left to column blocks when parent columns has blockGap.left
     */
    public function testItAddsPaddingLeftToColumnsWithBlockGap(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'style' => [
                        'spacing' => [
                            'blockGap' => [
                                'top'  => '20px',
                                'left' => '30px',
                            ],
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];

        $result        = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $columns_block = $result[0];
        $first_column  = $columns_block['innerBlocks'][0];
        $second_column = $columns_block['innerBlocks'][1];
        $third_column  = $columns_block['innerBlocks'][2];

        // First column should not have padding-left.
        $this->assertArrayNotHasKey('padding-left', $first_column['email_attrs']);

        // Second and third columns should have padding-left.
        $this->assertArrayHasKey('padding-left', $second_column['email_attrs']);
        $this->assertEquals('30px', $second_column['email_attrs']['padding-left']);
        $this->assertArrayHasKey('padding-left', $third_column['email_attrs']);
        $this->assertEquals('30px', $third_column['email_attrs']['padding-left']);
    }

    /**
     * Test it passes preset variables through for columns blockGap (WP styles engine will handle transformation)
     */
    public function testItPassesPresetVariablesThroughForColumnsBlockGap(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'style' => [
                        'spacing' => [
                            'blockGap' => [
                                'left' => 'var:preset|spacing|40',
                            ],
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];

        $result        = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $second_column = $result[0]['innerBlocks'][1];

        // Should pass through "var:preset|spacing|40" as-is. WP's styles engine will handle transformation.
        $this->assertEquals('var:preset|spacing|40', $second_column['email_attrs']['padding-left']);
    }

    /**
     * Test it adds default padding-left when columns has no blockGap.left
     */
    public function testItAddsDefaultPaddingLeftWithoutBlockGapLeft(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'style' => [
                        'spacing' => [
                            'blockGap' => [
                                'top' => '20px',
                                // No 'left' key.
                            ],
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];

        $result        = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $second_column = $result[0]['innerBlocks'][1];

        // Should have padding-left with default gap value since blockGap.left is not set.
        $this->assertArrayHasKey('padding-left', $second_column['email_attrs']);
        $this->assertEquals('10px', $second_column['email_attrs']['padding-left']);
    }

    /**
     * Test it rejects malicious values in blockGap
     */
    public function testItRejectsMaliciousBlockGapValues(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'style' => [
                        'spacing' => [
                            'blockGap' => [
                                'left' => '30px"><script>alert("xss")</script>',
                            ],
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];

        $result        = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $second_column = $result[0]['innerBlocks'][1];

        // Should not have padding-left due to malicious value.
        $this->assertArrayNotHasKey('padding-left', $second_column['email_attrs']);
    }
}
