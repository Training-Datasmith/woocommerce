<?php

declare(strict_types=1);
/**
 * Tests for the WC_Data class.
 *
 * @package WooCommerce\Tests\Blocks
 */

/**
 * Class WC_Test_Blocks_Utils
 */
class WC_Test_Blocks_Utils extends WC_Unit_Test_Case
{
    /**
     * @group block-utils
     * Test: has_block_in_page.
     *
     */
    public function test_has_block_in_page_on_page_with_single_block()
    {
        $page = [
            'name'    => 'blocks-page',
            'title'   => 'Checkout',
            'content' => '<!-- wp:woocommerce/checkout {"showOrderNotes":false} --> <div class="wp-block-woocommerce-checkout is-loading"></div> <!-- /wp:woocommerce/checkout -->',
        ];

        $page_id = wc_create_page($page['name'], '', $page['title'], $page['content']);

        $this->assertTrue(WC_Blocks_Utils::has_block_in_page($page_id, 'woocommerce/checkout'));
        $this->assertFalse(WC_Blocks_Utils::has_block_in_page($page_id, 'woocommerce/cart'));
    }

    /**
     * @group block-utils
     * Test: has_block_in_page.
     *
     */
    public function test_has_block_in_page_on_page_with_no_blocks()
    {
        $page = [
            'name'    => 'shortcode-page',
            'title'   => 'Checkout',
            'content' => '<!-- wp:shortcode --> [woocommerce_checkout] <!-- /wp:shortcode -->',
        ];

        $page_id = wc_create_page($page['name'], '', $page['title'], $page['content']);

        $this->assertFalse(WC_Blocks_Utils::has_block_in_page($page_id, 'woocommerce/checkout'));
        $this->assertFalse(WC_Blocks_Utils::has_block_in_page($page_id, 'woocommerce/cart'));
    }

    /**
     * @group block-utils
     * Test: has_block_in_page.
     *
     */
    public function test_has_block_in_page_on_page_with_multiple_blocks()
    {
        $page = [
            'name'    => 'shortcode-page',
            'title'   => 'Checkout',
            'content' => '<!-- wp:woocommerce/featured-product {"editMode":false,"productId":17} -->
				<!-- wp:button {"align":"center"} -->
				<div class="wp-block-button aligncenter"><a class="wp-block-button__link" href="https://blocks.local/product/beanie/">Shop now</a></div>
				<!-- /wp:button -->
				<!-- /wp:woocommerce/featured-product -->

				<!-- wp:heading -->
				<h2>test</h2>
				<!-- /wp:heading -->',
        ];

        $page_id = wc_create_page($page['name'], '', $page['title'], $page['content']);

        $this->assertTrue(WC_Blocks_Utils::has_block_in_page($page_id, 'woocommerce/featured-product'));
        $this->assertTrue(WC_Blocks_Utils::has_block_in_page($page_id, 'core/heading'));
    }

    /**
     * @group block-utils
     * Test: get_all_blocks_from_page.
     *
     */
    public function test_get_all_blocks_from_page()
    {
        $page = [
            'name'    => 'cart',
            'title'   => 'Checkout',
            'content' => '<!-- wp:heading --><h2>test1</h2><!-- /wp:heading --><!-- wp:heading --><h1>test2</h1><!-- /wp:heading -->',
        ];

        wc_create_page($page['name'], 'woocommerce_cart_page_id', $page['title'], $page['content']);

        $expected = [
            0 => [
                'blockName' => 'core/heading',
                'attrs' => [],
                'innerBlocks' => [],
                'innerHTML' => '<h2>test1</h2>',
                'innerContent' => [
                    0 => '<h2>test1</h2>',
                ],
            ],
            1 => [
                'blockName' => 'core/heading',
                'attrs' => [],
                'innerBlocks' => [],
                'innerHTML' => '<h1>test2</h1>',
                'innerContent' => [
                    0 => '<h1>test2</h1>',
                ],
            ],
        ];

        $blocks = WC_Blocks_Utils::get_blocks_from_page('core/heading', 'cart');

        $this->assertEquals($expected, $blocks);
    }
}
