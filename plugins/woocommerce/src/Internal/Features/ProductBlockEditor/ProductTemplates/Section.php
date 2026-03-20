<?php

declare (strict_types=1);
/**
 * WooCommerce Section Block class.
 */
namespace Automattic\Woo_Commerce\Internal\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Template_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Container_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Section_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Subsection_Interface;
/**
 * Class for Section block.
 */
class Section extends Product_Block implements Section_Interface
{
    // phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
    /**
     * Section Block constructor.
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
            throw new \InvalidArgumentException('Unexpected key "blockName", this defaults to "woocommerce/product-section".');
        }
        parent::__construct(array_merge(['blockName' => 'woocommerce/product-section'], $config), $root_template, $parent);
    }
    // phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
    /**
     * Add a sub-section block type to this template.
     *
     * @param array $block_config The block data.
     */
    public function add_subsection(array $block_config): Subsection_Interface
    {
        $block = new Subsection($block_config, $this->get_root_template(), $this);
        return $this->add_inner_block($block);
    }
    /**
     * Add a sub-section block type to this template.
     *
     * @deprecated 8.6.0
     *
     * @param array $block_config The block data.
     */
    public function add_section(array $block_config): Subsection_Interface
    {
        wc_deprecated_function('add_section', '8.6.0', 'add_subsection');
        return $this->add_subsection($block_config);
    }
}