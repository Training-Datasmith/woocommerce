<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Domain\Services\Checkout_Fields_Schema;

use Automattic\Woo_Commerce\Store_Api\Schema_Controller;
use Automattic\Woo_Commerce\Store_Api\Schemas\V1\Billing_Address_Schema;
use Automattic\Woo_Commerce\Store_Api\Schemas\V1\Cart_Schema;
use Automattic\Woo_Commerce\Store_Api\Schemas\V1\Shipping_Address_Schema;
use Automattic\Woo_Commerce\Store_Api\Store_Api;
use Automattic\Woo_Commerce\Store_Api\Utilities\Cart_Controller;
use Automattic\Woo_Commerce\Store_Api\Utilities\Local_Pickup_Utils;
use Automattic\Woo_Commerce\Utilities\Number_Util;
use WC_Cart;
use WC_Customer;
/**
 * DocumentObject class.
 *
 * This will combine and format given cart/customer/checkout data into a standard object format that can be queried through
 * JSON. This is used for conditional fields and validation during checkout.
 */
class Document_Object
{
    /**
     * Docuemnt object context which may adjust the schema response.
     *
     * @var null|string
     */
    protected $context;
    /**
     * Valid contexts.
     *
     * @var array
     */
    protected $valid_contexts = ['shipping_address', 'billing_address', 'contact', 'order'];
    /**
     * The cart object.
     *
     * @var WC_Cart|null
     */
    protected $cart;
    /**
     * The customer object.
     *
     * @var WC_Customer|null
     */
    protected $customer;
    /**
     * Cart controller class instance.
     */
    protected \Automattic\Woo_Commerce\Store_Api\Utilities\Cart_Controller $cart_controller;
    /**
     * Schema controller class instance.
     *
     * @var SchemaController
     */
    protected $schema_controller;
    /**
     * The constructor.
     *
     * @param array $request_data Data that overrides the default values.
     */
    public function __construct(protected array $request_data = [])
    {
        $this->cart_controller = new Cart_Controller();
        $this->schema_controller = Store_Api::container()->get(Schema_Controller::class);
    }
    /**
     * Set document object context.
     *
     * @param null|string $context Context to set.
     */
    public function set_context($context = null): void
    {
        if (!in_array($context, $this->valid_contexts, true)) {
            return;
        }
        $this->context = $context;
    }
    /**
     * Set the customer object.
     *
     * @param WC_Customer $customer The customer object.
     */
    public function set_customer(WC_Customer $customer): void
    {
        $this->customer = $customer;
    }
    /**
     * Set the cart object.
     *
     * @param WC_Cart $cart The cart object.
     */
    public function set_cart(WC_Cart $cart): void
    {
        $this->cart = $cart;
    }
    /**
     * Gets a subset of cart data.
     *
     * @return array The cart data.
     */
    protected function get_cart_data()
    {
        $cart_data = Store_Api::container()->get(Schema_Controller::class)->get(Cart_Schema::IDENTIFIER)->get_item_response($this->cart);
        $selected_shipping_rates = array_filter(array_map(function (array $package) {
            $selected_rate = array_search(true, array_column($package['shipping_rates'], 'selected'), true);
            return false !== $selected_rate && isset($package['shipping_rates'][$selected_rate]) ? $package['shipping_rates'][$selected_rate] : null;
        }, $cart_data['shipping_rates']));
        $local_pickup_method_ids = Local_Pickup_Utils::get_local_pickup_method_ids();
        return wp_parse_args($this->request_data['cart'] ?? [], ['coupons' => array_values(wc_list_pluck($cart_data['coupons'], 'code')), 'shipping_rates' => array_values(wc_list_pluck($selected_shipping_rates, 'rate_id')), 'items' => array_merge(...array_map(fn(array $item) => array_fill(0, (int) Number_Util::ceil($item['quantity']), $item['id']), $cart_data['items'])), 'items_type' => array_unique(array_values(wc_list_pluck($cart_data['items'], 'type'))), 'items_count' => $cart_data['items_count'], 'items_weight' => $cart_data['items_weight'], 'needs_shipping' => $cart_data['needs_shipping'], 'prefers_collection' => count(array_intersect($local_pickup_method_ids, wc_list_pluck($selected_shipping_rates, 'method_id'))) > 0, 'totals' => ['total_price' => (int) $cart_data['totals']->total_price, 'total_tax' => (int) $cart_data['totals']->total_tax], 'extensions' => (object) $cart_data['extensions']]);
    }
    /**
     * Get checkout data.
     *
     * @return array Checkout data context.
     */
    protected function get_checkout_data()
    {
        return $this->request_data['checkout'] ?? [];
    }
    /**
     * Get the customer data.
     *
     * @return array The customer data.
     */
    protected function get_customer_data(): array
    {
        $customer_data = ['id' => $this->request_data['customer']['id'] ?? $this->customer->get_id(), 'shipping_address' => wp_parse_args($this->request_data['customer']['shipping_address'] ?? (object) [], $this->schema_controller->get(Shipping_Address_Schema::IDENTIFIER)->get_item_response($this->customer)), 'billing_address' => wp_parse_args($this->request_data['customer']['billing_address'] ?? (object) [], $this->schema_controller->get(Billing_Address_Schema::IDENTIFIER)->get_item_response($this->customer)), 'additional_fields' => $this->request_data['customer']['additional_fields'] ?? (object) []];
        if ('shipping_address' === $this->context) {
            $customer_data['address'] = $customer_data['shipping_address'];
        }
        if ('billing_address' === $this->context) {
            $customer_data['address'] = $customer_data['billing_address'];
        }
        return $customer_data;
    }
    /**
     * Get the data for the document object.
     *
     * This isn't a 1:1 match with Store API because some data is simplified to make it easier to parse as JSON.
     *
     * @return array The data for the document object.
     */
    public function get_data(): array
    {
        // Get cart and customer objects before returning data if they are null.
        if (is_null($this->cart)) {
            $this->cart = $this->cart_controller->get_cart_for_response();
        }
        if (is_null($this->customer)) {
            $this->customer = !empty(WC()->customer) ? WC()->customer : new WC_Customer();
        }
        return ['cart' => $this->get_cart_data(), 'customer' => $this->get_customer_data(), 'checkout' => $this->get_checkout_data()];
    }
    /**
     * Get the current context.
     *
     * @return null|string The context.
     */
    public function get_context()
    {
        return $this->context;
    }
}