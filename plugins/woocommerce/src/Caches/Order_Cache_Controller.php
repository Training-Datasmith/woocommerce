<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Caches;

use Automattic\Woo_Commerce\Utilities\Order_Util;
/**
 * A class to control the usage of the orders cache.
 */
class Order_Cache_Controller
{
    /**
     * The backup value of the cache usage enable status, stored while the cache is temporarily disabled.
     */
    private ?bool $orders_cache_usage_backup = null;
    /**
     * Class initialization, invoked by the DI container.
     *
     * @internal
     * @param OrderCache $order_cache The order cache engine to use.
     */
    final public function init(Order_Cache $order_cache)
    {
    }
    /**
     * Whether order cache usage is enabled. Currently, linked to custom orders' table usage.
     *
     * @return bool True if the order cache is enabled.
     */
    public function orders_cache_usage_is_enabled(): bool
    {
        return Order_Util::custom_orders_table_usage_is_enabled();
    }
    /**
     * Temporarily disable the order cache if it's enabled.
     *
     * This is a purely in-memory operation: a variable is created with the value
     * of the current enable status for the feature, and this variable
     * is checked by orders_cache_usage_is_enabled. In the next request the
     * feature will be again enabled or not depending on how the feature is set.
     */
    public function temporarily_disable_orders_cache_usage(): void
    {
        if ($this->orders_cache_usage_is_temporarly_disabled()) {
            return;
        }
        $this->orders_cache_usage_backup = $this->orders_cache_usage_is_enabled();
    }
    /**
     * Check if the order cache has been temporarily disabled.
     *
     * @return bool True if the order cache is currently temporarily disabled.
     */
    public function orders_cache_usage_is_temporarly_disabled(): bool
    {
        return null !== $this->orders_cache_usage_backup;
    }
    /**
     * Restore the order cache usage that had been temporarily disabled.
     */
    public function maybe_restore_orders_cache_usage(): void
    {
        $this->orders_cache_usage_backup = null;
    }
}