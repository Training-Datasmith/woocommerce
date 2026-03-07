<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi;

use Automattic\WooCommerce\Blocks\Registry\Container;
use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;

/**
 * StoreApi Main Class.
 */
final class StoreApi
{
    /**
     * Init and hook in Store API functionality.
     */
    public function init(): void
    {
        /**
         * Authentication instance.
         *
         * @var Authentication $authentication
         */
        $authentication = self::container()->get(Authentication::class);

        add_filter('woocommerce_session_handler', $authentication->maybe_use_store_api_session_handler(...), 0);

        add_action(
            'rest_api_init',
            function (): void {
                if (! wc_rest_should_load_namespace('wc/store') && ! wc_rest_should_load_namespace('wc/private')) {
                    return;
                }
                self::container()->get(Legacy::class)->init();
                self::container()->get(RoutesController::class)->register_all_routes();
            }
        );
        // Runs on priority 11 after rest_api_default_filters() which is hooked at 10.
        add_action(
            'rest_api_init',
            function (): void {
                if (! wc_rest_should_load_namespace('wc/store')) {
                    return;
                }
                self::container()->get(Authentication::class)->init();
            },
            11
        );

        add_action(
            'woocommerce_blocks_pre_get_routes_from_namespace',
            function ($routes, $ns) {
                if ('wc/store/v1' !== $ns) {
                    return $routes;
                }

                return array_merge(
                    $routes,
                    self::container()->get(RoutesController::class)->get_all_routes('v1')
                );
            },
            10,
            2
        );
    }

    /**
     * Loads the DI container for Store API.
     *
     * @internal This uses the Blocks DI container. If Store API were to move to core, this container could be replaced
     * with a different compatible container.
     *
     * @param boolean $reset Used to reset the container to a fresh instance. Note: this means all dependencies will be reconstructed.
     * @return mixed
     */
    public static function container($reset = false)
    {
        static $container;

        if ($reset) {
            $container = null;
        }

        if ($container) {
            return $container;
        }

        $container = new Container();
        $container->register(
            Authentication::class,
            fn () => new Authentication()
        );
        $container->register(
            Legacy::class,
            fn () => new Legacy()
        );
        $container->register(
            RoutesController::class,
            fn ($container) => new RoutesController(
                $container->get(SchemaController::class)
            )
        );
        $container->register(
            SchemaController::class,
            fn ($container) => new SchemaController(
                $container->get(ExtendSchema::class)
            )
        );
        $container->register(
            ExtendSchema::class,
            fn ($container) => new ExtendSchema(
                $container->get(Formatters::class)
            )
        );
        $container->register(
            Formatters::class,
            function (): \Automattic\WooCommerce\StoreApi\Formatters {
                $formatters = new Formatters();
                $formatters->register('money', MoneyFormatter::class);
                $formatters->register('html', HtmlFormatter::class);
                $formatters->register('currency', CurrencyFormatter::class);
                return $formatters;
            }
        );
        return $container;
    }
}
