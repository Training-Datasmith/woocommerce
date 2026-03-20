<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Agentic\Enums\Specs;

/**
 * Content types for messages as defined in the Agentic Commerce Protocol.
 */
class Message_Content_Type
{
    /**
     * Plain text content.
     */
    public const PLAIN = 'plain';
    /**
     * Markdown formatted content.
     */
    public const MARKDOWN = 'markdown';
}