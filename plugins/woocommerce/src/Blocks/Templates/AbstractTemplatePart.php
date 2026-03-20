<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Templates;

/**
 * AbstractTemplatePart class.
 *
 * Shared logic for templates parts.
 *
 * @internal
 */
abstract class Abstract_Template_Part extends Abstract_Template
{
    /**
     * The template part area where the template part belongs.
     *
     * @var string
     */
    public $template_area;
}