<?php
declare(strict_types=1);

namespace Unwinded\Core;

/**
 * Simple synchronous event bus for domain events.
 * Events trigger listeners immediately in the same request.
 * Async side-effects (emails, queue entries) go to the email_queue table
 * and are drained by cron — not dispatched via long-running listeners.
 */
class EventBus
{
    private array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, mixed $payload = null): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }
}
