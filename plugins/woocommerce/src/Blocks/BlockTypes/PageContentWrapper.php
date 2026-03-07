<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Used in templates to wrap page content. Allows content to be populated at template level.
 *
 * @internal
 */
class PageContentWrapper extends AbstractBlock
{
    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'page-content-wrapper';

    /**
     * It isn't necessary to register block assets.
     *
     * @param string $key Data to get, or default to everything.
     * @return array|string|null
     */
    protected function get_block_type_script($key = null): null
    {
        return null;
    }

    /**
     * Get the frontend style handle for this block type.
     */
    protected function get_block_type_style(): null
    {
        return null;
    }
}
