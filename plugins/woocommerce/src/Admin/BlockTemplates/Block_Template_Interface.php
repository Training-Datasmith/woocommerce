<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Block_Templates;

/**
 * Interface for block-based template.
 */
interface Block_Template_Interface extends Container_Interface
{
    /**
     * Get the template ID.
     */
    public function get_id(): string;
    /**
     * Get the template title.
     */
    public function get_title(): string;
    /**
     * Get the template description.
     */
    public function get_description(): string;
    /**
     * Get the template area.
     */
    public function get_area(): string;
    /**
     * Generate a block ID based on a base.
     *
     * @param string $id_base The base to use when generating an ID.
     */
    public function generate_block_id(string $id_base): string;
    /**
     * Get the template as JSON like array.
     *
     * @return array The JSON.
     */
    public function to_json(): array;
}