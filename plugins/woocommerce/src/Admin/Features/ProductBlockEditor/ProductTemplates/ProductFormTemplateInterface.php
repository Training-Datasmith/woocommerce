<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Interface for block containers.
 */
interface ProductFormTemplateInterface extends BlockTemplateInterface
{
    /**
     * Adds a new group block.
     *
     * @param array $block_config block config.
     * @return GroupInterface new group block.
     */
    public function add_group(array $block_config): GroupInterface;

    /**
     * Gets Group block by id.
     *
     * @param string $group_id group id.
     */
    public function get_group_by_id(string $group_id): ?GroupInterface;

    /**
     * Gets Section block by id.
     *
     * @param string $section_id section id.
     */
    public function get_section_by_id(string $section_id): ?SectionInterface;

    /**
     * Gets subsection block by id.
     *
     * @param string $subsection_id subsection id.
     */
    public function get_subsection_by_id(string $subsection_id): ?SubsectionInterface;

    /**
     * Gets Block by id.
     *
     * @param string $block_id block id.
     */
    public function get_block_by_id(string $block_id): ?BlockInterface;
}
