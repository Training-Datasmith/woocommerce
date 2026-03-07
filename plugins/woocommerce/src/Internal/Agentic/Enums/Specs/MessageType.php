<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Message types as defined in the Agentic Commerce Protocol.
 */
class MessageType
{
    /**
     * Informational message.
     */
    public const INFO = 'info';

    /**
     * Warning message (deprecated in favor of info).
     */
    public const WARNING = 'warning';

    /**
     * Error message.
     */
    public const ERROR = 'error';
}
