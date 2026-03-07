<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Exceptions\ApiException;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;
use Exception;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Controller for the WooPayments-specific REST endpoints to service the Payments settings page.
 *
 * @internal
 */
class WooPaymentsRestController extends RestApiControllerBase
{
    /**
     * The root namespace for the JSON REST API endpoints.
     */
    protected string $route_namespace = 'wc-admin';

    /**
     * Route base.
     */
    protected string $rest_base = 'settings/payments/woopayments';

    /**
     * The payments settings page service.
     */
    private Payments $payments;

    /**
     * The WooPayments-specific Payments settings page service.
     */
    private WooPaymentsService $woopayments;

    /**
     * Get the WooCommerce REST API namespace for the class.
     */
    protected function get_rest_api_namespace(): string
    {
        return 'wc-admin-settings-payments-woopayments';
    }

    /**
     * Register the REST API endpoints handled by this controller.
     *
     * @param bool $override Whether to override the existing routes. Useful for testing.
     */
    public function register_routes(bool $override = false): void
    {
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'get_onboarding_details'),
                    'validation_callback' => 'rest_validate_request_arg',
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
                'schema' => $this->get_schema_for_get_onboarding_details(...),
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/start',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_step_start'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/save',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_step_save'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/check',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_step_check'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/finish',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_step_finish'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/(?P<step>[a-zA-Z0-9_-]+)/clean',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_step_clean'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                    ],
                ],
            ],
            $override
        );
        // Onboarding step specific routes.
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/init',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_test_account_init'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT . '/reset',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_test_account_reset'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_business_verification_kyc_session_init'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/kyc_session/finish',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_business_verification_kyc_session_finish'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        // This is a route to disable test accounts for the native onboarding UX.
        // The handler is the same as the one for the non-native onboarding UX.
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/step/' . WooPaymentsService::ONBOARDING_STEP_BUSINESS_VERIFICATION . '/test_account/disable',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_test_account_disable'),
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'from'     => [
                            'description'       => esc_html__('Where from in the onboarding flow this request was triggered.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/preload',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_onboarding_preload'),
                    'validation_callback' => 'rest_validate_request_arg',
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/reset',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'reset_onboarding'),
                    'validation_callback' => 'rest_validate_request_arg',
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'from'     => [
                            'description'       => esc_html__('Where from in the onboarding flow this request was triggered.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/woopay-eligibility',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'get_woopay_eligibility'),
                    'permission_callback' => $this->check_permissions(...),
                ],
            ],
            $override
        );
        // This is the route to disable test accounts when not in a native in-context UX.
        register_rest_route(
            $this->route_namespace,
            '/' . $this->rest_base . '/onboarding/test_account/disable',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => fn (\WP_REST_Request $request) => $this->run($request, 'handle_test_account_disable'),
                    'validation_callback' => 'rest_validate_request_arg',
                    'permission_callback' => $this->check_permissions(...),
                    'args'                => [
                        'location' => [
                            'description'       => esc_html__('ISO3166 alpha-2 country code. Defaults to the stored providers business location country code.', 'woocommerce'),
                            'type'              => 'string',
                            'pattern'           => '[a-zA-Z]{2}', // Two alpha characters.
                            'required'          => false,
                            'validate_callback' => fn ($value, \WP_REST_Request $request) => $this->check_location_arg($value, $request),
                        ],
                        'from'     => [
                            'description'       => esc_html__('Where from in the onboarding flow this request was triggered.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                        'source'   => [
                            'description'       => esc_html__('The upmost entry point from where the merchant entered the onboarding flow.', 'woocommerce'),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ],
                    ],
                ],
            ],
            $override
        );
    }

    /**
     * Get the controller's REST URL path.
     *
     * @param string $relative_path Optional. Relative path to append to the REST URL.
     *
     * @return string The REST URL path.
     */
    public function get_rest_url_path(string $relative_path = ''): string
    {
        $path = '/' . trim($this->route_namespace, '/') . '/' . trim($this->rest_base, '/');
        if (! empty($relative_path)) {
            $path .= '/' . ltrim($relative_path, '/');
        }

        return $path;
    }

    /**
     * Initialize the class instance.
     *
     * @param Payments           $payments    The general payments settings page service.
     * @param WooPaymentsService $woopayments The WooPayments-specific Payments settings page service.
     *
     * @internal
     */
    final public function init(Payments $payments, WooPaymentsService $woopayments): void
    {
        $this->payments    = $payments;
        $this->woopayments = $woopayments;
    }

    /**
     * Get the onboarding details for the given location.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function get_onboarding_details(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        try {
            $onboarding_details = $this->woopayments->get_onboarding_details($location, $this->get_rest_url_path('onboarding'), $source);
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        } catch (Exception $e) {
            return new WP_Error('woocommerce_rest_woopayments_onboarding_error', $e->getMessage(), [ 'status' => WP_Http::INTERNAL_SERVER_ERROR ]);
        }

        return rest_ensure_response($this->prepare_onboarding_details_response($onboarding_details));
    }

    /**
     * Handle the onboarding step start action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_step_start(WP_REST_Request $request)
    {
        $step_id = $request->get_param('step') ?? '';

        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        try {
            $previous_status = $this->woopayments->get_onboarding_step_status($step_id, $location);

            $this->woopayments->mark_onboarding_step_started($step_id, $location, false, $source);

            $response = [
                'success'         => true,
                'previous_status' => $previous_status,
                'current_status'  => $this->woopayments->get_onboarding_step_status($step_id, $location),
            ];
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Handle the onboarding step save action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response.
     */
    protected function handle_onboarding_step_save(WP_REST_Request $request)
    {
        $step_id = $request->get_param('step') ?? '';

        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        try {
            $this->woopayments->onboarding_step_save($step_id, $location, $request->get_params());

            // If some step data was saved, we also ensure that the step is marked as started, if not already.
            // This way we maintain onboarding state consistency if the frontend does not call the start endpoint.
            $this->woopayments->mark_onboarding_step_started($step_id, $location, false, $source);
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response([ 'success' => true ]);
    }

    /**
     * Handle the onboarding step check action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_step_check(WP_REST_Request $request)
    {
        $step_id = $request->get_param('step') ?? '';

        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        try {
            $result = $this->woopayments->onboarding_step_check($step_id, $location);
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        // Merge the result with the success flag.
        $response = array_merge([ 'success' => true ], $result);

        return rest_ensure_response($response);
    }

    /**
     * Handle the onboarding step finish action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_step_finish(WP_REST_Request $request)
    {
        $step_id = $request->get_param('step') ?? '';

        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        try {
            $previous_status = $this->woopayments->get_onboarding_step_status($step_id, $location);

            $this->woopayments->mark_onboarding_step_completed($step_id, $location, false, $source);

            $response = [
                'success'         => true,
                'previous_status' => $previous_status,
                'current_status'  => $this->woopayments->get_onboarding_step_status($step_id, $location),
            ];
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Handle the onboarding step clean action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_step_clean(WP_REST_Request $request)
    {
        $step_id = $request->get_param('step') ?? '';

        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        try {
            $previous_status = $this->woopayments->get_onboarding_step_status($step_id, $location);

            $this->woopayments->clean_onboarding_step_progress($step_id, $location);

            $response = [
                'success'         => true,
                'previous_status' => $previous_status,
                'current_status'  => $this->woopayments->get_onboarding_step_status($step_id, $location),
            ];
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response($response);
    }

    /**
     * Handle the onboarding test account initialize action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_test_account_init(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        try {
            // Mark the step as started, if not already.
            $this->woopayments->mark_onboarding_step_started(WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT, $location, false, $source);

            $result = $this->woopayments->onboarding_test_account_init($location, $source);
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response(
            array_merge(
                [ 'success' => true ],
                $result
            )
        );
    }

    /**
     * Handle the onboarding test account reset action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_test_account_reset(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        // For now, just "forward" the request to the generic onboarding reset endpoint.
        $request->set_param('location', $location);
        $request->set_param('from', WooPaymentsService::ONBOARDING_STEP_TEST_ACCOUNT);
        $request->set_param('source', $source);
        return $this->reset_onboarding($request);
    }

    /**
     * Handle the onboarding business verification step KYC session initialization action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_business_verification_kyc_session_init(WP_REST_Request $request)
    {
        // If we receive self-assessment data with the request, we will use it.
        $self_assessment = ! empty($request->get_param('self_assessment')) ? wc_clean(wp_unslash($request->get_param('self_assessment'))) : [];

        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        try {
            $account_session = $this->woopayments->get_onboarding_kyc_session($location, $self_assessment, $source);
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response(
            [
                'success' => true,
                'session' => $account_session,
            ]
        );
    }

    /**
     * Handle the onboarding business verification step KYC session finish action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_business_verification_kyc_session_finish(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        $source = $request->get_param('source');

        try {
            $response = $this->woopayments->finish_onboarding_kyc_session($location, $source);
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        // If there is no success key in the response, we assume the operation was successful.
        if (! isset($response['success'])) {
            $response['success'] = true;
        }

        return rest_ensure_response($response);
    }

    /**
     * Handle the onboarding preload action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_onboarding_preload(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        try {
            $response = $this->woopayments->onboarding_preload();
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        // If there is no success key in the response, we assume the operation was successful.
        if (! isset($response['success'])) {
            $response['success'] = true;
        }

        return rest_ensure_response($response);
    }

    /**
     * Handle the onboarding reset action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function reset_onboarding(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        try {
            $this->woopayments->reset_onboarding($location, $request->get_param('from') ?? '', $request->get_param('source') ?? '');
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response(
            [
                'success' => true,
            ]
        );
    }

    /**
     * Handle the onboarding test mode disable action.
     *
     * @param WP_REST_Request $request The request object.
     *
     * @return WP_Error|WP_REST_Response The response or error.
     */
    protected function handle_test_account_disable(WP_REST_Request $request)
    {
        $location = $request->get_param('location');
        if (empty($location)) {
            // Fall back to the providers country if no location is provided.
            $location = $this->payments->get_country();
        }

        try {
            $this->woopayments->disable_test_account(
                $location,
                $request->get_param('from') ?? '',
                $request->get_param('source') ?? ''
            );
        } catch (ApiException $e) {
            return new WP_Error($e->getErrorCode(), $e->getMessage(), [ 'status' => $e->getCode() ]);
        }

        return rest_ensure_response(
            [
                'success' => true,
            ]
        );
    }

    /**
     * Get WooPay eligibility status.
     *
     * @return WP_REST_Response The response.
     */
    protected function get_woopay_eligibility()
    {
        // We use the Payments Settings stored business location to determine the eligibility.
        $location = $this->payments->get_country();

        $woopay_eligible_countries = [ 'US' ];
        $is_eligible               = in_array($location, $woopay_eligible_countries, true);

        return rest_ensure_response(
            [
                'is_eligible' => $is_eligible,
            ]
        );
    }

    /**
     * General permissions check for WooPayments settings REST API endpoint.
     *
     * @param WP_REST_Request $request The request for which the permission is checked.
     *
     * @return bool|WP_Error True if the current user has the capability, otherwise an "Unauthorized" error or False if no error is available for the request method.
     */
    private function check_permissions(WP_REST_Request $request): bool|\WP_Error
    {
        $context = 'read';
        if ('POST' === $request->get_method()) {
            $context = 'edit';
        } elseif ('DELETE' === $request->get_method()) {
            $context = 'delete';
        }

        if (wc_rest_check_manager_permissions('payment_gateways', $context)) {
            return true;
        }

        $error_information = $this->get_authentication_error_by_method($request->get_method());
        if (is_null($error_information)) {
            return false;
        }

        return new WP_Error(
            $error_information['code'],
            $error_information['message'],
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    /**
     * Validate the location argument.
     *
     * @param mixed           $value   Value of the argument.
     * @param WP_REST_Request $request The current request object.
     *
     * @return WP_Error|true True if the location argument is valid, otherwise a WP_Error object.
     */
    private function check_location_arg($value, WP_REST_Request $request): \WP_Error|true
    {
        // If the 'location' argument is not a string return an error.
        if (! is_string($value)) {
            return new WP_Error('rest_invalid_param', esc_html__('The location argument must be a string.', 'woocommerce'), [ 'status' => 400 ]);
        }

        // Get the registered attributes for this endpoint request.
        $attributes = $request->get_attributes();

        // Grab the location param schema.
        $args = $attributes['args']['location'];

        // If the location param doesn't match the regex pattern then we should return an error as well.
        if (! preg_match('/^' . $args['pattern'] . '$/', $value)) {
            return new WP_Error('rest_invalid_param', esc_html__('The location argument must be a valid ISO3166 alpha-2 country code.', 'woocommerce'), [ 'status' => 400 ]);
        }

        return true;
    }

    /**
     * Prepare the response for the GET onboarding details request.
     *
     * @param array $response The response to prepare.
     *
     * @return array The prepared response.
     */
    private function prepare_onboarding_details_response(array $response): array
    {
        return $this->prepare_onboarding_details_response_recursive($response, $this->get_schema_for_get_onboarding_details());
    }

    /**
     * Recursively prepare the response items for the GET onboarding details request.
     *
     * @param mixed $response_item The response item to prepare.
     * @param array $schema        The schema to use for preparing the response.
     *
     * @return mixed The prepared response item.
     */
    private function prepare_onboarding_details_response_recursive($response_item, array $schema)
    {
        if (is_null($response_item)) {
            return null;
        }

        if (! array_key_exists('properties', $schema) ||
            ! is_array($schema['properties'])) {

            // Filter out null values for loosely defined schema types.
            if (is_array($response_item)) {
                return ArrayUtil::filter_null_values_recursive($response_item);
            }
            return $response_item;
        }

        $prepared_response = [];
        foreach ($schema['properties'] as $key => $property_schema) {
            if (is_array($response_item) && array_key_exists($key, $response_item)) {
                if (is_array($property_schema) && array_key_exists('properties', $property_schema)) {
                    $prepared_response[ $key ] = $this->prepare_onboarding_details_response_recursive($response_item[ $key ], $property_schema);
                } elseif (is_array($property_schema) && array_key_exists('items', $property_schema)) {
                    $prepared_response[ $key ] = array_map(
                        fn ($item): mixed => $this->prepare_onboarding_details_response_recursive($item, $property_schema['items']),
                        $response_item[ $key ]
                    );
                } else {
                    $prepared_response[ $key ] = $response_item[ $key ];
                }
            }
        }

        // Ensure the order is the same as in the schema.
        $prepared_response = array_merge(array_fill_keys(array_keys($schema['properties']), null), $prepared_response);

        // Remove any null values from the response.
        return ArrayUtil::filter_null_values_recursive($prepared_response);
    }

    /**
     * Get the schema for the GET onboarding details request.
     *
     * @return array[]
     */
    private function get_schema_for_get_onboarding_details(): array
    {
        $schema               = [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title'   => 'WooCommerce Settings Payments WooPayments onboarding details for the given location.',
            'type'    => 'object',
        ];
        $schema['properties'] = [
            'state'    => [
                'type'        => 'object',
                'description' => esc_html__('The general state of the onboarding process.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'properties'  => [
                    'supported' => [
                        'type'        => 'boolean',
                        'description' => esc_html__('Whether onboarding is supported.', 'woocommerce'),
                        'context'     => [ 'view', 'edit' ],
                        'readonly'    => true,
                    ],
                    'started'   => [
                        'type'        => 'boolean',
                        'description' => esc_html__('Whether the onboarding process is started.', 'woocommerce'),
                        'context'     => [ 'view', 'edit' ],
                        'readonly'    => true,
                    ],
                    'completed' => [
                        'type'        => 'boolean',
                        'description' => esc_html__('Whether the onboarding process is completed.', 'woocommerce'),
                        'context'     => [ 'view', 'edit' ],
                        'readonly'    => true,
                    ],
                    'test_mode' => [
                        'type'        => 'boolean',
                        'description' => esc_html__('Whether the onboarding process is in test mode.', 'woocommerce'),
                        'context'     => [ 'view', 'edit' ],
                        'readonly'    => true,
                    ],
                    'dev_mode'  => [
                        'type'        => 'boolean',
                        'description' => esc_html__('Whether WooPayments is in dev mode.', 'woocommerce'),
                        'context'     => [ 'view', 'edit' ],
                        'readonly'    => true,
                    ],
                ],
            ],
            'messages' => [
                'type'                 => 'object',
                'description'          => esc_html__('Various messages to possibly show the user.', 'woocommerce'),
                'context'              => [ 'view', 'edit' ],
                'readonly'             => true,
                'additionalProperties' => [
                    'type'        => 'string',
                    'description' => esc_html__('Message to show the user.', 'woocommerce'),
                    'readonly'    => true,
                ],
            ],
            'steps'    => [
                'type'        => 'array',
                'description' => esc_html__('The onboarding steps.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
                'items'       => [
                    'type'       => 'object',
                    'properties' => [
                        'id'             => [
                            'type'        => 'string',
                            'description' => esc_html__('The unique identifier for the step.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'path'           => [
                            'type'        => 'string',
                            'description' => esc_html__('The relative path of the step to use for frontend navigation.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                        'required_steps' => [
                            'type'        => 'array',
                            'description' => esc_html__('The steps that are required to be completed before this step.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'items'       => [
                                'type' => 'string',
                            ],
                        ],
                        'status'         => [
                            'type'        => 'string',
                            'description' => esc_html__('The current status of the step.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'enum'        => [
                                WooPaymentsService::ONBOARDING_STEP_STATUS_NOT_STARTED,
                                WooPaymentsService::ONBOARDING_STEP_STATUS_STARTED,
                                WooPaymentsService::ONBOARDING_STEP_STATUS_COMPLETED,
                            ],
                        ],
                        'errors'         => [
                            'type'        => 'array',
                            'description' => esc_html__('Errors list for the step.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'items'       => [
                                'type'       => 'object',
                                'properties' => [
                                    'code'    => [
                                        'type'     => 'string',
                                        'readonly' => true,
                                    ],
                                    'message' => [
                                        'type'     => 'string',
                                        'readonly' => true,
                                    ],
                                    'context' => [
                                        'type'     => 'object',
                                        'readonly' => true,
                                    ],
                                ],
                            ],
                        ],
                        'actions'        => [
                            'type'        => 'object',
                            'description' => esc_html__('The available actions for the step.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                            'properties'  => [
                                'start'                => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to signal the step start.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'save'                 => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to save step information in the database.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'check'                => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to check the step status.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'finish'               => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to signal the step completion.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'clean'                => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to clean the step progress.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'auth'                 => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to authorize the WPCOM connection.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'init'                 => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to initialize a test account.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'kyc_session'          => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to create or resume an embedded KYC session.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'kyc_session_finish'   => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to finish an embedded KYC session.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'kyc_fallback'         => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to use as a fallback when dealing with errors with the embedded KYC.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'reset'                => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to reset the onboarding process, either partially, for a certain step, or fully.', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                                'test_account_disable' => [
                                    'type'        => 'object',
                                    'description' => esc_html__('Action to disable the test account currently in use', 'woocommerce'),
                                    'properties'  => $this->get_schema_properties_for_onboarding_step_action(),
                                    'context'     => [ 'view', 'edit' ],
                                    'readonly'    => true,
                                ],
                            ],
                        ],
                        'context'        => [
                            'type'        => 'object',
                            'description' => esc_html__('Various contextual data for the step to use.', 'woocommerce'),
                            'context'     => [ 'view', 'edit' ],
                            'readonly'    => true,
                        ],
                    ],
                ],
            ],
            'context'  => [
                'type'        => 'object',
                'description' => esc_html__('Various contextual data for the onboarding process to use.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
        ];

        return $schema;
    }

    /**
     * Get the schema properties for an onboarding step action.
     *
     * @return array[] The schema properties for an onboarding step action.
     */
    private function get_schema_properties_for_onboarding_step_action(): array
    {
        return [
            'type' => [
                'type'        => 'string',
                'description' => esc_html__('The action type to determine how to use the URL.', 'woocommerce'),
                'enum'        => [ WooPaymentsService::ACTION_TYPE_REST, WooPaymentsService::ACTION_TYPE_REDIRECT ],
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
            'href' => [
                'type'        => 'string',
                'description' => esc_html__('The URL to use for the action.', 'woocommerce'),
                'context'     => [ 'view', 'edit' ],
                'readonly'    => true,
            ],
        ];
    }
}
