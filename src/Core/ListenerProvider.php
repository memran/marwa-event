<?php

declare(strict_types=1);

namespace Marwa\Event\Core;

use Marwa\Event\Resolver\ListenerResolver;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * @phpstan-type ListenerDefinition callable|string|array<int, mixed>
 * @phpstan-type ListenerEntry array{id: int, listener: ListenerDefinition, priority: int}
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, list<ListenerEntry>> */
    private array $listenersByEvent = [];

    /** @var array<int, array{event: string, priority: int}> */
    private array $registry = [];

    /** @var array<string, list<string>> Cached hierarchy for event FQCN => [class chain + interfaces] */
    private array $typeCache = [];

    private int $autoId = 0;

    public function __construct(
        private readonly ListenerResolver $resolver
    ) {}

    /**
     * Register a listener for an event FQCN.
     * Returns a numeric id which can be used to remove the listener later.
     *
     * @param ListenerDefinition $listener
     */
    public function addListener(string $eventClass, callable|string|array $listener, int $priority = 0): int
    {
        $id = ++$this->autoId;
        $entry = ['id' => $id, 'listener' => $listener, 'priority' => $priority];

        $this->registry[$id] = ['event' => $eventClass, 'priority' => $priority];
        $this->listenersByEvent[$eventClass][] = $entry;
        $this->sortEntries($this->listenersByEvent[$eventClass]);

        return $id;
    }

    /** Remove a listener by id. */
    public function remove(int $id): bool
    {
        if (!isset($this->registry[$id])) {
            return false;
        }

        $eventClass = $this->registry[$id]['event'];
        unset($this->registry[$id]);

        if (!isset($this->listenersByEvent[$eventClass])) {
            return true;
        }

        $this->listenersByEvent[$eventClass] = array_values(
            array_filter(
                $this->listenersByEvent[$eventClass],
                static fn (array $entry): bool => $entry['id'] !== $id
            )
        );

        if ($this->listenersByEvent[$eventClass] === []) {
            unset($this->listenersByEvent[$eventClass]);
        }

        return true;
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $matches = [];

        foreach ($this->expandTypes($event) as $type) {
            foreach ($this->listenersByEvent[$type] ?? [] as $entry) {
                $matches[] = $entry;
            }
        }

        $this->sortEntries($matches);

        foreach ($matches as $match) {
            yield $this->resolver->resolve($match['listener']);
        }
    }

    /**
     * Build + cache list of relevant types for an event:
     * [class, parents..., interfaces...]
     *
     * @return list<string>
     */
    private function expandTypes(object $event): array
    {
        $class = $event::class;

        if (isset($this->typeCache[$class])) {
            return $this->typeCache[$class];
        }

        $types = [$class];
        $parent = get_parent_class($class);
        while ($parent !== false) {
            $types[] = $parent;
            $parent = get_parent_class($parent);
        }

        foreach (class_implements($class) ?: [] as $interface) {
            $types[] = $interface;
        }

        return $this->typeCache[$class] = array_values(array_unique($types));
    }

    /**
     * @param list<ListenerEntry> $entries
     */
    private function sortEntries(array &$entries): void
    {
        usort(
            $entries,
            static fn (array $left, array $right): int => [$right['priority'], $left['id']] <=> [$left['priority'], $right['id']]
        );
    }
}
