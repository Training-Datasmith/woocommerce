<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

/**
 * Interface StepProcessor
 */
interface StepProcessor
{
    /**
     * Process the schema.
     *
     * @param object $schema The schema to process.
     */
    public function process($schema): StepProcessorResult;

    /**
     * Get the step class.
     */
    public function get_step_class(): string;
    /**
     * Check if the current user has the required capabilities for this step.
     *
     * @param object $schema The schema to process.
     *
     * @return bool True if the user has the required capabilities. False otherwise.
     */
    public function check_step_capabilities($schema): bool;
}
