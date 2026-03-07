<?php

/**
 * HTML to Text Exception class
 *
 * This file was extracted from the `soundasleep/html2text` package.
 * Copyright (c) 2019 Jevon Wright
 * MIT License
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

/**
 * Exception thrown when HTML to text conversion fails
 */
class Html2Text_Exception extends \Exception
{
    /**
     * Constructor
     *
     * @param string $message Error message.
     * @param string $more_info Additional error information.
     */
    public function __construct(string $message = '', /**
     * Additional information about the error
     */
        private readonly string $more_info = '')
    {
        parent::__construct($message);
    }

    /**
     * Returns additional error information
     *
     * @return string Additional error information.
     */
    public function get_more_info(): string
    {
        return $this->more_info;
    }
}
