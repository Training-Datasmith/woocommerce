<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Template_Interface;
/**
 * Interface for block containers.
 */
interface Product_Form_Template_Interface extends Block_Template_Interface
{
    /**
     * Adds a new group block.
     *
     * @param array $block_config block config.
     * @return GroupInterface new group block.
     */
    public function add_group(array $block_config): Group_Interface;
    /**
     * Gets Group block by id.
     *
     * @param string $group_id group id.
     */
    public function get_group_by_id(string $group_id): ?Group_Interface;
    /**
     * Gets Section block by id.
     *
     * @param string $section_id section id.
     */
    public function get_section_by_id(string $section_id): ?Section_Interface;
    /**
     * Gets subsection block by id.
     *
     * @param string $subsection_id subsection id.
     */
    public function get_subsection_by_id(string $subsection_id): ?Subsection_Interface;
    /**
     * Gets Block by id.
     *
     * @param string $block_id block id.
     */
    public function get_block_by_id(string $block_id): ?Block_Interface;
}