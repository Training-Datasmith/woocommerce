<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Sabberworm\CSS\Comment;

use Automattic\WooCommerce\Vendor\Sabberworm\CSS\OutputFormat;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Position;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Position\Positionable;
use Automattic\WooCommerce\Vendor\Sabberworm\CSS\Renderable;

class Comment implements Positionable, Renderable
{
    use Position;

    /**
     * @param string $sComment
     * @param int $iLineNo
     */
    public function __construct(/**
     * @internal since 8.8.0
     */
        protected $sComment = '',
        $iLineNo = 0
    ) {
        $this->setPosition($iLineNo);
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->sComment;
    }

    /**
     * @param string $sComment
     */
    public function setComment($sComment): void
    {
        $this->sComment = $sComment;
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
        return '/*' . $this->sComment . '*/';
    }
}
