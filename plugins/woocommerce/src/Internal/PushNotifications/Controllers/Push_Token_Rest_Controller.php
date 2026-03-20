<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\PushNotifications\Controllers;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\PushTokensDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushToken;
use Automattic\WooCommerce\Internal\PushNotifications\Exceptions\PushTokenNotFoundException;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Validators\PushTokenValidator;
use Automattic\WooCommerce\Internal\RestApiControllerBase;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WC_Data_Exception;
use WP_Error;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Controller for the REST endpoints associated with push notification device
 * tokens.
 *
 * @since 10.6.0
 */
class PushTokenRestController extends RestApiControllerBase
{
    /**
     * The root namespace for the JSON REST API endpoints.
     */
    protected string $route_namespace = 'wc-push-notifications';

    /**
     * The REST base for the endpoints URL.
     */
    protected string $rest_base = 'push-tokens';

    /**
     * Get the WooCommerce REST API namespace for the class.
     *
     * @since 10.6.0
     */
    protected function get_rest_api_namespace(): string
    {
        return $this->route_namespace;
    }

    /**
     * Register the REST API endpoints handled by this controller.
     *
     * @since 10.6.0
     */
    public function register_routes(): void
    {
        register_rest_route(
            $this->get_rest_api_namespace(),
            $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => fn (WP_REST_Request $request) => $this->run($request, 'create'),
                    'args'                => $this->get_args('create'),
                    'permission_callback' => $this->authorize(...),
                    'schema'              => $this->get_schema(...),
                ],
            ]
        );

        register_rest_route(
            $this->get_rest_api_namespace(),
            $this->rest_base . '/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => fn (WP_REST_Request $request) => $this->run($request, 'delete'),
                    'args'                => $this->get_args('delete'),
                    'permission_callback' => $this->authorize(...),
                    'schema'              => $this->get_schema(...),
                ],
            ]
        );
    }

    /**
     * Creates a push token record.
     *
     * @since 10.6.0
     *
     * @param WP_REST_Request $request The request object.
     * @phpstan-param WP_REST_Request<array<string, mixed>> $request
     */
    public function create(WP_REST_Request $request): \WP_Error|\WP_REST_Response
    {
        try {
            $data = [
                'user_id'       => get_current_user_id(),
                'token'         => $request->get_param('token'),
                'platform'      => $request->get_param('platform'),
                'device_uuid'   => $request->get_param('device_uuid'),
                'origin'        => $request->get_param('origin'),
                'device_locale' => $request->get_param('device_locale'),
                'metadata'      => $request->get_param('metadata') ?? [],
            ];

            $data_store = wc_get_container()->get(PushTokensDataStore::class);
            $push_token = $data_store->get_by_token_or_device_id($data);

            if ($push_token) {
                $push_token->set_token($data['token']);
                $push_token->set_device_uuid($data['device_uuid']);
                $push_token->set_device_locale($data['device_locale']);
                $push_token->set_metadata($data['metadata']);
                $data_store->update($push_token);
            } else {
                $push_token = $data_store->create($data);
            }
        } catch (Exception $e) {
            return $this->convert_exception_to_wp_error($e);
        }

        return new WP_REST_Response(
            [ 'id' => $push_token->get_id() ],
            WP_Http::CREATED
        );
    }

    /**
     * Deletes a push token record.
     *
     * @since 10.6.0
     *
     * @param WP_REST_Request $request The request object.
     * @phpstan-param WP_REST_Request<array<string, mixed>> $request
     * @throws PushTokenNotFoundException If token does not belong to authenticated user.
     * @throws WC_Data_Exception If token wasn't deleted.
     */
    public function delete(WP_REST_Request $request): \WP_Error|\WP_REST_Response
    {
        try {
            $id         = (int) $request->get_param('id');
            $data_store = wc_get_container()->get(PushTokensDataStore::class);
            $push_token = $data_store->read($id);

            if ($push_token->get_user_id() !== get_current_user_id()) {
                throw new PushTokenNotFoundException();
            }

            $deleted = $data_store->delete($id);

            if (! $deleted) {
                throw new WC_Data_Exception(
                    'woocommerce_push_token_not_deleted',
                    'The push token could not be deleted.',
                    WP_Http::INTERNAL_SERVER_ERROR
                );
            }
        } catch (Exception $e) {
            return $this->convert_exception_to_wp_error($e);
        }

        return new WP_REST_Response(null, WP_Http::NO_CONTENT);
    }

    /**
     * Validates the arguments from the request via PushTokenValidator.
     *
     * @since 10.6.0
     *
     * @param mixed           $value The value being validated.
     * @param WP_REST_Request $request The request object.
     * @phpstan-param WP_REST_Request<array<string, mixed>> $request
     * @param string          $param The name of the parameter being validated.
     * @return bool|WP_Error
     */
    public function validate_argument($value, WP_REST_Request $request, string $param)
    {
        return PushTokenValidator::validate($request->get_params(), [ $param ]);
    }

    /**
     * Get the schema for the POST endpoint.
     *
     * @since 10.6.0
     *
     * @return array[]
     */
    public function get_schema(): array
    {
        return array_merge(
            $this->get_base_schema(),
            [
                'title'      => PushToken::POST_TYPE,
                'properties' => array_map(
                    fn ($item): array => array_intersect_key(
                        $item,
                        [
                            'description' => null,
                            'type'        => null,
                            'enum'        => null,
                            'minimum'     => null,
                            'default'     => null,
                            'required'    => null,
                        ]
                    ),
                    $this->get_args()
                ),
            ]
        );
    }

    /**
     * Checks user is authorized to access this endpoint.
     *
     * @since 10.6.0
     *
     * @param WP_REST_Request $request The request object.
     * @phpstan-param WP_REST_Request<array<string, mixed>> $request
     */
    public function authorize(WP_REST_Request $request): \WP_Error|bool
    {
        if (! get_current_user_id()) {
            return new WP_Error(
                'woocommerce_rest_cannot_view',
                __('Sorry, you are not allowed to do that.', 'woocommerce'),
                [ 'status' => rest_authorization_required_code() ]
            );
        }

        if (! wc_get_container()->get(PushNotifications::class)->should_be_enabled()) {
            return false;
        }

        $has_valid_role = array_reduce(
            PushNotifications::ROLES_WITH_PUSH_NOTIFICATIONS_ENABLED,
            fn ($carry, $role) => $this->check_permission($request, $role) === true ? true : $carry,
            false
        );

        if (! $has_valid_role) {
            return false;
        }

        return true;
    }

    /**
     * Converts an exception to an instance of WP_Error.
     *
     * @since 10.6.0
     *
     * @param Exception $e The exception to convert.
     */
    private function convert_exception_to_wp_error(Exception $e): WP_Error
    {
        /**
         * If the exception is `WC_Data_Exception`, and doesn't represent an
         * internal server error (which may contain internal details that should
         * be obscured) then format it as a `WP_Error`.
         */
        if (
            $e instanceof WC_Data_Exception
            && $e->getCode() !== WP_Http::INTERNAL_SERVER_ERROR
        ) {
            return new WP_Error(
                $e->getErrorCode(),
                $e->getMessage(),
                $e->getErrorData()
            );
        }

        wc_get_container()
            ->get(LegacyProxy::class)
            ->call_function('wc_get_logger')
            ->error($e->getMessage(), [ 'source' => PushNotifications::FEATURE_NAME ]);

        return new WP_Error(
            'woocommerce_internal_error',
            'Internal server error',
            [ 'status' => WP_Http::INTERNAL_SERVER_ERROR ]
        );
    }

    /**
     * Get the accepted arguments for the POST request.
     *
     * @since 10.6.0
     *
     * @param string $context The context to return args for.
     */
    private function get_args(?string $context = null): array
    {
        $args = [
            'id'            => [
                'description'       => __('Push Token ID', 'woocommerce'),
                'type'              => 'integer',
                'required'          => true,
                'context'           => [ 'delete' ],
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => $this->validate_argument(...),
            ],
            'origin'        => [
                'description'       => __('Origin', 'woocommerce'),
                'type'              => 'string',
                'required'          => true,
                'context'           => [ 'create' ],
                'enum'              => PushToken::ORIGINS,
                'validate_callback' => $this->validate_argument(...),
            ],
            'device_uuid'   => [
                'description'       => __('Device UUID', 'woocommerce'),
                'default'           => '',
                'type'              => 'string',
                'context'           => [ 'create' ],
                'validate_callback' => $this->validate_argument(...),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'device_locale' => [
                'description'       => __('Device Locale', 'woocommerce'),
                'type'              => 'string',
                'required'          => true,
                'context'           => [ 'create' ],
                'validate_callback' => $this->validate_argument(...),
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'platform'      => [
                'description'       => __('Platform', 'woocommerce'),
                'type'              => 'string',
                'required'          => true,
                'context'           => [ 'create' ],
                'enum'              => PushToken::PLATFORMS,
                'validate_callback' => $this->validate_argument(...),
            ],
            'token'         => [
                'description'       => __('Push Token', 'woocommerce'),
                'type'              => 'string',
                'required'          => true,
                'context'           => [ 'create' ],
                'validate_callback' => $this->validate_argument(...),
                'sanitize_callback' => 'wp_unslash',
            ],
            'metadata'      => [
                'description'       => __('Metadata', 'woocommerce'),
                'type'              => 'object',
                'context'           => [ 'create' ],
                'validate_callback' => $this->validate_argument(...),
                'sanitize_callback' => 'wp_unslash',
            ],
        ];

        if ($context) {
            return array_filter(
                $args,
                fn (array $arg): bool => in_array($context, $arg['context'], true)
            );
        }

        return $args;
    }
}
