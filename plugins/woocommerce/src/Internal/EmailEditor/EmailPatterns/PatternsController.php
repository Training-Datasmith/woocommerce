<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email_Editor\Email_Patterns;

defined('ABSPATH') || exit;
/**
 * Controller class for registering block patterns used in the email editor.
 */
class Patterns_Controller
{
    /**
     * Initialize the controller.
     *
     * @internal
     */
    final public function init(): void
    {
        $this->register_patterns();
    }
    /**
     * Register all email editor block patterns.
     */
    public function register_patterns(): void
    {
        $patterns = [];
        $patterns[] = new Woo_Email_Content_Pattern();
        foreach ($patterns as $pattern) {
            register_block_pattern($pattern->get_namespace() . '/' . $pattern->get_name(), $pattern->get_properties());
        }
    }
}