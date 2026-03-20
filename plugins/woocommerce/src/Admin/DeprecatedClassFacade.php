<?php

declare (strict_types=1);
/**
 * A facade to allow deprecating an entire class. Calling instance or static
 * functions on the facade triggers a deprecation notice before calling the
 * underlying function.
 *
 * Use it by extending DeprecatedClassFacade in your facade class, setting the
 * static $facade_over_classname string to the name of the class to build
 * a facade over, and setting the static $deprecated_in_version to the version
 * that the class was deprecated in. Eg.:
 *
 * class DeprecatedGoose extends DeprecatedClassFacade {
 *     static $facade_over_classname = 'Goose';
 *     static $deprecated_in_version = '1.7.0';
 * }
 */
namespace Automattic\Woo_Commerce\Admin;

defined('ABSPATH') || exit;
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
/**
 * A facade to allow deprecating an entire class.
 */
class Deprecated_Class_Facade
{
    /**
     * The instance that this facade covers over.
     *
     * @var object
     */
    protected $instance;
    /**
     * The name of the non-deprecated class that this facade covers.
     *
     * @var string
     */
    protected static $facade_over_classname = '';
    /**
     * The version that this class was deprecated in.
     *
     * @var string
     */
    protected static $deprecated_in_version = '';
    /**
     * Static array of logged messages.
     */
    private static array $logged_messages = [];
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('' !== static::$facade_over_classname) {
            $this->instance = new static::$facade_over_classname();
        }
    }
    /**
     * Log a deprecation to the error log.
     *
     * @param string $function The name of the deprecated function being called.
     */
    private static function log_deprecation(string $function): void
    {
        $message = sprintf('%1$s is deprecated since version %2$s! Use %3$s instead.', static::class . '::' . $function, static::$deprecated_in_version, static::$facade_over_classname . '::' . $function);
        if ('' !== static::$facade_over_classname) {
            $message = $message . sprintf(' Use %s instead.', static::$facade_over_classname . '::' . $function);
        }
        // Only log when the message has not been logged before.
        if (!in_array($message, self::$logged_messages, true)) {
            error_log($message);
            // phpcs:ignore
            self::$logged_messages[] = $message;
        }
    }
    /**
     * Executes when calling any function on an instance of this class.
     *
     * @param string $name      The name of the function being called.
     * @param array  $arguments An array of the arguments to the function call.
     */
    public function __call(string $name, array $arguments)
    {
        self::log_deprecation($name);
        if (!isset($this->instance)) {
            return;
        }
        return call_user_func_array([$this->instance, $name], $arguments);
    }
    /**
     * Executes when calling any static function on this class.
     *
     * @param string $name      The name of the function being called.
     * @param array  $arguments An array of the arguments to the function call.
     */
    public static function __callStatic(string $name, array $arguments)
    {
        self::log_deprecation($name);
        if ('' === static::$facade_over_classname) {
            return;
        }
        return call_user_func_array([static::$facade_over_classname, $name], $arguments);
    }
}