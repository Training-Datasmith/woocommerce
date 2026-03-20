<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email_Editor;

use Automattic\Woo_Commerce\Email_Editor\Email_Editor_Container;
use Automattic\Woo_Commerce\Email_Editor\Engine\Dependency_Check;
use Automattic\Woo_Commerce\Email_Editor\Engine\Logger\Email_Editor_Logger;
use Automattic\Woo_Commerce\Internal\Admin\Email_Preview\Email_Preview;
use Automattic\Woo_Commerce\Internal\Email_Editor\Email_Patterns\Patterns_Controller;
use Automattic\Woo_Commerce\Internal\Email_Editor\Email_Templates\Template_Api_Controller;
use Automattic\Woo_Commerce\Internal\Email_Editor\Email_Templates\Templates_Controller;
use Automattic\Woo_Commerce\Internal\Email_Editor\Wc_Transactional_Emails\Wc_Transactional_Email_Posts_Manager;
use Automattic\Woo_Commerce\Internal\Email_Editor\Wc_Transactional_Emails\Wc_Transactional_Emails;
use WP_Post;
defined('ABSPATH') || exit;
/**
 * Integration class for the Email Editor functionality.
 */
class Integration
{
    public const EMAIL_POST_TYPE = 'woo_email';
    /**
     * The email editor page renderer instance.
     */
    private Page_Renderer $editor_page_renderer;
    /**
     * The dependency check instance.
     */
    private readonly Dependency_Check $dependency_check;
    /**
     * The template API controller instance.
     */
    private Template_Api_Controller $template_api_controller;
    /**
     * The email data API controller instance.
     */
    private Email_Api_Controller $email_api_controller;
    /**
     * The WC_Email instance.
     */
    private \WC_Email $wc_email_instance;
    /**
     * Constructor.
     */
    public function __construct()
    {
        $editor_container = Email_Editor_Container::container();
        $this->dependency_check = $editor_container->get(Dependency_Check::class);
    }
    /**
     * Initialize the integration.
     *
     * @internal
     */
    final public function init(): void
    {
        if (!$this->dependency_check->are_dependencies_met()) {
            // If dependencies are not met, do not initialize the email editor integration.
            return;
        }
        add_action('woocommerce_init', $this->initialize(...));
    }
    /**
     * Initialize the integration.
     */
    public function initialize(): void
    {
        $this->init_logger();
        $this->init_hooks();
        $this->extend_post_api();
        $this->extend_template_post_api();
        $this->register_hooks();
    }
    /**
     * Initialize the logger.
     */
    public function init_logger(): void
    {
        $editor_container = Email_Editor_Container::container();
        $logger = $editor_container->get(Email_Editor_Logger::class);
        // Register the WooCommerce logger with the email editor package.
        $logger->set_logger(new Logger(wc_get_logger()));
    }
    /**
     * Initialize hooks for required classes.
     */
    public function init_hooks(): void
    {
        $container = wc_get_container();
        $container->get(Patterns_Controller::class);
        $container->get(Templates_Controller::class);
        $container->get(Personalization_Tag_Manager::class);
        $container->get(Block_Email_Renderer::class);
        $container->get(Wc_Transactional_Emails::class);
        $this->editor_page_renderer = $container->get(Page_Renderer::class);
        $this->template_api_controller = $container->get(Template_Api_Controller::class);
        $this->email_api_controller = $container->get(Email_Api_Controller::class);
        // Using any email class to get the instance.
        $registered_emails = \WC_Emails::instance()->get_emails();
        if (isset($registered_emails['WC_Email_New_Order'])) {
            $this->wc_email_instance = $registered_emails['WC_Email_New_Order'];
        } else {
            $first_email_key = array_key_first($registered_emails);
            $this->wc_email_instance = $registered_emails[$first_email_key];
        }
    }
    /**
     * Register hooks for the integration.
     */
    public function register_hooks(): void
    {
        add_filter('woocommerce_email_editor_post_types', $this->add_email_post_type(...));
        add_filter('woocommerce_is_email_editor_page', $this->is_editor_page(...), 10, 1);
        add_filter('replace_editor', $this->replace_editor(...), 10, 2);
        add_action('before_delete_post', $this->delete_email_template_associated_with_email_editor_post(...), 10, 2);
        add_filter('woocommerce_email_editor_send_preview_email_rendered_data', $this->update_send_preview_email_rendered_data(...), 10, 2);
        add_filter('woocommerce_email_editor_send_preview_email_personalizer_context', $this->update_send_preview_email_personalizer_context(...));
        add_filter('woocommerce_email_editor_preview_post_template_html', $this->update_preview_post_template_html_data(...), 100, 1);
        add_action('woocommerce_email_editor_send_preview_email_before_wp_mail', $this->send_preview_email_before_wp_mail(...), 10);
        add_action('woocommerce_email_editor_send_preview_email_after_wp_mail', $this->send_preview_email_after_wp_mail(...), 10);
        add_filter('woocommerce_email_editor_send_preview_email_subject', $this->update_email_subject_for_send_preview_email(...), 10, 2);
    }
    /**
     * Add WooCommerce email post type to the list of supported post types.
     *
     * @param array $post_types List of post types.
     * @return array Modified list of post types.
     */
    public function add_email_post_type(array $post_types): array
    {
        $post_types[] = ['name' => self::EMAIL_POST_TYPE, 'args' => ['labels' => ['name' => __('Emails', 'woocommerce'), 'singular_name' => __('Email', 'woocommerce'), 'add_new_item' => __('Add Email', 'woocommerce'), 'edit_item' => __('Edit Email', 'woocommerce'), 'new_item' => __('New Email', 'woocommerce'), 'view_item' => __('View Email', 'woocommerce'), 'search_items' => __('Search Emails', 'woocommerce')], 'rewrite' => ['slug' => self::EMAIL_POST_TYPE], 'supports' => ['title', 'editor' => ['default-mode' => 'template-locked'], 'excerpt'], 'capability_type' => self::EMAIL_POST_TYPE, 'capabilities' => ['edit_post' => 'manage_woocommerce', 'read_post' => 'manage_woocommerce', 'delete_post' => 'manage_woocommerce', 'edit_posts' => 'manage_woocommerce', 'edit_others_posts' => 'manage_woocommerce', 'delete_posts' => 'manage_woocommerce', 'publish_posts' => 'manage_woocommerce', 'read_private_posts' => 'manage_woocommerce', 'create_posts' => 'manage_woocommerce'], 'map_meta_cap' => false]];
        return $post_types;
    }
    /**
     * Check if current page is email editor page.
     *
     * @param bool $is_editor_page Current editor page status.
     * @return bool Whether current page is email editor page.
     */
    public function is_editor_page(bool $is_editor_page): bool
    {
        if ($is_editor_page) {
            return $is_editor_page;
        }
        // We need to check early if we are on the email editor page. The check runs early so we can't use current_screen() here.
        if (is_admin() && isset($_GET['post']) && isset($_GET['action']) && 'edit' === $_GET['action']) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
            $post = get_post((int) $_GET['post']);
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
            return $post && self::EMAIL_POST_TYPE === $post->post_type;
        }
        return false;
    }
    /**
     * Replace the default editor with our custom email editor.
     *
     * @param bool    $replace Whether to replace the editor.
     * @param WP_Post $post    Post object.
     * @return bool Whether the editor was replaced.
     */
    public function replace_editor($replace, $post)
    {
        $current_screen = get_current_screen();
        if (self::EMAIL_POST_TYPE === $post->post_type && $current_screen) {
            $this->editor_page_renderer->render();
            return true;
        }
        return $replace;
    }
    /**
     * Delete the email template associated with the email editor post when the post is permanently deleted.
     *
     * @param int     $post_id The post ID.
     * @param WP_Post $post    The post object.
     */
    public function delete_email_template_associated_with_email_editor_post($post_id, $post): void
    {
        if (self::EMAIL_POST_TYPE !== $post->post_type) {
            return;
        }
        $post_manager = Wc_Transactional_Email_Posts_Manager::get_instance();
        $email_type = $post_manager->get_email_type_from_post_id($post_id, true);
        if (empty($email_type)) {
            return;
        }
        $post_manager->delete_email_template($email_type);
    }
    /**
     * Extend the post API for the wp_template post type to add and save the woocommerce_data field.
     */
    public function extend_template_post_api(): void
    {
        register_rest_field('wp_template', 'woocommerce_data', ['get_callback' => $this->template_api_controller->get_template_data(...), 'update_callback' => $this->template_api_controller->save_template_data(...), 'schema' => $this->template_api_controller->get_template_data_schema()]);
    }
    /**
     * Filter email preview data to replace placeholders with actual content.
     *
     * This method retrieves the appropriate email type based on the request,
     * generates the email content using the WooContentProcessor, and replaces
     * the placeholder in the preview HTML.
     *
     * @param string $data       The preview data.
     * @param string $email_type The email type identifier (e.g., 'customer_processing_order').
     * @param int    $post_id    The post ID.
     * @return string The updated preview data with placeholders replaced.
     */
    private function update_email_preview_data($data, string $email_type, $post_id = 0)
    {
        $type_param = Email_Preview::DEFAULT_EMAIL_TYPE;
        if (!empty($post_id)) {
            $type_param = Wc_Transactional_Email_Posts_Manager::get_instance()->get_email_type_class_name_from_post_id($post_id);
        } elseif (!empty($email_type)) {
            $type_param = Wc_Transactional_Email_Posts_Manager::get_instance()->get_email_type_class_name_from_email_id($email_type);
        }
        $email_preview = wc_get_container()->get(Email_Preview::class);
        try {
            $message = $email_preview->generate_placeholder_content($type_param);
        } catch (\InvalidArgumentException $e) {
            // If the provided type was invalid, fall back to the default.
            try {
                $message = $email_preview->generate_placeholder_content(Email_Preview::DEFAULT_EMAIL_TYPE);
            } catch (\Throwable) {
                return $data;
            }
        } catch (\Throwable) {
            return $data;
        }
        return str_replace(Block_Email_Renderer::WOO_EMAIL_CONTENT_PLACEHOLDER, $message, $data);
    }
    /**
     * Filter email preview data used when sending a preview email.
     *
     * @param string  $data The preview data.
     * @param WP_Post $post The post object.
     * @return string The updated preview data with placeholders replaced.
     */
    public function update_send_preview_email_rendered_data($data, $post)
    {
        $email_type = '';
        $post_body = file_get_contents('php://input');
        if ($post_body) {
            $decoded_body = json_decode($post_body);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded_body->post_id)) {
                // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $post_id = absint($decoded_body->post_id);
                // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $email_type = Wc_Transactional_Email_Posts_Manager::get_instance()->get_email_type_from_post_id($post_id);
                if (!empty($email_type)) {
                    return $this->update_email_preview_data($data, $email_type);
                }
            }
        } elseif (!empty($post) && $post instanceof \WP_Post) {
            $email_type = Wc_Transactional_Email_Posts_Manager::get_instance()->get_email_type_from_post_id($post->ID);
            if (!empty($email_type)) {
                return $this->update_email_preview_data($data, $email_type, $post->ID);
            }
        }
        return $data;
    }
    /**
     * Update the personalizer context for the send preview email.
     *
     * @param array $context The personalizer context.
     * @return array The updated personalizer context.
     */
    public function update_send_preview_email_personalizer_context(array $context)
    {
        $post_manager = Wc_Transactional_Email_Posts_Manager::get_instance();
        $email_id = $post_manager->get_email_type_from_post_id(get_the_ID());
        $email_type = $email_id ? $post_manager->get_email_type_class_name_from_email_id($email_id) : Email_Preview::DEFAULT_EMAIL_TYPE;
        $email_preview = wc_get_container()->get(Email_Preview::class);
        try {
            $email_preview->set_email_type($email_type);
        } catch (\InvalidArgumentException) {
            // If the email type is invalid, return the context data as is.
            return $context;
        }
        $email = $email_preview->get_email();
        $email->recipient = $context['recipient_email'] ?? '';
        $personalizer = wc_get_container()->get(Transactional_Email_Personalizer::class);
        return $personalizer->prepare_context_data($context, $email);
    }
    /**
     * Filter email preview data used when previewing the email in new tab.
     *
     * @param string $data The preview HTML string.
     * @return string The updated preview HTML with placeholders replaced.
     */
    public function update_preview_post_template_html_data($data)
    {
        // return early if the data does not contain the placeholder meaning it's already been processed.
        if (!str_contains((string) $data, Block_Email_Renderer::WOO_EMAIL_CONTENT_PLACEHOLDER)) {
            return $data;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        // Nonce verification is disabled here because the preview action doesn't modify data,
        // and the check caused issues with the 'Preview in new tab' feature due to context changes.
        $type_param = isset($_GET['woo_email']) ? sanitize_text_field(wp_unslash($_GET['woo_email'])) : '';
        // check for post id (preview id) in the request.
        $post_id = isset($_REQUEST['preview_id']) ? sanitize_text_field(wp_unslash($_REQUEST['preview_id'])) : '';
        // phpcs:enable
        return $this->update_email_preview_data($data, $type_param, $post_id);
    }
    /**
     * Extend the post API for the woo_email post type to add and save the woocommerce_data field.
     */
    public function extend_post_api(): void
    {
        register_rest_field(self::EMAIL_POST_TYPE, 'woocommerce_data', ['get_callback' => $this->email_api_controller->get_email_data(...), 'update_callback' => $this->email_api_controller->save_email_data(...), 'schema' => $this->email_api_controller->get_email_data_schema()]);
    }
    /**
     * Action hook callback before sending the preview email via wp_mail
     *
     * @since 10.6.0
     */
    public function send_preview_email_before_wp_mail(): void
    {
        add_filter('wp_mail_from', $this->wc_email_instance->get_from_address(...));
        add_filter('wp_mail_from_name', $this->wc_email_instance->get_from_name(...));
    }
    /**
     * Action hook callback after sending the preview email via wp_mail.
     *
     * @since 10.6.0
     */
    public function send_preview_email_after_wp_mail(): void
    {
        remove_filter('wp_mail_from', $this->wc_email_instance->get_from_address(...));
        remove_filter('wp_mail_from_name', $this->wc_email_instance->get_from_name(...));
    }
    /**
     * Update the email subject for the send preview email.
     *
     * @param string  $subject The email subject.
     * @param WP_Post $post    The post object.
     * @return string The updated email subject.
     */
    public function update_email_subject_for_send_preview_email($subject, $post)
    {
        if (!$post instanceof \WP_Post || self::EMAIL_POST_TYPE !== $post->post_type) {
            return $subject;
        }
        $post_manager = Wc_Transactional_Email_Posts_Manager::get_instance();
        $email_type_class_name = $post_manager->get_email_type_class_name_from_post_id($post->ID);
        if (empty($email_type_class_name)) {
            return $subject;
        }
        /**
         * Validate the email type class name.
         *
         * @var EmailPreview $email_preview
         */
        $email_preview = wc_get_container()->get(Email_Preview::class);
        try {
            $email_preview->set_email_type($email_type_class_name);
            return $email_preview->get_subject();
        } catch (\InvalidArgumentException|\Throwable) {
            return $subject;
        }
    }
}