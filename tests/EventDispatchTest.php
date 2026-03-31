<?php

declare(strict_types=1);

use Marwa\Event\Bus\EventBus;
use Marwa\Event\Contracts\StoppableEvent;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Resolver\ListenerResolver;
use PHPUnit\Framework\TestCase;

final class EventDispatchTest extends TestCase
{
    public function testDispatchRunsListenersInPriorityOrder(): void
    {
        $bus = $this->makeBus();
        $seq = [];

        $bus->listen(MyEvent::class, static function () use (&$seq): void {
            $seq[] = 2;
        }, 0);
        $bus->listen(MyEvent::class, static function () use (&$seq): void {
            $seq[] = 1;
        }, 100);

        $bus->dispatch(new MyEvent());

        $this->assertSame([1, 2], $seq);
    }

    public function testStopPropagation(): void
    {
        $bus = $this->makeBus();

        $bus->listen(MyStopEvent::class, static function (MyStopEvent $event): void {
            $event->stopPropagation();
        }, 100);

        $hit = false;
        $bus->listen(MyStopEvent::class, static function () use (&$hit): void {
            $hit = true;
        }, 0);

        $bus->dispatch(new MyStopEvent());

        $this->assertFalse($hit, 'Second listener should not run after stopPropagation.');
    }

    public function testDispatchMergesPriorityAcrossClassParentsAndInterfaces(): void
    {
        $bus = $this->makeBus();
        $seq = [];

        $bus->listen(DispatchMarker::class, static function () use (&$seq): void {
            $seq[] = 'interface-high';
        }, 200);
        $bus->listen(DispatchBaseEvent::class, static function () use (&$seq): void {
            $seq[] = 'parent-mid';
        }, 100);
        $bus->listen(DispatchChildEvent::class, static function () use (&$seq): void {
            $seq[] = 'child-low';
        }, 0);

        $bus->dispatch(new DispatchChildEvent());

        $this->assertSame(['interface-high', 'parent-mid', 'child-low'], $seq);
    }

    public function testDispatchPreservesRegistrationOrderWhenPrioritiesMatch(): void
    {
        $bus = $this->makeBus();
        $seq = [];

        $bus->listen(MyEvent::class, static function () use (&$seq): void {
            $seq[] = 'first';
        }, 10);
        $bus->listen(MyEvent::class, static function () use (&$seq): void {
            $seq[] = 'second';
        }, 10);

        $bus->dispatch(new MyEvent());

        $this->assertSame(['first', 'second'], $seq);
    }

    public function testForgetRemovesRegisteredListener(): void
    {
        $bus = $this->makeBus();
        $seq = [];

        $listenerId = $bus->listen(MyEvent::class, static function () use (&$seq): void {
            $seq[] = 'removed';
        });
        $bus->listen(MyEvent::class, static function () use (&$seq): void {
            $seq[] = 'active';
        }, 10);

        self::assertTrue($bus->forget($listenerId));

        $bus->dispatch(new MyEvent());

        $this->assertSame(['active'], $seq);
        self::assertFalse($bus->forget($listenerId));
    }

    private function makeBus(): EventBus
    {
        $resolver = new ListenerResolver();
        $provider = new ListenerProvider($resolver);
        $dispatcher = new EventDispatcher($provider);

        return new EventBus($provider, $dispatcher);
    }
}

final class MyEvent {}

final class MyStopEvent extends StoppableEvent {}

interface DispatchMarker {}

class DispatchBaseEvent implements DispatchMarker {}

final class DispatchChildEvent extends DispatchBaseEvent {}
