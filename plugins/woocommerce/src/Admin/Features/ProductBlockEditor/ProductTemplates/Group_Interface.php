<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Container_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
/**
 * Interface for group containers, which contain sections and blocks.
 */
interface Group_Interface extends Block_Container_Interface
{
    /**
     * Adds a new section to the group
     *
     * @param array $block_config block config.
     * @return SectionInterface new block section.
     */
    public function add_section(array $block_config): Section_Interface;
    /**
     * Adds a new block to the group.
     *
     * @param array $block_config block config.
     */
    public function add_block(array $block_config): Block_Interface;
}