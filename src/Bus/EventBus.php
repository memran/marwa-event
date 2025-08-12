<?php

namespace Marwa\Event\Bus;

use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Contracts\Subscriber;

/**
 * A thin, fluent facade-like API resembling Laravel's Event facade.
 * Compose this in your container and bind as a singleton.
 */
final class EventBus
{
    public function __construct(
        private readonly ListenerProvider $provider,
        private readonly EventDispatcher $dispatcher
    ) {}

    /** Register a listener. */
    public function listen(string $eventClass, callable|string|array $listener, int $priority = 0): int
    {
        return $this->provider->addListener($eventClass, $listener, $priority);
    }

    /** Remove a listener by id. */
    public function forget(int $id): bool
    {
        return $this->provider->remove($id);
    }

    /** Register a subscriber object or FQCN. */
    public function subscribe(Subscriber|string $subscriber): void
    {
        $instance = is_string($subscriber) ? new $subscriber() : $subscriber;
        $map = $instance::getSubscribedEvents();

        foreach ($map as $eventClass => $definition) {
            if (is_string($definition)) {
                $this->listen($eventClass, [$instance, $definition]);
                continue;
            }

            // Single handler with priority or array of handlers
            if (is_array($definition) && isset($definition[0]) && is_string($definition[0])) {
                [$method, $priority] = [$definition[0], $definition[1] ?? 0];
                $this->listen($eventClass, [$instance, $method], (int)$priority);
                continue;
            }

            // Multiple handlers for same event
            if (is_array($definition)) {
                foreach ($definition as $entry) {
                    [$method, $priority] = [$entry[0], $entry[1] ?? 0];
                    $this->listen($eventClass, [$instance, $method], (int)$priority);
                }
            }
        }
    }

    /** Dispatch an event object. */
    public function dispatch(object $event): object
    {
        return $this->dispatcher->dispatch($event);
    }
}
