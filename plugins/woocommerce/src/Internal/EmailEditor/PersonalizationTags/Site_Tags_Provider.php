<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags;

use Automattic\Woo_Commerce\Email_Editor\Engine\Personalization_Tags\Personalization_Tag;
use Automattic\Woo_Commerce\Email_Editor\Engine\Personalization_Tags\Personalization_Tags_Registry;
use Automattic\Woo_Commerce\Internal\Email_Editor\Integration;
use Automattic\Woo_Commerce\Internal\Orders\Point_Of_Sale_Order_Util;
use Automattic\Woo_Commerce\Internal\Settings\Point_Of_Sale_Default_Settings;
/**
 * Provider for site-related personalization tags.
 *
 * @internal
 */
class Site_Tags_Provider extends Abstract_Tag_Provider
{
    /**
     * Register site tags with the registry.
     *
     * @param Personalization_Tags_Registry $registry The personalization tags registry.
     */
    public function register_tags(Personalization_Tags_Registry $registry): void
    {
        $registry->register(new Personalization_Tag(__('Site Title', 'woocommerce'), 'woocommerce/site-title', __('Site', 'woocommerce'), function (array $context): string {
            if (isset($context['order']) && Point_Of_Sale_Order_Util::is_pos_order($context['order'])) {
                $store_name = get_option('woocommerce_pos_store_name');
                return htmlspecialchars_decode(empty($store_name) ? Point_Of_Sale_Default_Settings::get_default_store_name() : $store_name, ENT_QUOTES);
            }
            return htmlspecialchars_decode(get_bloginfo('name'));
        }, [], null, [Integration::EMAIL_POST_TYPE]));
        $registry->register(new Personalization_Tag(__('Homepage URL', 'woocommerce'), 'woocommerce/site-homepage-url', __('Site', 'woocommerce'), fn(): string => get_bloginfo('url'), [], null, [Integration::EMAIL_POST_TYPE]));
    }
}