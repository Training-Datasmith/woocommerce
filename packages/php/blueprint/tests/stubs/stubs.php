<?php

declare(strict_types=1);
/**
 * Stubs for WooCommerce classes and interfaces.
 */

if (! class_exists('WC_Log_Levels', false)) {
    /**
     * WC Log Levels Class
     */
    class WC_Log_Levels
    {
        public const EMERGENCY = 'emergency';
        public const ALERT     = 'alert';
        public const CRITICAL  = 'critical';
        public const ERROR     = 'error';
        public const WARNING   = 'warning';
        public const NOTICE    = 'notice';
        public const INFO      = 'info';
        public const DEBUG     = 'debug';
    }
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
if (! interface_exists('WC_Logger_Interface')) {
    /**
     * WC Logger Interface
     */
    interface WC_Logger_Interface
    {
        /**
         * Log message with level.
         *
         * @param string $level Log level.
         * @param string $message Log message.
         * @param array  $context Optional. Additional information for log handlers.
         */
        public function log($level, $message, $context = []);

        /**
         * Add a log entry.
         *
         * @param string $handle Log handle.
         * @param string $message Log message.
         * @param string $level Log level.
         */
        public function add($handle, $message, $level = 'notice');

        /**
         * Add an emergency level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function emergency($message, $context = []);

        /**
         * Add an alert level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function alert($message, $context = []);

        /**
         * Add a critical level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function critical($message, $context = []);

        /**
         * Add an error level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function error($message, $context = []);

        /**
         * Add a warning level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function warning($message, $context = []);

        /**
         * Add a notice level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function notice($message, $context = []);

        /**
         * Add an info level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function info($message, $context = []);

        /**
         * Add a debug level message.
         *
         * @param string $message Log message.
         * @param array  $context Log context.
         */
        public function debug($message, $context = []);
    }
}

if (! function_exists('wc_get_logger')) {
    /**
     * Mock wc_get_logger function.
     *
     * @return WC_Logger_Interface
     */
    function wc_get_logger() // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
    {return Mockery::mock('WC_Logger_Interface')->shouldReceive('log')->getMock();
    }
}
