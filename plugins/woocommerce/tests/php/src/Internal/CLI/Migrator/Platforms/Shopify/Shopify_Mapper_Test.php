<?php

/**
 * Shopify Mapper Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Platforms\Shopify;

use Automattic\WooCommerce\Internal\CLI\Migrator\Interfaces\PlatformMapperInterface;
use Automattic\WooCommerce\Internal\CLI\Migrator\Platforms\Shopify\ShopifyMapper;

/**
 * Test cases for ShopifyMapper implementation.
 */
class ShopifyMapperTest extends \WC_Unit_Test_Case
{
    /**
     * The ShopifyMapper instance under test.
     *
     * @var ShopifyMapper
     */
    private ShopifyMapper $mapper;

    /**
     * Set up before each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ShopifyMapper();
    }

    /**
     * Test that ShopifyMapper implements the PlatformMapperInterface.
     */
    public function test_implements_platform_mapper_interface()
    {
        $this->assertInstanceOf(PlatformMapperInterface::class, $this->mapper);
    }

    /**
     * Test map_product_data method returns expected type.
     */
    public function test_map_product_data_returns_array()
    {
        $shopify_product = $this->create_simple_shopify_product();
        $result          = $this->mapper->map_product_data($shopify_product);

        $this->assertIsArray($result);
    }

    /**
     * Test basic product field mapping for simple product.
     */
    public function test_basic_product_field_mapping()
    {
        $shopify_product = $this->create_simple_shopify_product();
        $result          = $this->mapper->map_product_data($shopify_product);

        // Test basic fields.
        $this->assertEquals('Amazing T-Shirt', $result['name']);
        $this->assertEquals('amazing-t-shirt', $result['slug']);
        $this->assertEquals('A really amazing t-shirt', $result['description']);
        $this->assertEquals('publish', $result['status']);
        $this->assertEquals('123456789', $result['original_product_id']);
        $this->assertEquals('2023-01-01T00:00:00Z', $result['date_created_gmt']);
        $this->assertFalse($result['is_variable']);
    }

    /**
     * Test product status mapping.
     */
    public function test_product_status_mapping()
    {
        // Test ACTIVE status.
        $active_product         = $this->create_simple_shopify_product();
        $active_product->status = 'ACTIVE';
        $result                 = $this->mapper->map_product_data($active_product);
        $this->assertEquals('publish', $result['status']);

        // Test DRAFT status.
        $draft_product         = $this->create_simple_shopify_product();
        $draft_product->status = 'DRAFT';
        $result                = $this->mapper->map_product_data($draft_product);
        $this->assertEquals('draft', $result['status']);

        // Test ARCHIVED status.
        $archived_product         = $this->create_simple_shopify_product();
        $archived_product->status = 'ARCHIVED';
        $result                   = $this->mapper->map_product_data($archived_product);
        $this->assertEquals('draft', $result['status']);
    }

    /**
     * Test catalog visibility mapping.
     */
    public function test_catalog_visibility_mapping()
    {
        // Test visible product (has onlineStoreUrl).
        $visible_product                 = $this->create_simple_shopify_product();
        $visible_product->onlineStoreUrl = 'https://test-store.myshopify.com/products/amazing-t-shirt'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
        $result                          = $this->mapper->map_product_data($visible_product);
        $this->assertEquals('visible', $result['catalog_visibility']);
        $this->assertEquals('https://test-store.myshopify.com/products/amazing-t-shirt', $result['original_url']);

        // Test hidden product (onlineStoreUrl is null).
        $hidden_product                 = $this->create_simple_shopify_product();
        $hidden_product->onlineStoreUrl = null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
        $result                         = $this->mapper->map_product_data($hidden_product);
        $this->assertEquals('hidden', $result['catalog_visibility']);
        $this->assertNull($result['original_url']);
    }

    /**
     * Test enhanced status mapping.
     */
    public function test_enhanced_status_mapping()
    {
        $shopify_product                   = $this->create_simple_shopify_product();
        $shopify_product->publishedAt      = '2023-06-01T10:00:00Z'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
        $shopify_product->availableForSale = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
        $result                            = $this->mapper->map_product_data($shopify_product);

        $this->assertEquals('2023-06-01T10:00:00Z', $result['date_published_gmt']);
        $this->assertTrue($result['available_for_sale']);
    }

    /**
     * Test product classification mapping.
     */
    public function test_product_classification_mapping()
    {
        $shopify_product                        = $this->create_simple_shopify_product();
        $shopify_product->product_type          = 'Apparel';
        $shopify_product->category              = (object) [ 'name' => 'Shirts' ];
        $shopify_product->is_gift_card          = false;
        $shopify_product->requires_selling_plan = true;

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertEquals(
            [
                'name' => 'Apparel',
                'slug' => 'apparel',
            ],
            $result['product_type']
        );
        $this->assertEquals(
            [
                'name' => 'Shirts',
                'slug' => 'shirts',
            ],
            $result['standard_category']
        );
        $this->assertFalse($result['is_gift_card']);
        $this->assertTrue($result['requires_subscription']);
    }

    /**
     * Test simple product pricing mapping.
     */
    public function test_simple_product_pricing()
    {
        $shopify_product = $this->create_simple_shopify_product();
        $result          = $this->mapper->map_product_data($shopify_product);

        // Test regular pricing (no sale).
        $this->assertEquals('25.00', $result['regular_price']);
        $this->assertNull($result['sale_price']);

        // Test sale pricing.
        $sale_product = $this->create_simple_shopify_product();
        $sale_product->variants->edges[0]->node->compareAtPrice = '35.00';
        $sale_result = $this->mapper->map_product_data($sale_product);

        $this->assertEquals('35.00', $sale_result['regular_price']);
        $this->assertEquals('25.00', $sale_result['sale_price']);
    }

    /**
     * Test simple product inventory mapping.
     */
    public function test_simple_product_inventory()
    {
        $shopify_product = $this->create_simple_shopify_product();

        // Add inventory tracking.
        $shopify_product->variants->edges[0]->node->inventoryItem     = (object) [ 'tracked' => true ];
        $shopify_product->variants->edges[0]->node->inventoryQuantity = 50;
        $shopify_product->variants->edges[0]->node->inventoryPolicy   = 'DENY';

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertTrue($result['manage_stock']);
        $this->assertEquals(50, $result['stock_quantity']);
        $this->assertEquals('instock', $result['stock_status']);

        // Test out of stock.
        $shopify_product->variants->edges[0]->node->inventoryQuantity = 0;
        $result = $this->mapper->map_product_data($shopify_product);
        $this->assertEquals('outofstock', $result['stock_status']);

        // Test oversell allowed.
        $shopify_product->variants->edges[0]->node->inventoryPolicy = 'CONTINUE';
        $result = $this->mapper->map_product_data($shopify_product);
        $this->assertEquals('instock', $result['stock_status']); // Should be in stock even with 0 qty.
    }

    /**
     * Test weight conversion.
     */
    public function test_weight_conversion()
    {
        $shopify_product = $this->create_simple_shopify_product();

        // Add weight data.
        $shopify_product->variants->edges[0]->node->inventoryItem = (object) [
            'tracked'     => true,
            'measurement' => (object) [
                'weight' => (object) [
                    'value' => 1000.0,
                    'unit'  => 'GRAMS',
                ],
            ],
        ];

        $result = $this->mapper->map_product_data($shopify_product);

        // Should convert grams to store unit (depends on WC settings).
        $this->assertIsFloat($result['weight']);
        $this->assertGreaterThan(0, $result['weight']);
    }

    /**
     * Test variable product detection.
     */
    public function test_variable_product_detection()
    {
        $variable_product = $this->create_variable_shopify_product();
        $result           = $this->mapper->map_product_data($variable_product);

        $this->assertTrue($result['is_variable']);
        $this->assertNotEmpty($result['attributes']);
        $this->assertNotEmpty($result['variations']);
        $this->assertCount(2, $result['variations']);
    }

    /**
     * Test product attributes mapping for variable products.
     */
    public function test_product_attributes_mapping()
    {
        $variable_product = $this->create_variable_shopify_product();
        $result           = $this->mapper->map_product_data($variable_product);

        $this->assertCount(2, $result['attributes']);

        // Test Size attribute.
        $size_attr = $result['attributes'][0];
        $this->assertEquals('Size', $size_attr['name']);
        $this->assertEquals([ 'Small', 'Large' ], $size_attr['options']);
        $this->assertEquals(1, $size_attr['position']);
        $this->assertTrue($size_attr['is_visible']);
        $this->assertTrue($size_attr['is_variation']);

        // Test Color attribute.
        $color_attr = $result['attributes'][1];
        $this->assertEquals('Color', $color_attr['name']);
        $this->assertEquals([ 'Red', 'Blue' ], $color_attr['options']);
        $this->assertEquals(2, $color_attr['position']);
    }

    /**
     * Test product variations mapping.
     */
    public function test_product_variations_mapping()
    {
        $variable_product = $this->create_variable_shopify_product();
        $result           = $this->mapper->map_product_data($variable_product);

        $this->assertCount(2, $result['variations']);

        // Test first variation.
        $variation1 = $result['variations'][0];
        $this->assertEquals('111', $variation1['original_id']);
        $this->assertEquals('20.00', $variation1['regular_price']);
        $this->assertEquals('SMALL-RED', $variation1['sku']);
        $this->assertEquals(1, $variation1['menu_order']);
        $this->assertEquals(
            [
                'Size'  => 'Small',
                'Color' => 'Red',
            ],
            $variation1['attributes']
        );

        // Test second variation.
        $variation2 = $result['variations'][1];
        $this->assertEquals('222', $variation2['original_id']);
        $this->assertEquals('25.00', $variation2['regular_price']);
        $this->assertEquals('LARGE-BLUE', $variation2['sku']);
        $this->assertEquals(2, $variation2['menu_order']);
        $this->assertEquals(
            [
                'Size'  => 'Large',
                'Color' => 'Blue',
            ],
            $variation2['attributes']
        );
    }

    /**
     * Test categories mapping from collections.
     */
    public function test_categories_mapping()
    {
        $shopify_product              = $this->create_simple_shopify_product();
        $shopify_product->collections = (object) [
            'edges' => [
                (object) [
                    'node' => (object) [
                        'title'  => 'T-Shirts',
                        'handle' => 't-shirts',
                    ],
                ],
                (object) [
                    'node' => (object) [
                        'title'  => 'Clothing',
                        'handle' => 'clothing',
                    ],
                ],
            ],
        ];

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertCount(2, $result['categories']);
        $this->assertEquals(
            [
                'name' => 'T-Shirts',
                'slug' => 't-shirts',
            ],
            $result['categories'][0]
        );
        $this->assertEquals(
            [
                'name' => 'Clothing',
                'slug' => 'clothing',
            ],
            $result['categories'][1]
        );
    }

    /**
     * Test tags mapping.
     */
    public function test_tags_mapping()
    {
        $shopify_product       = $this->create_simple_shopify_product();
        $shopify_product->tags = [ 'summer', 'casual', 'cotton' ];

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertCount(3, $result['tags']);
        $this->assertEquals(
            [
                'name' => 'summer',
                'slug' => 'summer',
            ],
            $result['tags'][0]
        );
        $this->assertEquals(
            [
                'name' => 'casual',
                'slug' => 'casual',
            ],
            $result['tags'][1]
        );
        $this->assertEquals(
            [
                'name' => 'cotton',
                'slug' => 'cotton',
            ],
            $result['tags'][2]
        );
    }

    /**
     * Test brand mapping from vendor.
     */
    public function test_brand_mapping()
    {
        $shopify_product         = $this->create_simple_shopify_product();
        $shopify_product->vendor = 'Cool Brand';

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertEquals(
            [
                'name' => 'Cool Brand',
                'slug' => 'cool-brand',
            ],
            $result['brand']
        );
    }

    /**
     * Test images mapping.
     */
    public function test_images_mapping()
    {
        $shopify_product                = $this->create_simple_shopify_product();
        $shopify_product->featuredMedia = (object) [ 'id' => 'gid://shopify/MediaImage/featured123' ]; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- GraphQL uses camelCase.
        $shopify_product->media         = (object) [
            'edges' => [
                (object) [
                    'node' => (object) [
                        'id'    => 'gid://shopify/MediaImage/featured123',
                        'image' => (object) [
                            'url'     => 'https://placehold.co/800x600.jpg',
                            'altText' => 'Featured Image',
                        ],
                    ],
                ],
                (object) [
                    'node' => (object) [
                        'id'    => 'gid://shopify/MediaImage/gallery456',
                        'image' => (object) [
                            'url'     => 'https://placehold.co/600x600.jpg',
                            'altText' => 'Gallery Image',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertCount(2, $result['images']);

        // Test featured image.
        $featured_image = $result['images'][0];
        $this->assertEquals('gid://shopify/MediaImage/featured123', $featured_image['original_id']);
        $this->assertEquals('https://placehold.co/800x600.jpg', $featured_image['src']);
        $this->assertEquals('Featured Image', $featured_image['alt']);
        $this->assertTrue($featured_image['is_featured']);

        // Test gallery image.
        $gallery_image = $result['images'][1];
        $this->assertEquals('gid://shopify/MediaImage/gallery456', $gallery_image['original_id']);
        $this->assertEquals('https://placehold.co/600x600.jpg', $gallery_image['src']);
        $this->assertEquals('Gallery Image', $gallery_image['alt']);
        $this->assertFalse($gallery_image['is_featured']);
    }

    /**
     * Test SEO fields mapping.
     */
    public function test_seo_fields_mapping()
    {
        $shopify_product      = $this->create_simple_shopify_product();
        $shopify_product->seo = (object) [
            'title'       => 'Amazing T-Shirt - Best Quality',
            'description' => 'Buy the best quality t-shirt online. Free shipping available.',
        ];

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertEquals('Amazing T-Shirt - Best Quality', $result['metafields']['global_title_tag']);
        $this->assertEquals('Buy the best quality t-shirt online. Free shipping available.', $result['metafields']['global_description_tag']);
    }

    /**
     * Test metafields mapping.
     */
    public function test_metafields_mapping()
    {
        $shopify_product             = $this->create_simple_shopify_product();
        $shopify_product->metafields = (object) [
            'edges' => [
                (object) [
                    'node' => (object) [
                        'namespace' => 'custom',
                        'key'       => 'material',
                        'value'     => '100% Cotton',
                    ],
                ],
                (object) [
                    'node' => (object) [
                        'namespace' => 'seo',
                        'key'       => 'focus_keyword',
                        'value'     => 't-shirt',
                    ],
                ],
            ],
        ];

        $result = $this->mapper->map_product_data($shopify_product);

        $this->assertEquals('100% Cotton', $result['metafields']['custom_material']);
        $this->assertEquals('t-shirt', $result['metafields']['seo_focus_keyword']);
    }

    /**
     * Test selective field processing with constructor args.
     */
    public function test_selective_field_processing()
    {
        $mapper_limited = new ShopifyMapper([ 'fields' => [ 'title', 'slug', 'price' ] ]);

        $shopify_product = $this->create_simple_shopify_product();
        $result          = $mapper_limited->map_product_data($shopify_product);

        // Should still have basic fields.
        $this->assertEquals('Amazing T-Shirt', $result['name']);
        $this->assertEquals('amazing-t-shirt', $result['slug']);
        $this->assertEquals('25.00', $result['regular_price']);

        // Should not have SKU key because 'sku' not in fields.
        $this->assertArrayNotHasKey('sku', $result);
    }

    /**
     * Test error handling with malformed data.
     */
    public function test_error_handling_with_malformed_data()
    {
        // Test with minimal data.
        $minimal_product = (object) [
            'id'        => 'gid://shopify/Product/123',
            'title'     => 'Test Product',
            'handle'    => 'test-product',
            'status'    => 'ACTIVE',
            'createdAt' => '2023-01-01T00:00:00Z',
        ];

        $result = $this->mapper->map_product_data($minimal_product);

        $this->assertIsArray($result);
        $this->assertEquals('Test Product', $result['name']);
        $this->assertEquals('test-product', $result['slug']);
        $this->assertEquals('publish', $result['status']);
        $this->assertFalse($result['is_variable']);
        $this->assertEquals([], $result['categories']);
        $this->assertEquals([], $result['tags']);
        $this->assertEquals([], $result['images']);
    }

    /**
     * Test tax status mapping for simple products.
     */
    public function test_tax_status_mapping_simple_product()
    {
        // Test non-taxable simple product.
        $non_taxable_product                                    = $this->create_simple_shopify_product();
        $non_taxable_product->variants->edges[0]->node->taxable = false;

        $result = $this->mapper->map_product_data($non_taxable_product);

        $this->assertEquals('none', $result['tax_status']);

        // Test taxable simple product.
        $taxable_product                                    = $this->create_simple_shopify_product();
        $taxable_product->variants->edges[0]->node->taxable = true;

        $result = $this->mapper->map_product_data($taxable_product);

        $this->assertEquals('taxable', $result['tax_status']);
    }

    /**
     * Test tax status mapping for variable products.
     */
    public function test_tax_status_mapping_variable_product()
    {
        $variable_product = $this->create_variable_shopify_product();

        // Set first variation as non-taxable, second as taxable.
        $variable_product->variants->edges[0]->node->taxable = false;
        $variable_product->variants->edges[1]->node->taxable = true;

        $result = $this->mapper->map_product_data($variable_product);

        $this->assertTrue($result['is_variable']);
        $this->assertCount(2, $result['variations']);

        // Check first variation is not taxable.
        $this->assertEquals('none', $result['variations'][0]['tax_status']);

        // Check second variation is taxable.
        $this->assertEquals('taxable', $result['variations'][1]['tax_status']);
    }

    /**
     * Test tax status mapping when taxable field is missing (backwards compatibility).
     */
    public function test_tax_status_mapping_missing_field()
    {
        // Test simple product without taxable field.
        $simple_product = $this->create_simple_shopify_product();
        // Intentionally don't set taxable field.

        $result = $this->mapper->map_product_data($simple_product);

        // Should not have tax_status key when taxable field is missing.
        $this->assertArrayNotHasKey('tax_status', $result);

        // Test variable product without taxable field.
        $variable_product = $this->create_variable_shopify_product();
        // Intentionally don't set taxable field on variants.

        $result = $this->mapper->map_product_data($variable_product);

        $this->assertTrue($result['is_variable']);
        // Variations should not have tax_status key when taxable field is missing.
        foreach ($result['variations'] as $variation) {
            $this->assertArrayNotHasKey('tax_status', $variation);
        }
    }

    /**
     * Create a simple Shopify product object for testing.
     *
     * @return object Shopify product object.
     */
    private function create_simple_shopify_product(): object
    {
        return (object) [
            'id'              => 'gid://shopify/Product/123456789',
            'title'           => 'Amazing T-Shirt',
            'handle'          => 'amazing-t-shirt',
            'descriptionHtml' => 'A really amazing t-shirt',
            'status'          => 'ACTIVE',
            'createdAt'       => '2023-01-01T00:00:00Z',
            'vendor'          => 'Cool Brand',
            'variants'        => (object) [
                'edges' => [
                    (object) [
                        'node' => (object) [
                            'id'                => 'gid://shopify/ProductVariant/987654321',
                            'price'             => '25.00',
                            'compareAtPrice'    => null,
                            'sku'               => 'AMAZ-TSHIRT-M',
                            'inventoryQuantity' => 100,
                            'inventoryPolicy'   => 'DENY',
                            'position'          => 1,
                        ],
                    ],
                ],
            ],
            'tags'            => [],
        ];
    }

    /**
     * Create a variable Shopify product object for testing.
     *
     * @return object Variable Shopify product object.
     */
    private function create_variable_shopify_product(): object
    {
        return (object) [
            'id'              => 'gid://shopify/Product/123456789',
            'title'           => 'Variable T-Shirt',
            'handle'          => 'variable-t-shirt',
            'descriptionHtml' => 'A variable t-shirt with multiple options',
            'status'          => 'ACTIVE',
            'createdAt'       => '2023-01-01T00:00:00Z',
            'vendor'          => 'Cool Brand',
            'options'         => [
                (object) [
                    'name'     => 'Size',
                    'values'   => [ 'Small', 'Large' ],
                    'position' => 1,
                ],
                (object) [
                    'name'     => 'Color',
                    'values'   => [ 'Red', 'Blue' ],
                    'position' => 2,
                ],
            ],
            'variants'        => (object) [
                'edges' => [
                    (object) [
                        'node' => (object) [
                            'id'              => 'gid://shopify/ProductVariant/111',
                            'price'           => '20.00',
                            'compareAtPrice'  => null,
                            'sku'             => 'SMALL-RED',
                            'inventoryPolicy' => 'DENY',
                            'position'        => 1,
                            'selectedOptions' => [
                                (object) [
                                    'name'  => 'Size',
                                    'value' => 'Small',
                                ],
                                (object) [
                                    'name'  => 'Color',
                                    'value' => 'Red',
                                ],
                            ],
                            'media'           => (object) [ 'edges' => [] ],
                        ],
                    ],
                    (object) [
                        'node' => (object) [
                            'id'              => 'gid://shopify/ProductVariant/222',
                            'price'           => '25.00',
                            'compareAtPrice'  => null,
                            'sku'             => 'LARGE-BLUE',
                            'inventoryPolicy' => 'DENY',
                            'position'        => 2,
                            'selectedOptions' => [
                                (object) [
                                    'name'  => 'Size',
                                    'value' => 'Large',
                                ],
                                (object) [
                                    'name'  => 'Color',
                                    'value' => 'Blue',
                                ],
                            ],
                            'media'           => (object) [ 'edges' => [] ],
                        ],
                    ],
                ],
            ],
            'tags'            => [],
        ];
    }
}
