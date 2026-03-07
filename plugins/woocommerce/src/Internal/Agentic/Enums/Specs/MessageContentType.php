<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Content types for messages as defined in the Agentic Commerce Protocol.
 */
class MessageContentType
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
