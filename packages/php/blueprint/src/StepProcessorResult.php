<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

use InvalidArgumentException;

/**
 * A class returned by StepProcessor classes containing result of the process and messages.
 */
class StepProcessorResult
{
    public const MESSAGE_TYPES = [ 'error', 'info', 'debug', 'warn' ];

    /**
     * Messages
     */
    private array $messages = [];

    /**
     * Construct.
     *
     * @param bool   $success Indicate whether the process was success or not.
     * @param string $step_name The name of the step.
     */
    public function __construct(
        /**
         * Indicate whether the process was success or not
         */
        private readonly bool $success,
        /**
         * Step name
         */
        private string $step_name
    ) {
    }

    /**
     * Get messages.
     *
     * @param string $step_name The name of the step.
     */
    public function set_step_name(string $step_name): void
    {
        $this->step_name = $step_name;
    }

    /**
     * Create a new instance with $success = true.
     *
     * @param string $stp_name The name of the step.
     */
    public static function success(string $stp_name): self
    {
        return (new self(true, $stp_name));
    }

    /**
     * Add a new message.
     *
     * @param string $message message.
     * @param string $type one of error, info.
     *
     * @throws InvalidArgumentException When incorrect type is given.
     */
    public function add_message(string $message, string $type = 'error'): void
    {
        if (! in_array($type, self::MESSAGE_TYPES, true)) {
            // phpcs:ignore
            throw new InvalidArgumentException("{$type} is not allowed. Type must be one of " . implode(',', self::MESSAGE_TYPES));
        }

        $this->messages[] = compact('message', 'type');
    }

    /**
     * Merge messages from another StepProcessorResult instance.
     *
     * @param StepProcessorResult $other The other StepProcessorResult instance.
     */
    public function merge_messages(StepProcessorResult $other): void
    {
        $this->messages = array_merge($this->messages, $other->get_messages());
    }

    /**
     * Add a new error message.
     *
     * @param string $message message.
     */
    public function add_error(string $message): void
    {
        $this->add_message($message);
    }

    /**
     * Add a new debug message.
     *
     * @param string $message message.
     */
    public function add_debug(string $message): void
    {
        $this->add_message($message, 'debug');
    }

    /**
     * Add a new info message.
     *
     * @param string $message message.
     */
    public function add_info(string $message): void
    {
        $this->add_message($message, 'info');
    }

    /**
     * Add a new warn message.
     *
     * @param string $message message.
     */
    public function add_warn(string $message): void
    {
        $this->add_message($message, 'warn');
    }

    /**
     * Filter messages.
     *
     * @param string $type one of all, error, and info.
     */
    public function get_messages(string $type = 'all'): array
    {
        if ('all' === $type) {
            return $this->messages;
        }

        return array_filter(
            $this->messages,
            fn (array $message) => $type === $message['type']
        );
    }

    /**
     * Check to see if the result was success.
     */
    public function is_success(): bool
    {
        return true === $this->success && 0 === count($this->get_messages('error'));
    }

    /**
     * Get the name of the step.
     *
     * @return string The name of the step.
     */
    public function get_step_name(): string
    {
        return $this->step_name;
    }
}
