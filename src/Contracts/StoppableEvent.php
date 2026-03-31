<?php

declare(strict_types=1);

namespace Marwa\Event\Contracts;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base Stoppable Event: toggle propagation for any event safely.
 */
abstract class StoppableEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /** Stop further listeners from running. */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
