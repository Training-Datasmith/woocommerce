<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Block_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
/**
 * Block template class.
 */
class Block_Template extends Abstract_Block_Template
{
    /**
     * Get the template ID.
     */
    public function get_id(): string
    {
        return 'woocommerce-block-template';
    }
    /**
     * Add an inner block to this template.
     *
     * @param array $block_config The block data.
     */
    public function add_block(array $block_config): Block_Interface
    {
        $block = new Block($block_config, $this->get_root_template(), $this);
        return $this->add_inner_block($block);
    }
}