<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * ProductAttributeSchema class.
 */
class ProductAttributeSchema extends AbstractSchema
{
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'product_attribute';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'product-attribute';

    /**
     * Term properties.
     */
    public function get_properties(): array
    {
        return [
            'id'           => [
                'description' => __('Unique identifier for the resource.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'name'         => [
                'description' => __('Attribute name.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'taxonomy'     => [
                'description' => __('The attribute taxonomy name.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'type'         => [
                'description' => __('Attribute type.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'order'        => [
                'description' => __('How terms in this attribute are sorted by default.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'has_archives' => [
                'description' => __('If this attribute has term archive pages.', 'woocommerce'),
                'type'        => 'boolean',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'count'        => [
                'description' => __('Number of terms in the attribute taxonomy.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
        ];
    }

    /**
     * Convert an attribute object into an object suitable for the response.
     *
     * @param object $attribute Attribute object.
     */
    public function get_item_response($attribute): array
    {
        return [
            'id'           => (int) $attribute->id,
            'name'         => $this->prepare_html_response($attribute->name),
            'taxonomy'     => $attribute->slug,
            'type'         => $attribute->type,
            'order'        => $attribute->order_by,
            'has_archives' => $attribute->has_archives,
            'count'        => (int) \wp_count_terms($attribute->slug),
        ];
    }
}
