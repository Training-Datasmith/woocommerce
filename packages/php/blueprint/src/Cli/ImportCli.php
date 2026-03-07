<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint\Cli;

use Automattic\WooCommerce\Blueprint\ImportSchema;
use Automattic\WooCommerce\Blueprint\ResultFormatters\CliResultFormatter;

/**
 * Class ImportCli
 */
class ImportCli
{
    /**
     * ImportCli constructor.
     *
     * @param string $schema_path The path to the schema file.
     */
    public function __construct(
        /**
         * Schema path
         */
        private $schema_path
    ) {
    }

    /**
     * Run the import process.
     *
     * @param array $optional_args Optional arguments.
     */
    public function run(array $optional_args): void
    {
        try {
            $blueprint = ImportSchema::create_from_file($this->schema_path);
        } catch (\Exception $e) {
            \WP_CLI::error($e->getMessage());
            return;
        }

        $results = $blueprint->import();

        $result_formatter = new CliResultFormatter($results);
        $is_success       = $result_formatter->is_success();

        if (isset($optional_args['show-messages'])) {
            $result_formatter->format($optional_args['show-messages']);
        }

        if ($is_success) {
            \WP_CLI::success("$this->schema_path imported successfully");
        } else {
            \WP_CLI::error("Failed to import $this->schema_path. Run with --show-messages=all to debug");
        }
    }
}
