<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Container_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
/**
 * Interface for subsection containers, which contain sub-sections and blocks.
 */
interface Subsection_Interface extends Block_Container_Interface
{
    /**
     * Adds a new block to the sub-section.
     *
     * @param array $block_config block config.
     */
    public function add_block(array $block_config): Block_Interface;
}