<?php

use PHPUnit\Framework\TestCase;
use Marwa\Event\Resolver\ListenerResolver;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Bus\EventBus;
use Marwa\Event\Contracts\StoppableEvent;

final class EventDispatchTest extends TestCase
{
    public function testDispatchRunsListenersInPriorityOrder(): void
    {
        $resolver   = new ListenerResolver();
        $provider   = new ListenerProvider($resolver);
        $dispatcher = new EventDispatcher($provider);
        $bus        = new EventBus($provider, $dispatcher);

        $seq = [];

        $bus->listen(MyEvent::class, function () use (&$seq) {
            $seq[] = 2;
        }, 0);
        $bus->listen(MyEvent::class, function () use (&$seq) {
            $seq[] = 1;
        }, 100);

        $bus->dispatch(new MyEvent());

        $this->assertSame([1, 2], $seq);
    }

    public function testStopPropagation(): void
    {
        $resolver   = new ListenerResolver();
        $provider   = new ListenerProvider($resolver);
        $dispatcher = new EventDispatcher($provider);
        $bus        = new EventBus($provider, $dispatcher);

        $bus->listen(MyStopEvent::class, function (MyStopEvent $e) {
            $e->stopPropagation();
        }, 100);

        $hit = false;
        $bus->listen(MyStopEvent::class, function () use (&$hit) {
            $hit = true;
        }, 0);

        $bus->dispatch(new MyStopEvent());
        $this->assertFalse($hit, 'Second listener should not run after stopPropagation.');
    }
}

final class MyEvent {}
final class MyStopEvent extends StoppableEvent {}
