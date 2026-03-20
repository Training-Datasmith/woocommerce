<?php

declare (strict_types=1);
/**
 * WooCommerce Product Group Block class.
 */
namespace Automattic\Woo_Commerce\Internal\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Template_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Container_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Group_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Section_Interface;
use Automattic\Woo_Commerce\Internal\Admin\Block_Templates\Block_Container_Trait;
/**
 * Class for Group block.
 */
class Group extends Product_Block implements Group_Interface
{
    use Block_Container_Trait;
    // phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
    /**
     * Group Block constructor.
     *
     * @param array                   $config The block configuration.
     * @param BlockTemplateInterface  $root_template The block template that this block belongs to.
     * @param ContainerInterface|null $parent The parent block container.
     *
     * @throws \ValueError If the block configuration is invalid.
     * @throws \ValueError If the parent block container does not belong to the same template as the block.
     * @throws \InvalidArgumentException If blockName key and value are passed into block configuration.
     */
    public function __construct(array $config, Block_Template_Interface &$root_template, ?Container_Interface &$parent = null)
    {
        if (!empty($config['blockName'])) {
            throw new \InvalidArgumentException('Unexpected key "blockName", this defaults to "woocommerce/product-tab".');
        }
        if ($config['id'] && (empty($config['attributes']) || empty($config['attributes']['id']))) {
            $config['attributes'] = empty($config['attributes']) ? [] : $config['attributes'];
            $config['attributes']['id'] = $config['id'];
        }
        parent::__construct(array_merge(['blockName' => 'woocommerce/product-tab'], $config), $root_template, $parent);
    }
    // phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
    /**
     * Add a section block type to this template.
     *
     * @param array $block_config The block data.
     */
    public function add_section(array $block_config): Section_Interface
    {
        $block = new Section($block_config, $this->get_root_template(), $this);
        return $this->add_inner_block($block);
    }
}