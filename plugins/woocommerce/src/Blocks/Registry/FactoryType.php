<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Blocks\Registry;

/**
 * Definition for the FactoryType dependency type.
 *
 * @since 2.5.0
 */
class Factory_Type extends Abstract_Dependency_Type
{
    /**
     * Invokes and returns the value from the stored internal callback.
     *
     * @param Container $container  An instance of the dependency injection
     *                              container.
     *
     * @return mixed
     */
    public function get(Container $container)
    {
        return $this->resolve_value($container);
    }
}