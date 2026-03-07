<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Emails;

use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use WP_Error;
use WP_REST_Request;

/**
 * Controller for the REST endpoint for the new email listing page.
 */
class EmailListingRestController extends RestApiControllerBase
{
    /**
     * Email listing nonce.
     *
     * @var string
     */
    public const NONCE_KEY = 'email-listing-nonce';

    /**
     * The root namespace for the JSON REST API endpoints.
     */
    protected string $route_namespace = 'wc-admin-email';

    /**
     * Route base.
     */
    protected string $rest_base = 'settings/email/listing';

    /**
     * Email template generator instance.
     */
    private readonly \Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsGenerator $email_template_generator;

    /**
     * Get the WooCommerce REST API namespace for the class.
     */
    protected function get_rest_api_namespace(): string
    {
        return 'wc-admin-email-listing';
    }

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->email_template_generator = new WCTransactionalEmailPostsGenerator();
    }

    /**
     * Perform the initialization.
     */
    public function initialize_template_generator(): void
    {
        $this->email_template_generator->init_default_transactional_emails();
    }

    /**
     * Register the REST API endpoints handled by this controller.
     */
    public function register_routes(): void
    {
        $this->initialize_template_generator();

        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/recreate-email-post',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => $this->recreate_email_post(...),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => $this->get_args_for_recreate_email_post(),
                    'schema'              => $this->get_schema_with_message(),
                ],
            ]
        );
    }

    /**
     * Get the accepted arguments for the POST recreate-email-post request.
     *
     * @return array[]
     */
    private function get_args_for_recreate_email_post(): array
    {
        return [
            'email_id' => [
                'description'       => __('The email ID to recreate the post for.', 'woocommerce'),
                'type'              => 'string',
                'required'          => true,
                'validate_callback' => $this->validate_email_id(...),
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Get the schema for the POST recreate-email-post and save-transient requests.
     *
     * @return array[]
     */
    private function get_schema_with_message(): array
    {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'email-listing-with-message',
            'type'       => 'object',
            'properties' => [
                'message' => [
                    'description' => __('A message indicating that the action completed successfully.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
                'post_id' => [
                    'description' => __('The post ID of the generated email post.', 'woocommerce'),
                    'type'        => 'string',
                    'context'     => [ 'view', 'edit' ],
                    'readonly'    => true,
                ],
            ],
        ];
    }

    /**
     * Validate the email ID.
     *
     * @param string $email_id The email ID to validate.
     * @return bool|WP_Error True if the email ID is valid, otherwise a WP_Error object.
     */
    private function validate_email_id(string $email_id): \WP_Error|true
    {
        if (! in_array($email_id, WCTransactionalEmails::get_transactional_emails(), true)) {
            return new \WP_Error(
                'woocommerce_rest_not_allowed_email_id',
                sprintf('The provided email ID "%s" is not allowed.', $email_id),
                [ 'status' => 400 ],
            );
        }
        return true;
    }

    /**
     * Permission check for REST API endpoint.
     *
     * @param WP_REST_Request $request The request for which the permission is checked.
     * @return bool|WP_Error True if the current user has the capability, otherwise a WP_Error object.
     */
    private function check_permissions(WP_REST_Request $request)
    {
        $nonce = $request->get_param('nonce');
        if (! wp_verify_nonce($nonce, self::NONCE_KEY)) {
            return new WP_Error(
                'invalid_nonce',
                __('Invalid nonce.', 'woocommerce'),
                [ 'status' => 403 ],
            );
        }
        return $this->check_permission($request, 'manage_woocommerce');
    }

    /**
     * Handle the POST /settings/email/listing/recreate-email-post.
     *
     * @param WP_REST_Request $request The received request.
     * @return array|WP_Error Request response or an error.
     */
    public function recreate_email_post(WP_REST_Request $request): \WP_Error|array
    {
        $email_id = $request->get_param('email_id');

        $generated_post_id = '';

        try {
            $generated_post_id = $this->email_template_generator->generate_email_template_if_not_exists($email_id);
        } catch (\Exception $e) {
            return new WP_Error(
                'woocommerce_rest_email_post_generation_failed',
                // translators: %s: Error message.
                sprintf(__('Error generating email post. Error: %s.', 'woocommerce'), $e->getMessage()),
                [ 'status' => 500 ]
            );
        }

        if ($generated_post_id) {
            return [
                // translators: %s: WooCommerce transactional email ID.
                'message' => sprintf(__('Email post generated for %s.', 'woocommerce'), $email_id),
                'post_id' => (string) $generated_post_id,
            ];
        }
        return new WP_Error(
            'woocommerce_rest_email_post_generation_error',
            __('Error unable to generate email post.', 'woocommerce'),
            [ 'status' => 500 ]
        );
    }
}
