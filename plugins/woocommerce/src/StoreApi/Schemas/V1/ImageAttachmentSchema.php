<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Schemas\V1;

/**
 * ImageAttachmentSchema class.
 */
class ImageAttachmentSchema extends AbstractSchema
{
    /**
     * The schema item name.
     *
     * @var string
     */
    protected $title = 'image';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'image';

    /**
     * Product schema properties.
     */
    public function get_properties(): array
    {
        return [
            'id'        => [
                'description' => __('Image ID.', 'woocommerce'),
                'type'        => 'integer',
                'context'     => [ 'view', 'edit', 'embed' ],
            ],
            'src'       => [
                'description' => __('Full size image URL.', 'woocommerce'),
                'type'        => 'string',
                'format'      => 'uri',
                'context'     => [ 'view', 'edit', 'embed' ],
            ],
            'thumbnail' => [
                'description' => __('Thumbnail URL.', 'woocommerce'),
                'type'        => 'string',
                'format'      => 'uri',
                'context'     => [ 'view', 'edit', 'embed' ],
            ],
            'srcset'    => [
                'description' => __('Thumbnail srcset for responsive images.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit', 'embed' ],
            ],
            'sizes'     => [
                'description' => __('Thumbnail sizes for responsive images.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit', 'embed' ],
            ],
            'name'      => [
                'description' => __('Image name.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit', 'embed' ],
            ],
            'alt'       => [
                'description' => __('Image alternative text.', 'woocommerce'),
                'type'        => 'string',
                'context'     => [ 'view', 'edit', 'embed' ],
            ],
        ];
    }

    /**
     * Convert a WooCommerce product into an object suitable for the response.
     *
     * @param int $attachment_id Image attachment ID.
     * @return array
     */
    public function get_item_response($attachment_id): array
    {
        if (! $attachment_id) {
            return [];
        }

        $attachment = wp_get_attachment_image_src($attachment_id, 'full');

        if (! is_array($attachment)) {
            return [];
        }

        $thumbnail = wp_get_attachment_image_src($attachment_id, 'woocommerce_thumbnail');

        return [
            'id'        => (int) $attachment_id,
            'src'       => current($attachment),
            'thumbnail' => current($thumbnail),
            'srcset'    => (string) wp_get_attachment_image_srcset($attachment_id, 'full'),
            'sizes'     => (string) wp_get_attachment_image_sizes($attachment_id, 'full'),
            'name'      => get_the_title($attachment_id),
            'alt'       => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
        ];
    }

}
