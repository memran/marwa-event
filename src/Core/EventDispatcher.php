<?php

namespace Marwa\Event\Core;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Throwable;

/**
 * Ultra-light synchronous dispatcher.
 * - No reflection
 * - No allocations in hot path beyond what's necessary
 * - Honors StoppableEventInterface
 * - Defensive try/catch optional (configurable)
 */
final class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly ListenerProvider $provider,
        private readonly bool $swallowExceptions = false
    ) {}

    public function dispatch(object $event): object
    {
        $listeners = $this->provider->getListenersForEvent($event);

        foreach ($listeners as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }

            if ($this->swallowExceptions) {
                try {
                    $listener($event);
                } catch (Throwable) {
                    // Intentionally swallow to keep the bus resilient.
                }
            } else {
                $listener($event);
            }
        }

        return $event;
    }
}
