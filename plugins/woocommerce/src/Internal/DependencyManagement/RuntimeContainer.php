<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Internal\Dependency_Management;

use Automattic\Woo_Commerce\Blocks\Package as BlocksPackage;
use Automattic\Woo_Commerce\Store_Api\Store_Api;
use Automattic\Woo_Commerce\Utilities\String_Util;
/**
 * Dependency injection container used at runtime.
 *
 * This is a simple container that doesn't implement explicit class registration.
 * Instead, all the classes in the Automattic\WooCommerce namespace can be resolved
 * and are considered as implicitly registered as single-instance classes
 * (so each class will be instantiated only once and the instance will be cached).
 */
class Runtime_Container
{
    /**
     * The root namespace of all WooCommerce classes in the `src` directory.
     *
     * @var string
     */
    public const WOOCOMMERCE_NAMESPACE = 'Automattic\WooCommerce\\';
    /**
     * Cache of classes already resolved.
     */
    protected array $resolved_cache;
    /**
     * Initializes a new instance of the class.
     *
     * @param array $initial_resolved_cache Dictionary of class name => instance, to be used as the starting point for the resolved classes cache.
     */
    public function __construct(
        /**
         * A copy of the initial resolved classes cache passed to the constructor.
         */
        protected array $initial_resolved_cache
    )
    {
        $this->resolved_cache = $this->initial_resolved_cache;
    }
    /**
     * Get an instance of a class.
     *
     * ContainerException will be thrown in these cases:
     *
     * - $class_name is outside the WooCommerce root namespace (and wasn't included in the initial resolve cache).
     * - The class referred by $class_name doesn't exist.
     * - Recursive resolution condition found.
     * - Reflection exception thrown when instantiating or initializing the class.
     *
     * A "recursive resolution condition" happens when class A depends on class B and at the same time class B depends on class A, directly or indirectly;
     * without proper handling this would lead to an infinite loop.
     *
     * Note that this method throwing ContainerException implies that code fixes are needed, it's not an error condition that's recoverable at runtime.
     *
     * @template T of object
     * @param string $class_name Class name.
     * @phpstan-param class-string<T> $class_name
     *
     * @return T Object instance.
     * @throws ContainerException Error when resolving the class to an object instance.
     * @throws \Exception Exception thrown in the constructor or in the 'init' method of one of the resolved classes.
     */
    public function get(string $class_name)
    {
        $class_name = trim($class_name, '\\');
        $resolve_chain = [];
        // @phpstan-ignore return.type (get_core uses reflection to instantiate the correct class type at runtime)
        return $this->get_core($class_name, $resolve_chain);
    }
    /**
     * Core function to get an instance of a class.
     *
     * @param string $class_name The class name.
     * @param array  $resolve_chain Classes already resolved in this resolution chain. Passed between recursive calls to the method in order to detect a recursive resolution condition.
     * @return object The resolved object.
     * @throws ContainerException Error when resolving the class to an object instance.
     */
    protected function get_core(string $class_name, array &$resolve_chain)
    {
        if (isset($this->resolved_cache[$class_name])) {
            return $this->resolved_cache[$class_name];
        }
        // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        if (in_array($class_name, $resolve_chain, true)) {
            throw new Container_Exception("Recursive resolution of class '{$class_name}'. Resolution chain: " . implode(', ', $resolve_chain));
        }
        if (!$this->is_class_allowed($class_name)) {
            throw new Container_Exception("Attempt to get an instance of class '{$class_name}', which is not in the " . self::WOOCOMMERCE_NAMESPACE . ' namespace. Did you forget to add a namespace import?');
        }
        if (!class_exists($class_name)) {
            throw new Container_Exception("Attempt to get an instance of class '{$class_name}', which doesn't exist.");
        }
        // Account for the containers used by the Store API and Blocks.
        if (String_Util::starts_with($class_name, 'Automattic\WooCommerce\StoreApi\\')) {
            return Store_Api::container()->get($class_name);
        }
        if (String_Util::starts_with($class_name, 'Automattic\WooCommerce\Blocks\\')) {
            return Blocks_Package::container()->get($class_name);
        }
        $resolve_chain[] = $class_name;
        try {
            $instance = $this->instantiate_class_using_reflection($class_name, $resolve_chain);
        } catch (\Reflection_Exception $e) {
            throw new Container_Exception("Reflection error when resolving '{$class_name}': (" . $e::class . ") {$e->get_message()}", 0, $e);
        }
        // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        $this->resolved_cache[$class_name] = $instance;
        return $instance;
    }
    // phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
    /**
     * Get an instance of a class using reflection.
     * This method recursively calls 'get_core' (which in turn calls this method) for each of the arguments
     * in the 'init' method of the resolved class (if the method is public and non-static).
     *
     * @param string $class_name The name of the class to resolve.
     * @param array  $resolve_chain Classes already resolved in this resolution chain. Passed between recursive calls to the method in order to detect a recursive resolution condition.
     * @return object The resolved object.
     *
     * @throws ContainerException The 'init' method has invalid arguments.
     * @throws \ReflectionException Something went wrong when using reflection to get information about the class to resolve.
     */
    private function instantiate_class_using_reflection(string $class_name, array &$resolve_chain): object
    {
        $ref_class = new \ReflectionClass($class_name);
        // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        $constructor = $ref_class->get_constructor();
        if (!is_null($constructor)) {
            if (!$constructor->is_public()) {
                throw new Container_Exception("Error resolving '{$class_name}': the class doesn't have a public constructor.");
            }
            $constructor_arguments = $constructor->get_parameters();
            foreach ($constructor_arguments as $argument) {
                if (!$argument->is_optional()) {
                    throw new Container_Exception("Error resolving '{$class_name}': the class constructor has non-optional arguments.");
                }
            }
        }
        $instance = $ref_class->new_instance();
        if (!$ref_class->has_method('init')) {
            return $instance;
        }
        $init_method = $ref_class->get_method('init');
        if (!$init_method->is_public() || $init_method->is_static()) {
            return $instance;
        }
        $init_args = $init_method->get_parameters();
        $init_arg_instances = array_map(function (\ReflectionParameter $arg) use ($class_name, &$resolve_chain) {
            $arg_type = $arg->get_type();
            if (!$arg_type instanceof \ReflectionNamedType) {
                throw new Container_Exception("Error resolving '{$class_name}': argument '\${$arg->get_name()}' doesn't have a type declaration.");
            }
            if ($arg_type->is_builtin()) {
                throw new Container_Exception("Error resolving '{$class_name}': argument '\${$arg->get_name()}' is not of a class type.");
            }
            if ($arg->is_passed_by_reference()) {
                throw new Container_Exception("Error resolving '{$class_name}': argument '\${$arg->get_name()}' is passed by reference.");
            }
            return $this->get_core($arg_type->get_name(), $resolve_chain);
        }, $init_args);
        // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        $init_method->invoke($instance, ...$init_arg_instances);
        return $instance;
    }
    // phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber
    /**
     * Tells if the 'get' method can be used to resolve a given class.
     *
     * Note that 'get' can throw an exception even if this method returns true,
     * for example for classes that are in the correct namespace but don't have a public constructor.
     *
     * @param string $class_name The class name.
     * @return bool True if the class with the supplied name can be resolved with 'get'.
     */
    public function has(string $class_name): bool
    {
        $class_name = trim($class_name, '\\');
        if ($this->is_class_allowed($class_name)) {
            return true;
        }
        return isset($this->resolved_cache[$class_name]);
    }
    /**
     * Checks to see whether a class is allowed to be registered.
     *
     * @param string $class_name The class to check.
     *
     * @return bool True if the class is allowed to be registered, false otherwise.
     */
    protected function is_class_allowed(string $class_name): bool
    {
        return String_Util::starts_with($class_name, self::WOOCOMMERCE_NAMESPACE, false);
    }
}