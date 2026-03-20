<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Blueprint;

trait UsePubSub
{
    /**
     * Subscribers.
     */
    private array $subscribers = [];

    /**
     * Subscribe to an event with a callback.
     *
     * @param string   $event The event name.
     * @param callable $callback The callback to execute when the event is published.
     */
    public function subscribe(string $event, callable $callback): void
    {
        if (! isset($this->subscribers[ $event ])) {
            $this->subscribers[ $event ] = [];
        }

        $this->subscribers[ $event ][] = $callback;
    }

    /**
     * Publish an event to all subscribers.
     *
     * @param string $event The event name.
     * @param mixed  ...$args Arguments to pass to the callbacks.
     */
    public function publish(string $event, ...$args): void
    {
        if (! isset($this->subscribers[ $event ])) {
            return;
        }

        foreach ($this->subscribers[ $event ] as $callback) {
            call_user_func($callback, ...$args);
        }
    }

    /**
     * Unsubscribe a specific callback from an event.
     *
     * @param string   $event The event name.
     * @param callable $callback The callback to remove.
     */
    public function unsubscribe(string $event, callable $callback): void
    {
        if (! isset($this->subscribers[ $event ])) {
            return;
        }

        $this->subscribers[ $event ] = array_filter(
            $this->subscribers[ $event ],
            fn ($subscriber): bool => $subscriber !== $callback
        );

        if (empty($this->subscribers[ $event ])) {
            unset($this->subscribers[ $event ]);
        }
    }
}
