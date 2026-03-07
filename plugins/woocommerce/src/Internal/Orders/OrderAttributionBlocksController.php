<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use WP_Error;

/**
 * Class OrderAttributionBlocksController
 *
 * @since 8.5.0
 */
class OrderAttributionBlocksController implements RegisterHooksInterface
{
    use ScriptDebug;

    /**
     * Instance of the features controller.
     */
    private ?\Automattic\WooCommerce\Internal\Features\FeaturesController $features_controller = null;

    /**
     * ExtendSchema instance.
     */
    private ?\Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema $extend_schema = null;

    /**
     * Instance of the order attribution controller.
     */
    private ?\Automattic\WooCommerce\Internal\Orders\OrderAttributionController $order_attribution_controller = null;

    /**
     * Bind dependencies on init.
     *
     * @internal
     *
     * @param ExtendSchema               $extend_schema                 ExtendSchema instance.
     * @param FeaturesController         $features_controller           Features controller.
     * @param OrderAttributionController $order_attribution_controller Instance of the order attribution controller.
     */
    final public function init(
        ExtendSchema $extend_schema,
        FeaturesController $features_controller,
        OrderAttributionController $order_attribution_controller
    ): void {
        $this->extend_schema                = $extend_schema;
        $this->features_controller          = $features_controller;
        $this->order_attribution_controller = $order_attribution_controller;
    }

    /**
     * Register this class instance to the appropriate hooks.
     */
    public function register(): void
    {
        add_action('init', $this->on_init(...));
    }

    /**
     * Hook into WordPress on init.
     */
    public function on_init(): void
    {
        // Bail if the feature is not enabled.
        if (! $this->features_controller->feature_is_enabled('order_attribution')) {
            return;
        }

        $this->extend_api();
    }

    /**
     * Extend the Store API.
     */
    private function extend_api(): void
    {
        $this->extend_schema->register_endpoint_data(
            [
                'endpoint'        => CheckoutSchema::IDENTIFIER,
                'namespace'       => 'woocommerce/order-attribution',
                'schema_callback' => $this->get_schema_callback(),
            ]
        );
        // Update order based on extended data.
        add_action(
            'woocommerce_store_api_checkout_update_order_from_request',
            function ($order, $request): void {
                $extensions = $request->get_param('extensions');
                $params     = $extensions['woocommerce/order-attribution'] ?? [];

                if (empty($params)) {
                    return;
                }

                // Check if this order already has any attribution data to prevent duplicates attribution data.
                if ($this->order_attribution_controller->has_attribution($order)) {
                    return;
                }

                /**
                 * Run an action to save order attribution data.
                 *
                 * @since 8.5.0
                 *
                 * @param WC_Order $order  The order object.
                 * @param array    $params Unprefixed order attribution data.
                 */
                do_action('woocommerce_order_save_attribution_data', $order, $params);
            },
            10,
            2
        );
    }

    /**
     * Get the schema callback.
     *
     * @return callable
     */
    private function get_schema_callback()
    {
        return function (): array {
            $schema      = [];
            $field_names = $this->order_attribution_controller->get_field_names();

            $validate_callback = function ($value): \WP_Error|true {
                if (! is_string($value) && null !== $value) {
                    return new WP_Error(
                        'api-error',
                        sprintf(
                            /* translators: %s is the property type */
                            esc_html__('Value of type %s was posted to the order attribution callback', 'woocommerce'),
                            gettype($value)
                        )
                    );
                }

                return true;
            };

            $sanitize_callback = (fn ($value) => sanitize_text_field($value));

            foreach ($field_names as $field_name) {
                $schema[ $field_name ] = [
                    'description' => sprintf(
                        /* translators: %s is the field name */
                        __('Order attribution field: %s', 'woocommerce'),
                        esc_html($field_name)
                    ),
                    'type'        => [ 'string', 'null' ],
                    'context'     => [],
                    'arg_options' => [
                        'validate_callback' => $validate_callback,
                        'sanitize_callback' => $sanitize_callback,
                    ],
                ];
            }

            return $schema;
        };
    }
}
