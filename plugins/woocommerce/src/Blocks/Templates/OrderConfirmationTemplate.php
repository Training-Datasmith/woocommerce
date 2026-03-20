<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

/**
 * OrderConfirmationTemplate class.
 *
 * @internal
 */
class Order_Confirmation_Template extends Abstract_Page_Template
{
    /**
     * The slug of the template.
     *
     * @var string
     */
    public const SLUG = 'order-confirmation';
    /**
     * Initialization method.
     */
    public function init(): void
    {
        add_action('wp_before_admin_bar_render', $this->remove_edit_page_link(...));
        add_filter('pre_get_document_title', $this->page_template_title(...));
        parent::init();
    }
    /**
     * Returns the title of the template.
     *
     * @return string
     */
    public function get_template_title()
    {
        return _x('Order Confirmation', 'Template name', 'woocommerce');
    }
    /**
     * Returns the description of the template.
     *
     * @return string
     */
    public function get_template_description()
    {
        return __('The Order Confirmation template serves as a receipt and confirmation of a successful purchase. It includes a summary of the ordered items, shipping, billing, and totals.', 'woocommerce');
    }
    /**
     * Remove edit page from admin bar.
     */
    public function remove_edit_page_link(): void
    {
        if ($this->is_active_template()) {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('edit');
        }
    }
    /**
     * Returns the page object assigned to this template/page.
     *
     * @return \WP_Post|null Post object or null.
     */
    protected function get_placeholder_page(): null
    {
        return null;
    }
    /**
     * True when viewing the Order Received endpoint.
     *
     * @return boolean
     */
    protected function is_active_template()
    {
        return is_wc_endpoint_url('order-received');
    }
}