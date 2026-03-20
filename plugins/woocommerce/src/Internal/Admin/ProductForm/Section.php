<?php

declare (strict_types=1);
/**
 * Handles product form section related methods.
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Product_Form;

/**
 * Section class.
 */
class Section extends Component
{
    /**
     * Constructor
     *
     * @param string $id Section id.
     * @param string $plugin_id Plugin id.
     * @param array  $additional_args Array containing additional arguments.
     *     $args = array(
     *       'order'       => (int) Section order.
     *       'title'       => (string) Section description.
     *       'description' => (string) Section description.
     *     ).
     * @throws \Exception If there are missing arguments.
     */
    public function __construct($id, $plugin_id, $additional_args)
    {
        parent::__construct($id, $plugin_id, $additional_args);
        $this->required_arguments = ['title'];
        $missing_arguments = self::get_missing_arguments($additional_args);
        if (count($missing_arguments) > 0) {
            throw new \Exception(sprintf(
                /* translators: 1: Missing arguments list. */
                esc_html__('You are missing required arguments of WooCommerce ProductForm Section: %1$s', 'woocommerce'),
                join(', ', $missing_arguments)
            ));
        }
    }
}