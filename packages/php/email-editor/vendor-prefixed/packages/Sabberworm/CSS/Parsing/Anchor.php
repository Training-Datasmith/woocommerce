<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing;

/**
 * @internal since 8.7.0
 */
class Anchor
{
    /**
     * @param int $iPosition
     */
    public function __construct(private $iPosition, private readonly ParserState $oParserState)
    {
    }

    public function backtrack(): void
    {
        $this->oParserState->setPosition($this->iPosition);
    }
}
