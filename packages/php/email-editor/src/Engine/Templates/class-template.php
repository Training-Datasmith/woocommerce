<?php

/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Templates;

/**
 * The class represents a template
 */
class Template
{
    /**
     * The template name used for block template registration.
     */
    private readonly string $name;

    /**
     * Constructor of the class.
     *
     * @param string   $plugin_uri The plugin uri.
     * @param string   $slug The template slug.
     * @param string   $title The template title.
     * @param string   $description The template description.
     * @param string   $content The template content.
     * @param string[] $post_types The list of post types supported by the template.
     */
    public function __construct(
        /**
         * Plugin uri used in the template name.
         */
        private readonly string $plugin_uri,
        /**
         * The template slug used in the template name.
         */
        private readonly string $slug,
        /**
         * The template title.
         */
        private readonly string $title,
        /**
         * The template description.
         */
        private readonly string $description,
        /**
         * The template content.
         */
        private readonly string $content,
        /**
         * The list of supoorted post types.
         */
        private readonly array $post_types = []
    ) {
        $this->name        = "{$this->plugin_uri}//{$this->slug}";
    }
    /**
     * Get the plugin uri.
     */
    public function get_pluginuri(): string
    {
        return $this->plugin_uri;
    }
    /**
     * Get the template slug.
     */
    public function get_slug(): string
    {
        return $this->slug;
    }

    /**
     * Get the template name composed from the plugin_uri and the slug.
     */
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * Get the template title.
     */
    public function get_title(): string
    {
        return $this->title;
    }
    /**
     * Get the template description.
     */
    public function get_description(): string
    {
        return $this->description;
    }
    /**
     * Get the template content.
     */
    public function get_content(): string
    {
        return $this->content;
    }
    /**
     * Get the list of supported post types.
     *
     * @return string[]
     */
    public function get_post_types(): array
    {
        return $this->post_types;
    }
}
