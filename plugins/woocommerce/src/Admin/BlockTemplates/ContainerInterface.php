<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Block_Templates;

/**
 * Interface for block containers.
 */
interface Container_Interface
{
    /**
     * Get the root template that the block belongs to.
     */
    public function &get_root_template(): Block_Template_Interface;
    /**
     * Get the block configuration as a formatted template.
     */
    public function get_formatted_template(): array;
    /**
     * Get a block by ID.
     *
     * @param string $block_id The block ID.
     */
    public function get_block(string $block_id): ?Block_Interface;
    /**
     * Removes a block from the container.
     *
     * @param string $block_id The block ID.
     *
     * @throws \UnexpectedValueException If the block container is not an ancestor of the block.
     */
    public function remove_block(string $block_id);
    /**
     * Removes all blocks from the container.
     */
    public function remove_blocks();
}