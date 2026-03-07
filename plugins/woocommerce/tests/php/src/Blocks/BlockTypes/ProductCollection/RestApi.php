<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Mocks\ProductCollectionMock;

/**
 * Tests for the ProductCollection block REST API integration
 *
 * @group rest-api
 */
class RestApi extends \WP_UnitTestCase
{
    /**
     * This variable holds our Product Query object.
     *
     * @var ProductCollectionMock
     */
    private $block_instance;

    /**
     * Initiate the mock object.
     */
    protected function setUp(): void
    {
        $this->block_instance = new ProductCollectionMock();
    }

    /**
     * Test merging multiple filter queries on Editor side
     */
    public function test_updating_rest_query_without_attributes()
    {
        $product_visibility_terms  = wc_get_product_visibility_term_ids();
        $product_visibility_not_in = [ is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] ];

        $args    = [
            'posts_per_page' => 9,
        ];
        $request = Utils::build_request();

        $updated_query = $this->block_instance->update_rest_query_in_editor($args, $request);

        $this->assertContainsEquals(
            [
                'key'     => '_stock_status',
                'value'   => [],
                'compare' => 'IN',
            ],
            $updated_query['meta_query'],
        );

        $this->assertEquals(
            [
                [
                    'taxonomy' => 'product_visibility',
                    'field'    => 'term_taxonomy_id',
                    'terms'    => $product_visibility_not_in,
                    'operator' => 'NOT IN',
                ],
            ],
            $updated_query['tax_query'],
        );
    }

    /**
     * Test merging multiple filter queries.
     */
    public function test_updating_rest_query_with_attributes()
    {
        $product_visibility_terms  = wc_get_product_visibility_term_ids();
        $product_visibility_not_in = [ is_search() ? $product_visibility_terms['exclude-from-search'] : $product_visibility_terms['exclude-from-catalog'] ];

        $args            = [
            'posts_per_page' => 9,
        ];
        $time_frame_date = gmdate('Y-m-d H:i:s');
        $params          = [
            'featured'               => 'true',
            'woocommerceOnSale'      => 'true',
            'woocommerceAttributes'  => [
                [
                    'taxonomy' => 'pa_test',
                    'termId'   => 1,
                ],
            ],
            'woocommerceStockStatus' => [ ProductStockStatus::IN_STOCK, ProductStockStatus::OUT_OF_STOCK ],
            'timeFrame'              => [
                'operator' => 'in',
                'value'    => $time_frame_date,
            ],
            'priceRange'             => [
                'min' => 1,
                'max' => 100,
            ],
        ];

        $request = Utils::build_request($params);

        $updated_query = $this->block_instance->update_rest_query_in_editor($args, $request);

        $this->assertContainsEquals(
            [
                'key'     => '_stock_status',
                'value'   => [ ProductStockStatus::IN_STOCK, ProductStockStatus::OUT_OF_STOCK ],
                'compare' => 'IN',
            ],
            $updated_query['meta_query'],
        );

        $this->assertContains(
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => $product_visibility_not_in,
                'operator' => 'NOT IN',
            ],
            $updated_query['tax_query'],
        );
        $this->assertContains(
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
                'operator' => 'IN',
            ],
            $updated_query['tax_query'],
        );

        $this->assertContains(
            [
                'column'    => 'post_date_gmt',
                'after'     => $time_frame_date,
                'inclusive' => true,
            ],
            $updated_query['date_query'],
        );

        $this->assertContains(
            [
                'field'    => 'term_id',
                'operator' => 'IN',
                'taxonomy' => 'pa_test',
                'terms'    => [ 1 ],
            ],
            $updated_query['tax_query'],
        );

        $this->assertEquals(
            [
                'min' => 1,
                'max' => 100,
            ],
            $updated_query['priceRange'],
        );
    }
}
