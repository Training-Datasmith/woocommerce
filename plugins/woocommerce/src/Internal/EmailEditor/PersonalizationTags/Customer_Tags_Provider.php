<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags;

use Automattic\Woo_Commerce\Email_Editor\Engine\Personalization_Tags\Personalization_Tag;
use Automattic\Woo_Commerce\Email_Editor\Engine\Personalization_Tags\Personalization_Tags_Registry;
use Automattic\Woo_Commerce\Internal\Email_Editor\Integration;
/**
 * Provider for customer-related personalization tags.
 *
 * @internal
 */
class Customer_Tags_Provider extends Abstract_Tag_Provider
{
    /**
     * Register customer tags with the registry.
     *
     * @param Personalization_Tags_Registry $registry The personalization tags registry.
     */
    public function register_tags(Personalization_Tags_Registry $registry): void
    {
        $registry->register(new Personalization_Tag(__('Customer Email', 'woocommerce'), 'woocommerce/customer-email', __('Customer', 'woocommerce'), function (array $context): string {
            if (isset($context['order'])) {
                return $context['order']->get_billing_email() ?? '';
            }
            return $context['recipient_email'] ?? '';
        }, [], null, [Integration::EMAIL_POST_TYPE]));
        $registry->register(new Personalization_Tag(__('Customer First Name', 'woocommerce'), 'woocommerce/customer-first-name', __('Customer', 'woocommerce'), function (array $context): string {
            if (isset($context['order'])) {
                return $context['order']->get_billing_first_name() ?? '';
            }
            if (isset($context['wp_user'])) {
                return $context['wp_user']->first_name ?? '';
            }
            return '';
        }, [], null, [Integration::EMAIL_POST_TYPE]));
        $registry->register(new Personalization_Tag(__('Customer Last Name', 'woocommerce'), 'woocommerce/customer-last-name', __('Customer', 'woocommerce'), function (array $context): string {
            if (isset($context['order'])) {
                return $context['order']->get_billing_last_name() ?? '';
            }
            if (isset($context['wp_user'])) {
                return $context['wp_user']->last_name ?? '';
            }
            return '';
        }, [], null, [Integration::EMAIL_POST_TYPE]));
        $registry->register(new Personalization_Tag(__('Customer Full Name', 'woocommerce'), 'woocommerce/customer-full-name', __('Customer', 'woocommerce'), function (array $context): string {
            if (isset($context['order'])) {
                return $context['order']->get_formatted_billing_full_name() ?? '';
            }
            if (isset($context['wp_user'])) {
                $first_name = $context['wp_user']->first_name ?? '';
                $last_name = $context['wp_user']->last_name ?? '';
                return trim("{$first_name} {$last_name}");
            }
            return '';
        }, [], null, [Integration::EMAIL_POST_TYPE]));
        $registry->register(new Personalization_Tag(__('Customer Username', 'woocommerce'), 'woocommerce/customer-username', __('Customer', 'woocommerce'), function (array $context): string {
            if (isset($context['wp_user'])) {
                return stripslashes($context['wp_user']->user_login ?? '');
            }
            return '';
        }, [], null, [Integration::EMAIL_POST_TYPE]));
        $registry->register(new Personalization_Tag(__('Customer Country', 'woocommerce'), 'woocommerce/customer-country', __('Customer', 'woocommerce'), function (array $context): string {
            if (isset($context['order'])) {
                $country_code = $context['order']->get_billing_country();
                return WC()->countries->countries[$country_code] ?? $country_code ?? '';
            }
            return '';
        }, [], null, [Integration::EMAIL_POST_TYPE]));
    }
}