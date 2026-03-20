<?php

declare (strict_types=1);
/**
 * ContainerException class file.
 */
namespace Automattic\Woo_Commerce\Internal\Dependency_Management;

/**
 * Class ContainerException.
 * Used to signal error conditions related to the dependency injection container.
 */
class Container_Exception extends \Exception
{
    /**
     * Create a new instance of the class.
     *
     * @param null            $message The exception message to throw.
     * @param int             $code The error code.
     * @param \Exception|null $previous The previous throwable used for exception chaining.
     */
    public function __construct($message = null, $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}