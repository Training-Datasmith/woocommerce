<?php

/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Fallback;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Button;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Collection;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Image;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Price;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Product_Sale_Badge;

/**
 * Initializes the WooCommerce blocks renderers.
 */
class Initializer
{
    /**
     * List of supported WooCommerce blocks in the email editor.
     */
    public const ALLOWED_BLOCK_TYPES = [
        'woocommerce/product-collection',
        'woocommerce/product-image',
        'woocommerce/product-price',
        'woocommerce/product-button',
        'woocommerce/product-sale-badge',
        'woocommerce/coupon-code',
    ];

    /**
     * Cache renderers by block name.
     *
     * @var array<string, Abstract_Block_Renderer>
     */
    private array $renderers = [];

    /**
     * Set `supports.email = true` and configure render_email_callback for supported blocks.
     *
     * @param array $settings Block settings.
     */
    public function update_block_settings(array $settings): array
    {
        if (in_array($settings['name'], self::ALLOWED_BLOCK_TYPES, true)) {
            $settings['supports']['email']     = true;
            $settings['render_email_callback'] = $this->render_block(...);
        }

        return $settings;
    }

    /**
     * Returns the block content rendered by the block renderer.
     *
     * @param string            $block_content Block content.
     * @param array             $parsed_block Parsed block settings.
     * @param Rendering_Context $rendering_context Rendering context.
     */
    public function render_block(string $block_content, array $parsed_block, Rendering_Context $rendering_context): string
    {
        if (isset($parsed_block['blockName'])) {
            $block_renderer = $this->get_block_renderer($parsed_block['blockName']);
            return $block_renderer->render($block_content, $parsed_block, $rendering_context);
        }

        return $block_content;
    }

    /**
     * Return an instance of Abstract_Block_Renderer by the block name.
     *
     * @param string $block_name Block name.
     */
    private function get_block_renderer(string $block_name): Abstract_Block_Renderer
    {
        if (isset($this->renderers[ $block_name ])) {
            return $this->renderers[ $block_name ];
        }

        $renderer = match ($block_name) {
            'woocommerce/product-image' => new Product_Image(),
            'woocommerce/product-price' => new Product_Price(),
            'woocommerce/product-sale-badge' => new Product_Sale_Badge(),
            'woocommerce/product-collection' => new Product_Collection(),
            'woocommerce/product-button' => new Product_Button(),
            default => new Fallback(),
        };

        $this->renderers[ $block_name ] = $renderer;
        return $renderer;
    }
}
