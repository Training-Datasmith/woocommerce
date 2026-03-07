<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Cli\ExportCli;
use Automattic\WooCommerce\Blueprint\Cli\ImportCli;

$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}
/**
 * Class Cli.
 *
 * This class is included and execute from WC_CLI(class-wc-cli.php) to register
 * WP CLI commands.
 */
class Cli
{
    /**
     * Register WP CLI commands.
     */
    public static function register_commands(): void
    {
        \WP_CLI::add_command(
            'wc blueprint import',
            function ($args, $assoc_args): void {
                $import = new ImportCli($args[0]);
                $import->run($assoc_args);
            },
            [
                'synopsis' => [
                    [
                        'type'     => 'positional',
                        'name'     => 'schema-path',
                        'optional' => false,
                    ],
                    [
                        'type'     => 'assoc',
                        'name'     => 'show-messages',
                        'optional' => true,
                        'options'  => [ 'all', 'error', 'info', 'debug' ],
                    ],
                ],
                'when'     => 'after_wp_load',
            ]
        );

        \WP_CLI::add_command(
            'wc blueprint export',
            function ($args, array $assoc_args): void {
                $export = new ExportCli($args[0]);
                $steps  = [];

                if (isset($assoc_args['steps'])) {
                    $steps = array_map(
                        fn ($step) => trim($step),
                        explode(',', (string) $assoc_args['steps'])
                    );
                }
                $export->run(
                    [
                        'steps'  => $steps,
                        'format' => 'json',
                    ]
                );
            },
            [
                'synopsis' => [
                    [
                        'type'     => 'positional',
                        'name'     => 'save-to',
                        'optional' => false,
                    ],
                    [
                        'type'     => 'assoc',
                        'name'     => 'steps',
                        'optional' => true,
                    ],
                ],
                'when'     => 'after_wp_load',
            ]
        );
    }
}
