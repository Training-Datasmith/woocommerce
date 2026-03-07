<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

/**
 * This class represents URLs in CSS. `URL`s always output in `URL("")` notation.
 */
class URL extends PrimitiveValue
{
    /**
     * @param int $iLineNo
     */
    public function __construct(private CSSString $oURL, $iLineNo = 0)
    {
        parent::__construct($iLineNo);
    }

    /**
     *
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     * @internal since V8.8.0
     */
    public static function parse(ParserState $oParserState): \Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\URL
    {
        $oAnchor = $oParserState->anchor();
        $sIdentifier = '';
        for ($i = 0; $i < 3; $i++) {
            $sChar = $oParserState->parseCharacter(true);
            if ($sChar === null) {
                break;
            }
            $sIdentifier .= $sChar;
        }
        $bUseUrl = $oParserState->streql($sIdentifier, 'url');
        if ($bUseUrl) {
            $oParserState->consumeWhiteSpace();
            $oParserState->consume('(');
        } else {
            $oAnchor->backtrack();
        }
        $oParserState->consumeWhiteSpace();
        $oResult = new URL(CSSString::parse($oParserState), $oParserState->currentLine());
        if ($bUseUrl) {
            $oParserState->consumeWhiteSpace();
            $oParserState->consume(')');
        }
        return $oResult;
    }

    public function setURL(CSSString $oURL): void
    {
        $this->oURL = $oURL;
    }

    /**
     * @return CSSString
     */
    public function getURL()
    {
        return $this->oURL;
    }

    /**
     * @deprecated in V8.8.0, will be removed in V9.0.0. Use `render` instead.
     */
    public function __toString(): string
    {
        return $this->render(new OutputFormat());
    }

    /**
     * @param OutputFormat|null $oOutputFormat
     */
    public function render($oOutputFormat): string
    {
        return "url({$this->oURL->render($oOutputFormat)})";
    }
}
