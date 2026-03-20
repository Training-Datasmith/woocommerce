<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email_Editor\Email_Templates;

use Automattic\Woo_Commerce\Email_Editor\Validator\Builder;
defined('ABSPATH') || exit;
/**
 * API Controller for managing WooCommerce email templates via extending the post type API.
 *
 * @internal
 */
class Template_Api_Controller
{
    /**
     * Returns the sender settings for the given template.
     *
     * @param array $template_data - WP_Block_Template data.
     */
    public function get_template_data(array $template_data): array
    {
        $template_slug = $template_data['slug'] ?? null;
        if (Woo_Email_Template::TEMPLATE_SLUG !== $template_slug) {
            return [];
        }
        return ['sender_settings' => ['from_name' => get_option('woocommerce_email_from_name', get_bloginfo('name', 'display')), 'from_address' => get_option('woocommerce_email_from_address')]];
    }
    /**
     * Update WooCommerce specific data we store with Template.
     *
     * @param array              $data - WP_Block_Template data.
     * @param \WP_Block_Template $template_post - WP_Block_Template object.
     * @return \WP_Error|null Returns WP_Error if email validation fails, null otherwise.
     */
    public function save_template_data(array $data, \WP_Block_Template $template_post): ?\WP_Error
    {
        if (Woo_Email_Template::TEMPLATE_SLUG === $template_post->slug && isset($data['sender_settings'])) {
            $new_from_name = $data['sender_settings']['from_name'] ?? null;
            if (null !== $new_from_name) {
                update_option('woocommerce_email_from_name', $new_from_name);
            }
            $new_from_address = $data['sender_settings']['from_address'] ?? null;
            if (null === $new_from_address || !filter_var($new_from_address, FILTER_VALIDATE_EMAIL)) {
                return new \WP_Error('invalid_email_address', __('Invalid email address provided for sender settings', 'woocommerce'), ['status' => 400]);
            }
            update_option('woocommerce_email_from_address', $new_from_address);
        }
        return null;
    }
    /**
     * Get the schema for the template data.
     */
    public function get_template_data_schema(): array
    {
        return Builder::object(['sender_settings' => Builder::object(['preheader' => Builder::string(), 'preview_url' => Builder::string()])])->to_array();
    }
}