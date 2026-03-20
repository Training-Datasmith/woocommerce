<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email_Editor;

use Automattic\Woo_Commerce\Email_Editor\Engine\Personalization_Tags\Personalization_Tags_Registry;
use Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Customer_Tags_Provider;
use Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Order_Tags_Provider;
use Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Site_Tags_Provider;
use Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Store_Tags_Provider;
defined('ABSPATH') || exit;
/**
 * Manages personalization tags for WooCommerce emails.
 *
 * @internal
 */
class Personalization_Tag_Manager
{
    /**
     * The customer related tags provider.
     */
    private readonly \Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Customer_Tags_Provider $customer_tags_provider;
    /**
     * The order related tags provider.
     */
    private readonly \Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Order_Tags_Provider $order_tags_provider;
    /**
     * The site related tags provider.
     */
    private readonly \Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Site_Tags_Provider $site_tags_provider;
    /**
     * The store related tags provider.
     */
    private readonly \Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags\Store_Tags_Provider $store_tags_provider;
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->customer_tags_provider = new Customer_Tags_Provider();
        $this->order_tags_provider = new Order_Tags_Provider();
        $this->site_tags_provider = new Site_Tags_Provider();
        $this->store_tags_provider = new Store_Tags_Provider();
    }
    /**
     * Initialize the personalization tag manager.
     *
     * @internal
     */
    final public function init(): void
    {
        add_filter('woocommerce_email_editor_register_personalization_tags', $this->register_personalization_tags(...));
    }
    /**
     * Register WooCommerce personalization tags with the registry.
     *
     * @param Personalization_Tags_Registry $registry The personalization tags registry.
     */
    public function register_personalization_tags(Personalization_Tags_Registry $registry): Personalization_Tags_Registry
    {
        $this->customer_tags_provider->register_tags($registry);
        $this->order_tags_provider->register_tags($registry);
        $this->site_tags_provider->register_tags($registry);
        $this->store_tags_provider->register_tags($registry);
        return $registry;
    }
}