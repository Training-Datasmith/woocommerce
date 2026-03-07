<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\CSSList;

use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\EmailEditorVendor\Sabberworm\CSS\Property\AtRule;

class KeyFrame extends CSSList implements AtRule
{
    /**
     * @var string|null
     */
    private $vendorKeyFrame;

    /**
     * @var string|null
     */
    private $animationName;

    /**
     * @param int $iLineNo
     */
    public function __construct($iLineNo = 0)
    {
        parent::__construct($iLineNo);
        $this->vendorKeyFrame = null;
        $this->animationName = null;
    }

    /**
     * @param string $vendorKeyFrame
     */
    public function setVendorKeyFrame($vendorKeyFrame): void
    {
        $this->vendorKeyFrame = $vendorKeyFrame;
    }

    /**
     * @return string|null
     */
    public function getVendorKeyFrame()
    {
        return $this->vendorKeyFrame;
    }

    /**
     * @param string $animationName
     */
    public function setAnimationName($animationName): void
    {
        $this->animationName = $animationName;
    }

    /**
     * @return string|null
     */
    public function getAnimationName()
    {
        return $this->animationName;
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
        $sResult = $oOutputFormat->comments($this);
        $sResult .= "@{$this->vendorKeyFrame} {$this->animationName}{$oOutputFormat->spaceBeforeOpeningBrace()}{";
        $sResult .= $this->renderListContents($oOutputFormat);
        return $sResult . '}';
    }

    public function isRootList(): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function atRuleName()
    {
        return $this->vendorKeyFrame;
    }

    /**
     * @return string|null
     */
    public function atRuleArgs()
    {
        return $this->animationName;
    }
}
