<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Value;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;

/**
 * A `ValueList` represents a lists of `Value`s, separated by some separation character
 * (mostly `,`, whitespace, or `/`).
 *
 * There are two types of `ValueList`s: `RuleValueList` and `CSSFunction`
 */
abstract class ValueList extends Value
{
    /**
     * @var array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string>
     *
     * @internal since 8.8.0
     */
    protected $aComponents;

    /**
     * phpcs:ignore Generic.Files.LineLength
     * @param array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string>|RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string $aComponents
     * @param string $sSeparator
     * @param int $iLineNo
     */
    public function __construct($aComponents = [], /**
     * @internal since 8.8.0
     */
        protected $sSeparator = ',', $iLineNo = 0)
    {
        parent::__construct($iLineNo);
        if (!is_array($aComponents)) {
            $aComponents = [$aComponents];
        }
        $this->aComponents = $aComponents;
    }

    /**
     * @param RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string $mComponent
     */
    public function addListComponent($mComponent): void
    {
        $this->aComponents[] = $mComponent;
    }

    /**
     * @return array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string>
     */
    public function getListComponents()
    {
        return $this->aComponents;
    }

    /**
     * @param array<int, RuleValueList|CSSFunction|CSSString|LineName|Size|URL|string> $aComponents
     */
    public function setListComponents(array $aComponents): void
    {
        $this->aComponents = $aComponents;
    }

    /**
     * @return string
     */
    public function getListSeparator()
    {
        return $this->sSeparator;
    }

    /**
     * @param string $sSeparator
     */
    public function setListSeparator($sSeparator): void
    {
        $this->sSeparator = $sSeparator;
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
     *
     * @return string
     */
    public function render($oOutputFormat)
    {
        return $oOutputFormat->implode(
            $oOutputFormat->spaceBeforeListArgumentSeparator($this->sSeparator) . $this->sSeparator
            . $oOutputFormat->spaceAfterListArgumentSeparator($this->sSeparator),
            $this->aComponents
        );
    }
}
