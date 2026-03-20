<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Admin\Block_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Container_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
/**
 * Generic block with container properties to be used in BlockTemplate.
 */
class Block extends Abstract_Block implements Block_Container_Interface
{
    use Block_Container_Trait;
    /**
     * Add an inner block to this block.
     *
     * @param array $block_config The block data.
     */
    public function &add_block(array $block_config): Block_Interface
    {
        $block = new Block($block_config, $this->get_root_template(), $this);
        return $this->add_inner_block($block);
    }
}