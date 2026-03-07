<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

/**
 * Trait for block formatted template.
 */
trait BlockFormattedTemplateTrait
{
    /**
     * Get the block configuration as a formatted template.
     *
     * @return array The block configuration as a formatted template.
     */
    public function get_formatted_template(): array
    {
        return [
            $this->get_name(),
            array_merge(
                $this->get_attributes(),
                [
                    '_templateBlockId'    => $this->get_id(),
                    '_templateBlockOrder' => $this->get_order(),
                ],
                ! empty($this->get_hide_conditions()) ? [
                    '_templateBlockHideConditions' => $this->get_formatted_hide_conditions(),
                ] : [],
                ! empty($this->get_disable_conditions()) ? [
                    '_templateBlockDisableConditions' => $this->get_formatted_disable_conditions(),
                ] : [],
            ),
        ];
    }

    /**
     * Get the block hide conditions formatted for inclusion in a formatted template.
     */
    private function get_formatted_hide_conditions(): array
    {
        return $this->format_conditions($this->get_hide_conditions());
    }

    /**
     * Get the block disable conditions formatted for inclusion in a formatted template.
     */
    private function get_formatted_disable_conditions(): array
    {
        return $this->format_conditions($this->get_disable_conditions());
    }

    /**
     * Formats conditions in the expected format to include in the template.
     *
     * @param array $conditions The conditions to format.
     */
    private function format_conditions($conditions): array
    {
        return array_map(
            fn (array $condition) => [
                    'expression' => $condition['expression'],
                ],
            array_values($conditions)
        );
    }
}
