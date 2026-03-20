<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Email_Editor\Personalization_Tags;

use Automattic\Woo_Commerce\Email_Editor\Engine\Personalization_Tags\Personalization_Tags_Registry;
/**
 * Abstract class for personalization tag providers.
 *
 * @internal
 */
abstract class Abstract_Tag_Provider
{
    /**
     * Register tags with the registry.
     *
     * @param Personalization_Tags_Registry $registry The personalization tags registry.
     */
    abstract public function register_tags(Personalization_Tags_Registry $registry): void;
}