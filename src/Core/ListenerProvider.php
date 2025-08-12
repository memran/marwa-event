<?php

namespace Marwa\Event\Core;

use Marwa\Event\Resolver\ListenerResolver;
use Psr\EventDispatcher\ListenerProviderInterface;
use SplPriorityQueue;

/**
 * High-performance listener provider with:
 * - Per-event-class priority queues
 * - Interface/parent-type matching with caching
 * - O(1) lookups for known types
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, SplPriorityQueue> */
    private array $queues = [];

    /** @var array<int, array{event:string, listener:callable|string|array, priority:int}> */
    private array $registry = [];

    /** @var array<string, string[]> Cached hierarchy for event FQCN => [class chain + interfaces] */
    private array $typeCache = [];

    private int $autoId = 0;

    public function __construct(
        private readonly ListenerResolver $resolver
    ) {}

    /**
     * Register a listener for an event FQCN.
     * Returns a numeric id which can be used to remove the listener later.
     */
    public function addListener(string $eventClass, callable|string|array $listener, int $priority = 0): int
    {
        $id = ++$this->autoId;
        $this->registry[$id] = ['event' => $eventClass, 'listener' => $listener, 'priority' => $priority];

        if (!isset($this->queues[$eventClass])) {
            $this->queues[$eventClass] = $this->newQueue();
        }

        $this->queues[$eventClass]->insert($listener, $priority);
        return $id;
    }

    /** Remove a listener by id. */
    public function remove(int $id): bool
    {
        if (!isset($this->registry[$id])) {
            return false;
        }
        $entry = $this->registry[$id];
        unset($this->registry[$id]);

        // Rebuild the queue for that event to keep SplPriorityQueue lean
        $eventClass = $entry['event'];
        if (isset($this->queues[$eventClass])) {
            $this->queues[$eventClass] = $this->newQueue();
            foreach ($this->registry as $r) {
                if ($r['event'] === $eventClass) {
                    $this->queues[$eventClass]->insert($r['listener'], $r['priority']);
                }
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getListenersForEvent(object $event): iterable
    {
        $types = $this->expandTypes($event);

        // Fast path: gather listeners across all matching types
        $listeners = [];
        foreach ($types as $type) {
            if (!isset($this->queues[$type])) {
                continue;
            }
            // Clone queue to iterate without disturbing original
            $q = clone $this->queues[$type];
            $q->setExtractFlags(SplPriorityQueue::EXTR_DATA);
            foreach ($q as $listener) {
                // Resolve lazily right before dispatch
                $listeners[] = $this->resolver->resolve($listener);
            }
        }

        return $listeners;
    }

    private function newQueue(): SplPriorityQueue
    {
        // Max-heap (default) works well; higher priority first.
        $q = new SplPriorityQueue();
        $q->setExtractFlags(SplPriorityQueue::EXTR_BOTH); // keep data + priority during internal ops
        return $q;
    }

    /**
     * Build + cache list of relevant types for an event:
     * [class, parents..., interfaces...]
     *
     * Cached per event FQCN to avoid repeated class graph work.
     */
    private function expandTypes(object $event): array
    {
        $class = $event::class;

        if (isset($this->typeCache[$class])) {
            return $this->typeCache[$class];
        }

        // Build class chain
        $types = [$class];
        $parent = get_parent_class($class);
        while ($parent) {
            $types[] = $parent;
            $parent = get_parent_class($parent);
        }

        // Add interfaces (unique)
        $ifaces = class_implements($class);
        if ($ifaces) {
            foreach ($ifaces as $iface) {
                $types[] = $iface;
            }
        }

        // De-duplicate while preserving order
        $types = array_values(array_unique($types));

        return $this->typeCache[$class] = $types;
    }
}
