<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing;

/**
 * Thrown if the CSS parser encounters a token it did not expect.
 */
class UnexpectedTokenException extends SourceException
{
    /**
     * @param string $sExpected
     * @param string $sFound
     * @param string $sMatchType
     * @param int $iLineNo
     */
    public function __construct(private $sExpected, private $sFound, /**
     * Possible values: literal, identifier, count, expression, search
     */
        private $sMatchType = 'literal', $iLineNo = 0)
    {
        $sMessage = "Token “{$this->sExpected}” ({$this->sMatchType}) not found. Got “{$this->sFound}”.";
        if ($this->sMatchType === 'search') {
            $sMessage = "Search for “{$this->sExpected}” returned no results. Context: “{$this->sFound}”.";
        } elseif ($this->sMatchType === 'count') {
            $sMessage = "Next token was expected to have {$this->sExpected} chars. Context: “{$this->sFound}”.";
        } elseif ($this->sMatchType === 'identifier') {
            $sMessage = "Identifier expected. Got “{$this->sFound}”";
        } elseif ($this->sMatchType === 'custom') {
            $sMessage = trim("{$this->sExpected} {$this->sFound}");
        }

        parent::__construct($sMessage, $iLineNo);
    }
}
