<?php

/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\Preprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Blocks_Width_Preprocessor;

/**
 * Unit test for Blocks_Width_Preprocessor
 */
class Blocks_Width_Preprocessor_Test extends \Email_Editor_Unit_Test
{
    /**
     * Instance of Blocks_Width_Preprocessor
     *
     * @var Blocks_Width_Preprocessor
     */
    private $preprocessor;

    /**
     * Layout configuration
     *
     * @var array{contentSize: string}
     */
    private array $layout;

    /**
     * Styles configuration
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
        $this->preprocessor = new Blocks_Width_Preprocessor();
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
     * Test it calculates width without padding
     */
    public function testItCalculatesWidthWithoutPadding(): void
    {
        $blocks                       = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '50%',
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '25%',
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '100px',
                        ],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];
        $styles                       = $this->styles;
        $styles['spacing']['padding'] = [
            'left'   => '0px',
            'right'  => '0px',
            'top'    => '0px',
            'bottom' => '0px',
        ];
        $result                       = $this->preprocessor->preprocess($blocks, $this->layout, $styles);
        $result                       = $result[0];
        $this->assertEquals('660px', $result['email_attrs']['width']);
        $this->assertCount(3, $result['innerBlocks']);
        $this->assertEquals('330px', $result['innerBlocks'][0]['email_attrs']['width']); // 660 * 0.5
        $this->assertEquals('165px', $result['innerBlocks'][1]['email_attrs']['width']); // 660 * 0.25
        $this->assertEquals('100px', $result['innerBlocks'][2]['email_attrs']['width']);
    }

    /**
     * Test it calculates width for column with layout padding
     */
    public function testItCalculatesWidthWithLayoutPadding(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '33%',
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '100px',
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '20%',
                        ],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];
        $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $result = $result[0];
        $this->assertCount(3, $result['innerBlocks']);
        $this->assertEquals('211px', $result['innerBlocks'][0]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
        $this->assertEquals('100px', $result['innerBlocks'][1]['email_attrs']['width']);
        $this->assertEquals('128px', $result['innerBlocks'][2]['email_attrs']['width']); // (660 - 10 - 10) * 0.2
    }

    /**
     * Test it calculates width of block in column
     */
    public function testItCalculatesWidthOfBlockInColumn(): void
    {
        $blocks       = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '40%',
                            'style' => [
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '10px',
                                        'right' => '10px',
                                    ],
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
                        'attrs'       => [
                            'width' => '60%',
                            'style' => [
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '25px',
                                        'right' => '15px',
                                    ],
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
        $result       = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $inner_blocks = $result[0]['innerBlocks'];

        $this->assertCount(2, $inner_blocks);
        $this->assertEquals('256px', $inner_blocks[0]['email_attrs']['width']); // (660 - 10 - 10) * 0.4
        $this->assertEquals('236px', $inner_blocks[0]['innerBlocks'][0]['email_attrs']['width']); // 256 - 10 - 10
        $this->assertEquals('384px', $inner_blocks[1]['email_attrs']['width']); // (660 - 10 - 10) * 0.6
        $this->assertEquals('344px', $inner_blocks[1]['innerBlocks'][0]['email_attrs']['width']); // 384 - 25 - 15
    }

    /**
     * Test it calculates width for column with padding
     */
    public function testItAddsMissingColumnWidth(): void
    {
        $blocks       = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
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
                        'attrs'       => [],
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
                        'attrs'       => [],
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
        $result       = $this->preprocessor->preprocess($blocks, [ 'contentSize' => '620px' ], $this->styles);
        $inner_blocks = $result[0]['innerBlocks'];

        $this->assertCount(3, $inner_blocks);
        $this->assertEquals('200px', $inner_blocks[0]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
        $this->assertEquals('200px', $inner_blocks[0]['innerBlocks'][0]['email_attrs']['width']);
        $this->assertEquals('200px', $inner_blocks[1]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
        $this->assertEquals('200px', $inner_blocks[1]['innerBlocks'][0]['email_attrs']['width']);
        $this->assertEquals('200px', $inner_blocks[2]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
        $this->assertEquals('200px', $inner_blocks[2]['innerBlocks'][0]['email_attrs']['width']);
    }

    /**
     * Test it calculates width for column with padding
     */
    public function testItCalculatesMissingColumnWidth(): void
    {
        $blocks       = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'style' => [
                        'spacing' => [
                            'padding' => [
                                'left'  => '25px',
                                'right' => '15px',
                            ],
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '33.33%',
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '200px',
                        ],
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
        $result       = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $inner_blocks = $result[0]['innerBlocks'];

        $this->assertCount(3, $inner_blocks);
        $this->assertEquals('200px', $inner_blocks[0]['email_attrs']['width']); // (620 - 10 - 10) * 0.3333
        $this->assertEquals('200px', $inner_blocks[1]['email_attrs']['width']); // already defined.
        $this->assertEquals('200px', $inner_blocks[2]['email_attrs']['width']); // 600 -200 - 200
    }

    /**
     * Test it calculates width for column with padding
     */
    public function testItDoesNotSubtractPaddingForFullWidthBlocks(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'align' => 'full',
                ],
                'innerBlocks' => [],
            ],
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [],
            ],
        ];
        $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);

        $this->assertCount(2, $result);
        $this->assertEquals('660px', $result[0]['email_attrs']['width']); // full width.
        $this->assertEquals('640px', $result[1]['email_attrs']['width']); // 660 - 10 - 10
    }

    /**
     * Test it calculates width for column with padding
     */
    public function testItCalculatesWidthForColumnWithoutDefinition(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'style' => [
                        'spacing' => [
                            'padding' => [
                                'left'  => '25px',
                                'right' => '15px',
                            ],
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '140px',
                            'style' => [
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '25px',
                                        'right' => '15px',
                                    ],
                                ],
                            ],
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'style' => [
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '10px',
                                        'right' => '10px',
                                    ],
                                ],
                            ],
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'style' => [
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '20px',
                                        'right' => '20px',
                                    ],
                                ],
                            ],
                        ],
                        'innerBlocks' => [],
                    ],
                ],
            ],
        ];

        $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $this->assertCount(3, $result[0]['innerBlocks']);
        $this->assertEquals('140px', $result[0]['innerBlocks'][0]['email_attrs']['width']);
        $this->assertEquals('220px', $result[0]['innerBlocks'][1]['email_attrs']['width']);
        $this->assertEquals('240px', $result[0]['innerBlocks'][2]['email_attrs']['width']);

        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '140px',
                            'style' => [
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '25px',
                                        'right' => '15px',
                                    ],
                                ],
                            ],
                        ],
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

        $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $this->assertCount(2, $result[0]['innerBlocks']);
        $this->assertEquals('140px', $result[0]['innerBlocks'][0]['email_attrs']['width']);
        $this->assertEquals('500px', $result[0]['innerBlocks'][1]['email_attrs']['width']);
    }

    /**
     * Test it calculates width for column with border
     */
    public function testItCalculatesWidthForColumnWithBorder(): void
    {
        $blocks = [
            [
                'blockName'   => 'core/columns',
                'attrs'       => [
                    'style' => [
                        'border'  => [
                            'width' => '10px',
                        ],
                        'spacing' => [
                            'padding' => [
                                'left'  => '25px',
                                'right' => '15px',
                            ],
                        ],
                    ],
                ],
                'innerBlocks' => [
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'width' => '140px',
                            'style' => [
                                'border'  => [
                                    'left'  => [
                                        'width' => '5px',
                                    ],
                                    'right' => [
                                        'width' => '5px',
                                    ],
                                ],
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '25px',
                                        'right' => '15px',
                                    ],
                                ],
                            ],
                        ],
                        'innerBlocks' => [],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [],
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/image',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                    [
                        'blockName'   => 'core/column',
                        'attrs'       => [
                            'style' => [
                                'border'  => [
                                    'width' => '15px',
                                ],
                                'spacing' => [
                                    'padding' => [
                                        'left'  => '20px',
                                        'right' => '20px',
                                    ],
                                ],
                            ],
                        ],
                        'innerBlocks' => [
                            [
                                'blockName'   => 'core/image',
                                'attrs'       => [],
                                'innerBlocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
        $this->assertCount(3, $result[0]['innerBlocks']);
        $this->assertEquals('140px', $result[0]['innerBlocks'][0]['email_attrs']['width']);
        $this->assertEquals('185px', $result[0]['innerBlocks'][1]['email_attrs']['width']);
        $this->assertEquals('255px', $result[0]['innerBlocks'][2]['email_attrs']['width']);
        $image_block = $result[0]['innerBlocks'][1]['innerBlocks'][0];
        $this->assertEquals('185px', $image_block['email_attrs']['width']);
        $image_block = $result[0]['innerBlocks'][2]['innerBlocks'][0];
        $this->assertEquals('215px', $image_block['email_attrs']['width']);
    }

    /**
     * Test it handles non-string width values
     */
    public function testItHandlesNonStringWidthValues(): void
    {
        $styles                       = $this->styles;
        $styles['spacing']['padding'] = [
            'left'   => '0px',
            'right'  => '0px',
            'top'    => '0px',
            'bottom' => '0px',
        ];

        // Test numeric width (should be treated as percentage).
        $blocks = [
            [
                'blockName'   => 'core/paragraph',
                'attrs'       => [
                    'width' => 50,
                ],
                'innerBlocks' => [],
            ],
        ];
        $result = $this->preprocessor->preprocess($blocks, $this->layout, $styles);
        $this->assertEquals('330px', $result[0]['email_attrs']['width']); // 660 * 0.5

        // Test array width (should default to 100%).
        $blocks = [
            [
                'blockName'   => 'core/paragraph',
                'attrs'       => [
                    'width' => [ 'value' => 50 ],
                ],
                'innerBlocks' => [],
            ],
        ];
        $result = $this->preprocessor->preprocess($blocks, $this->layout, $styles);
        $this->assertEquals('660px', $result[0]['email_attrs']['width']); // 100% of 660

        // Test boolean width (should default to 100%).
        $blocks = [
            [
                'blockName'   => 'core/paragraph',
                'attrs'       => [
                    'width' => true,
                ],
                'innerBlocks' => [],
            ],
        ];
        $result = $this->preprocessor->preprocess($blocks, $this->layout, $styles);
        $this->assertEquals('660px', $result[0]['email_attrs']['width']); // 100% of 660
    }
}
