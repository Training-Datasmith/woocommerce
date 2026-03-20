<?php

declare (strict_types=1);
namespace Automattic\Woo_Commerce\Admin\Remote_Specs;

/**
 * RemoteSpecsEngine class.
 */
abstract class Remote_Specs_Engine
{
    /**
     * Log errors.
     *
     * @param array $errors Array of errors from \Throwable interface.
     */
    public static function log_errors($errors = []): void
    {
        if (true !== defined('WP_ENVIRONMENT_TYPE') || !in_array(constant('WP_ENVIRONMENT_TYPE'), ['development', 'local'], true)) {
            return;
        }
        $logger = wc_get_logger();
        $error_messages = [];
        foreach ($errors as $error) {
            if (isset($error) && method_exists($error, 'getMessage')) {
                $error_messages[] = $error->get_message();
            }
        }
        $logger->error('Error while evaluating specs', ['source' => 'remotespecsengine-errors', 'class' => static::class, 'errors' => $error_messages]);
    }
}