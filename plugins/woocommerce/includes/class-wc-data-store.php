<?php

declare(strict_types=1);
/**
 * WC Data Store.
 *
 * @package WooCommerce\Classes
 * @since   3.0.0
 * @version 3.0.0
 */

defined('ABSPATH') || exit;

/**
 * Data store class.
 */
class WC_Data_Store
{
    /**
     * Contains an instance of the data store class that we are working with.
     *
     * @var WC_Data_Store
     */
    private $instance;

    /**
     * Contains an array of default WC supported data stores.
     * Format of object name => class name.
     * Example: 'product' => 'WC_Product_Data_Store_CPT'
     * You can also pass something like product_<type> for product stores and
     * that type will be used first when available, if a store is requested like
     * this and doesn't exist, then the store would fall back to 'product'.
     * Ran through `woocommerce_data_stores`.
     *
     * @var array
     */
    private $stores = [
        'coupon'                => 'WC_Coupon_Data_Store_CPT',
        'customer'              => 'WC_Customer_Data_Store',
        'customer-download'     => 'WC_Customer_Download_Data_Store',
        'customer-download-log' => 'WC_Customer_Download_Log_Data_Store',
        'customer-session'      => 'WC_Customer_Data_Store_Session',
        'order'                 => 'WC_Order_Data_Store_CPT',
        'order-refund'          => 'WC_Order_Refund_Data_Store_CPT',
        'order-item'            => 'WC_Order_Item_Data_Store',
        'order-item-coupon'     => 'WC_Order_Item_Coupon_Data_Store',
        'order-item-fee'        => 'WC_Order_Item_Fee_Data_Store',
        'order-item-product'    => 'WC_Order_Item_Product_Data_Store',
        'order-item-shipping'   => 'WC_Order_Item_Shipping_Data_Store',
        'order-item-tax'        => 'WC_Order_Item_Tax_Data_Store',
        'payment-token'         => 'WC_Payment_Token_Data_Store',
        'product'               => 'WC_Product_Data_Store_CPT',
        'product-grouped'       => 'WC_Product_Grouped_Data_Store_CPT',
        'product-variable'      => 'WC_Product_Variable_Data_Store_CPT',
        'product-variation'     => 'WC_Product_Variation_Data_Store_CPT',
        'shipping-zone'         => 'WC_Shipping_Zone_Data_Store',
        'webhook'               => 'WC_Webhook_Data_Store',
    ];

    /**
     * Contains the name of the current data store's class name.
     */
    private string $current_class_name = '';

    /**
     * Tells WC_Data_Store which object (coupon, product, order, etc)
     * store we want to work with.
     *
     * @throws Exception When validation fails.
     * @param string $object_type Name of object.
     */
    public function __construct(/**
     * The object type this store works with.
     */
        private $object_type
    ) {
        $this->stores      = apply_filters('woocommerce_data_stores', $this->stores);

        // If this object type can't be found, check to see if we can load one
        // level up (so if product-type isn't found, we try product).
        if (! array_key_exists($this->object_type, $this->stores)) {
            $pieces      = explode('-', $this->object_type);
            $this->object_type = $pieces[0];
        }

        if (array_key_exists($this->object_type, $this->stores)) {
            $store = apply_filters('woocommerce_' . $this->object_type . '_data_store', $this->stores[ $this->object_type ]);
            if (is_object($store)) {
                if (! $store instanceof WC_Object_Data_Store_Interface) {
                    throw new Exception(__('Invalid data store.', 'woocommerce'));
                }
                $this->current_class_name = $store::class;
                $this->instance           = $store;
            } else {
                if (! class_exists($store)) {
                    throw new Exception(__('Invalid data store.', 'woocommerce'));
                }
                $this->current_class_name = $store;
                $this->instance           = new $store();
            }
        } else {
            throw new Exception(__('Invalid data store.', 'woocommerce'));
        }
    }

    /**
     * Only store the object type to avoid serializing the data store instance.
     *
     * @return array
     */
    public function __sleep()
    {
        return [ 'object_type' ];
    }

    /**
     * Re-run the constructor with the object type.
     *
     * @throws Exception When validation fails.
     */
    public function __wakeup()
    {
        $this->__construct($this->object_type);
    }

    /**
     * Loads a data store.
     *
     * @param string $object_type Name of object.
     *
     * @since 3.0.0
     * @throws Exception When validation fails.
     */
    public static function load($object_type): \WC_Data_Store
    {
        return new WC_Data_Store($object_type);
    }

    /**
     * Returns the class name of the current data store.
     *
     * @since 3.0.0
     * @return string
     */
    public function get_current_class_name()
    {
        return $this->current_class_name;
    }

    /**
     * Reads an object from the data store.
     *
     * @since 3.0.0
     * @param WC_Data $data WooCommerce data instance.
     */
    public function read(&$data): void
    {
        $this->instance->read($data);
    }

    /**
     * Reads multiple objects from the data store.
     *
     * @since 6.9.0
     * @param array[WC_Data] $objects Array of object instances to read.
     */
    public function read_multiple(&$objects = []): void
    {
        // If the datastore allows for bulk-reading, use it.
        if (is_callable($this->instance->read_multiple(...))) {
            $this->instance->read_multiple($objects);
        } else {
            foreach ($objects as &$obj) {
                $this->read($obj);
            }
        }
    }

    /**
     * Create an object in the data store.
     *
     * @since 3.0.0
     * @param WC_Data $data WooCommerce data instance.
     */
    public function create(&$data): void
    {
        $this->instance->create($data);
    }

    /**
     * Update an object in the data store.
     *
     * @since 3.0.0
     * @param WC_Data $data WooCommerce data instance.
     */
    public function update(&$data): void
    {
        $this->instance->update($data);
    }

    /**
     * Delete an object from the data store.
     *
     * @since 3.0.0
     * @param WC_Data $data WooCommerce data instance.
     * @param array   $args Array of args to pass to the delete method.
     */
    public function delete(&$data, $args = []): void
    {
        $this->instance->delete($data, $args);
    }

    /**
     * Data stores can define additional functions (for example, coupons have
     * some helper methods for increasing or decreasing usage). This passes
     * through to the instance if that function exists.
     *
     * @since 3.0.0
     * @param string $method     Method.
     * @param mixed  $parameters Parameters.
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (is_callable([ $this->instance, $method ])) {
            $object     = array_shift($parameters);
            $parameters = array_merge([ &$object ], $parameters);
            return $this->instance->$method(...$parameters);
        }
    }

    /**
     * Check if the data store we are working with has a callable method.
     *
     * @param string $method Method name.
     *
     * @return bool Whether the passed method is callable.
     */
    public function has_callable(string $method): bool
    {
        return is_callable([ $this->instance, $method ]);
    }
}
