<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Cli;

use Automattic\WooCommerce\Blueprint\ExportSchema;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportCli
 *
 * This class handles the CLI commands for exporting schemas.
 *
 * @package Automattic\WooCommerce\Blueprint\Cli
 */
class ExportCli
{
    use UseWPFunctions;

    /**
     * ExportCli constructor.
     *
     * @param string $save_to The path where the exported schema will be saved.
     */
    public function __construct(
        /**
         * The path where the exported schema will be saved.
         */
        private string $save_to
    ) {
    }

    /**
     * Run the export process.
     *
     * @param array $args The arguments for the export process.
     */
    public function run(array $args = []): void
    {
        if (! isset($args['steps'])) {
            $args['steps'] = [];
        }

        $exporter = new ExportSchema();

        $result = $exporter->export($args['steps']);
        if (is_wp_error($result)) {
            \WP_CLI::error($result->get_error_message());
            return;
        }

        $is_saved = $this->wp_filesystem_put_contents($this->save_to, wp_json_encode($result, JSON_PRETTY_PRINT));

        if (false === $is_saved) {
            \WP_CLI::error("Failed to save to {$this->save_to}");
        } else {
            \WP_CLI::success("Exported JSON to {$this->save_to}");
        }
    }
}
