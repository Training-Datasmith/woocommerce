<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Container_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
/**
 * Interface for section containers, which contain sub-sections and blocks.
 */
interface Section_Interface extends Block_Container_Interface
{
    /**
     * Adds a new sub-section to the section.
     *
     * @param array $block_config block config.
     * @return SubsectionInterface new block sub-section.
     */
    public function add_subsection(array $block_config): Subsection_Interface;
    /**
     * Adds a new block to the section.
     *
     * @param array $block_config block config.
     */
    public function add_block(array $block_config): Block_Interface;
    /**
     * Adds a new sub-section to the section.
     *
     * @deprecated 8.6.0
     *
     * @param array $block_config The block data.
     */
    public function add_section(array $block_config): Subsection_Interface;
}