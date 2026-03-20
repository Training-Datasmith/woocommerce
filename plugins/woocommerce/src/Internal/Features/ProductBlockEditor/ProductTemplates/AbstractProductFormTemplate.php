<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Group_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Product_Form_Template_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Section_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Subsection_Interface;
use Automattic\Woo_Commerce\Internal\Admin\Block_Templates\Abstract_Block_Template;
/**
 * Block template class.
 */
abstract class Abstract_Product_Form_Template extends Abstract_Block_Template implements Product_Form_Template_Interface
{
    /**
     * Get the template area.
     */
    public function get_area(): string
    {
        return 'product-form';
    }
    /**
     * Get a group block by ID.
     *
     * @param string $group_id The group block ID.
     * @throws \UnexpectedValueException If block is not of type GroupInterface.
     */
    public function get_group_by_id(string $group_id): ?Group_Interface
    {
        $group = $this->get_block($group_id);
        if ($group && !$group instanceof Group_Interface) {
            throw new \UnexpectedValueException('Block with specified ID is not a group.');
        }
        return $group;
    }
    /**
     * Get a section block by ID.
     *
     * @param string $section_id The section block ID.
     * @throws \UnexpectedValueException If block is not of type SectionInterface.
     */
    public function get_section_by_id(string $section_id): ?Section_Interface
    {
        $section = $this->get_block($section_id);
        if ($section && !$section instanceof Section_Interface) {
            throw new \UnexpectedValueException('Block with specified ID is not a section.');
        }
        return $section;
    }
    /**
     * Get a subsection block by ID.
     *
     * @param string $subsection_id The subsection block ID.
     * @throws \UnexpectedValueException If block is not of type SubsectionInterface.
     */
    public function get_subsection_by_id(string $subsection_id): ?Subsection_Interface
    {
        $subsection = $this->get_block($subsection_id);
        if ($subsection && !$subsection instanceof Subsection_Interface) {
            throw new \UnexpectedValueException('Block with specified ID is not a subsection.');
        }
        return $subsection;
    }
    /**
     * Get a block by ID.
     *
     * @param string $block_id The block block ID.
     */
    public function get_block_by_id(string $block_id): ?Block_Interface
    {
        return $this->get_block($block_id);
    }
    /**
     * Add a custom block type to this template.
     *
     * @param array $block_config The block data.
     */
    public function add_group(array $block_config): Group_Interface
    {
        $block = new Group($block_config, $this->get_root_template(), $this);
        return $this->add_inner_block($block);
    }
}