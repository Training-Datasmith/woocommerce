<?php

declare (strict_types=1);
/**
 * WooCommerce Product Block class.
 */
namespace Automattic\Woo_Commerce\Internal\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Container_Interface;
use Automattic\Woo_Commerce\Internal\Admin\Block_Templates\Abstract_Block;
use Automattic\Woo_Commerce\Internal\Admin\Block_Templates\Block_Container_Trait;
/**
 * Class for Product block.
 */
class Product_Block extends Abstract_Block implements Container_Interface
{
    use Block_Container_Trait;
    /**
     * Adds block to the section block.
     *
     * @param array $block_config The block data.
     */
    public function &add_block(array $block_config): Block_Interface
    {
        $block = new Product_Block($block_config, $this->get_root_template(), $this);
        return $this->add_inner_block($block);
    }
}