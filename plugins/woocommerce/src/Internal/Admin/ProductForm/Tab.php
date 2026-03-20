<?php

declare (strict_types=1);
/**
 * Handles product form tab related methods.
 */
namespace Automattic\Woo_Commerce\Internal\Admin\Product_Form;

/**
 * Field class.
 */
class Tab extends Component
{
    /**
     * Constructor
     *
     * @param string $id Field id.
     * @param string $plugin_id Plugin id.
     * @param array  $additional_args Array containing the necessary arguments.
     *     $args = array(
     *       'name'            => (string) Tab name. Required.
     *       'title'         => (string) Tab title. Required.
     *       'order'           => (int) Tab order.
     *       'properties'      => (array) Tab properties.
     *     ).
     * @throws \Exception If there are missing arguments.
     */
    public function __construct($id, $plugin_id, $additional_args)
    {
        parent::__construct($id, $plugin_id, $additional_args);
        $this->required_arguments = ['name', 'title'];
        $missing_arguments = self::get_missing_arguments($additional_args);
        if (count($missing_arguments) > 0) {
            throw new \Exception(sprintf(
                /* translators: 1: Missing arguments list. */
                esc_html__('You are missing required arguments of WooCommerce ProductForm Tab: %1$s', 'woocommerce'),
                join(', ', $missing_arguments)
            ));
        }
    }
}