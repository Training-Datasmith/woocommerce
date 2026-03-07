<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\ParserState;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\SourceException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Parsing\UnexpectedTokenException;

/**
 * A `CSSFunction` represents a special kind of value that also contains a function name and where the values are the
 * function’s arguments. It also handles equals-sign-separated argument lists like `filter: alpha(opacity=90);`.
 */
class CSSFunction extends ValueList
{
    /**
     * @param string $sName
     * @param RuleValueList|array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string> $aArguments
     * @param string $sSeparator
     * @param int $iLineNo
     */
    public function __construct(/**
     * @internal since 8.8.0
     */
        protected $sName,
        $aArguments,
        $sSeparator = ',',
        $iLineNo = 0
    ) {
        if ($aArguments instanceof RuleValueList) {
            $sSeparator = $aArguments->getListSeparator();
            $aArguments = $aArguments->getListComponents();
        }
        $this->setPosition($iLineNo); // TODO: redundant?
        parent::__construct($aArguments, $sSeparator, $iLineNo);
    }

    /**
     * @param bool $bIgnoreCase
     *
     *
     * @throws SourceException
     * @throws UnexpectedEOFException
     * @throws UnexpectedTokenException
     *
     * @internal since V8.8.0
     */
    public static function parse(ParserState $oParserState, $bIgnoreCase = false): \Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value\CSSFunction
    {
        $mResult = $oParserState->parseIdentifier($bIgnoreCase);
        $oParserState->consume('(');
        $aArguments = Value::parseValue($oParserState, ['=', ' ', ',']);
        $mResult = new CSSFunction($mResult, $aArguments, ',', $oParserState->currentLine());
        $oParserState->consume(')');
        return $mResult;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->sName;
    }

    /**
     * @param string $sName
     */
    public function setName($sName): void
    {
        $this->sName = $sName;
    }

    /**
     * @return array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string>
     */
    public function getArguments()
    {
        return $this->aComponents;
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
        $aArguments = parent::render($oOutputFormat);
        return "{$this->sName}({$aArguments})";
    }
}
