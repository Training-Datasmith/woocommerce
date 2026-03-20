<?php

declare (strict_types=1);
/**
 * WooCommerce Subsection Block class.
 */
namespace Automattic\Woo_Commerce\Internal\Features\Product_Block_Editor\Product_Templates;

use Automattic\Woo_Commerce\Admin\Block_Templates\Block_Template_Interface;
use Automattic\Woo_Commerce\Admin\Block_Templates\Container_Interface;
use Automattic\Woo_Commerce\Admin\Features\Product_Block_Editor\Product_Templates\Subsection_Interface;
/**
 * Class for Subsection block.
 */
class Subsection extends Product_Block implements Subsection_Interface
{
    // phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
    /**
     * Subsection Block constructor.
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
            throw new \InvalidArgumentException('Unexpected key "blockName", this defaults to "woocommerce/product-subsection".');
        }
        parent::__construct(array_merge(['blockName' => 'woocommerce/product-subsection'], $config), $root_template, $parent);
    }
    // phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
}