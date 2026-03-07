<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\RouteInterface;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Batch Route class.
 */
class Batch extends AbstractRoute implements RouteInterface
{
    /**
     * The route identifier.
     *
     * @var string
     */
    public const IDENTIFIER = 'batch';

    /**
     * The schema item identifier.
     *
     * @var string
     */
    public const SCHEMA_TYPE = 'batch';

    /**
     * Get the path of this REST route.
     *
     * @return string
     */
    public function get_path()
    {
        return self::get_path_regex();
    }

    /**
     * Get the path of this rest route.
     */
    public static function get_path_regex(): string
    {
        return '/batch';
    }

    /**
     * Get arguments for this REST route.
     *
     * @return array An array of endpoints.
     */
    public function get_args(): array
    {
        return [
            'callback'            => $this->get_response(...),
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'args'                => [
                'validation' => [
                    'type'    => 'string',
                    'enum'    => [ 'require-all-validate', 'normal' ],
                    'default' => 'normal',
                ],
                'requests'   => [
                    'required' => true,
                    'type'     => 'array',
                    'maxItems' => 25,
                    'items'    => [
                        'type'       => 'object',
                        'properties' => [
                            'method'  => [
                                'type'    => 'string',
                                /**
                                 * Filters the allowed methods for store API batch requests.
                                 *
                                 * @since 9.8.0
                                 *
                                 * @param string[] $methods Allowed methods.
                                 */
                                'enum'    => apply_filters('__experimental_woocommerce_store_api_batch_request_methods', [ 'POST', 'PUT', 'PATCH', 'DELETE' ]),
                                'default' => 'POST',
                            ],
                            'path'    => [
                                'type'     => 'string',
                                'required' => true,
                            ],
                            'body'    => [
                                'type'                 => 'object',
                                'properties'           => [],
                                'additionalProperties' => true,
                            ],
                            'headers' => [
                                'type'                 => 'object',
                                'properties'           => [],
                                'additionalProperties' => [
                                    'type'  => [ 'string', 'array' ],
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get the route response.
     *
     * @see WP_REST_Server::serve_batch_request_v1
     * https://developer.wordpress.org/reference/classes/wp_rest_server/serve_batch_request_v1/
     *
     * @throws RouteException On error.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_response(WP_REST_Request $request)
    {
        try {
            foreach ($request['requests'] as $args) {
                $parsed_path = wp_parse_url($args['path'], PHP_URL_PATH);
                if (! $parsed_path || !str_starts_with($parsed_path, '/wc/store')) {
                    throw new RouteException('woocommerce_rest_invalid_path', __('Invalid path provided.', 'woocommerce'), 400);
                }
            }
            $response = rest_get_server()->serve_batch_request_v1($request);
        } catch (RouteException $error) {
            $response = $this->get_route_error_response($error->getErrorCode(), $error->getMessage(), $error->getCode(), $error->getAdditionalData());
        } catch (\Exception $error) {
            $response = $this->get_route_error_response('woocommerce_rest_unknown_server_error', $error->getMessage(), 500);
        }

        if (is_wp_error($response)) {
            $response = $this->error_to_response($response);
        }

        $nonce = wp_create_nonce('wc_store_api');

        $response->header('Nonce', $nonce);
        $response->header('Nonce-Timestamp', time());
        $response->header('User-ID', get_current_user_id());

        return $response;
    }
}
