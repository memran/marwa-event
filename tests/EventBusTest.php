<?php

declare(strict_types=1);

use Marwa\Event\Bus\EventBus;
use Marwa\Event\Contracts\Subscriber;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Resolver\ListenerResolver;
use PHPUnit\Framework\TestCase;

final class EventBusTest extends TestCase
{
    public function testSubscribeRegistersSingleAndMultipleHandlers(): void
    {
        $bus = $this->makeBus();
        $subscriber = new DemoSubscriber();

        $bus->subscribe($subscriber);
        $bus->dispatch(new SubscriberEvent());

        $this->assertSame(['primary', 'secondary'], $subscriber->calls);
    }

    public function testSubscribeAcceptsSubscriberClassString(): void
    {
        $bus = $this->makeBus();
        $bus->subscribe(ClassStringSubscriber::class);

        $event = new ClassStringSubscriberEvent();
        $bus->dispatch($event);

        $this->assertTrue($event->handled);
    }

    public function testSubscribeRejectsUnknownSubscriberClass(): void
    {
        $bus = $this->makeBus();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Subscriber class 'MissingSubscriber' does not exist.");

        $bus->subscribe('MissingSubscriber');
    }

    public function testSubscribeRejectsInvalidDefinitions(): void
    {
        $bus = $this->makeBus();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Subscriber definition for 'SubscriberEvent' must contain method/priority pairs.");

        $bus->subscribe(new InvalidSubscriber());
    }

    private function makeBus(): EventBus
    {
        $resolver = new ListenerResolver();
        $provider = new ListenerProvider($resolver);
        $dispatcher = new EventDispatcher($provider);

        return new EventBus($provider, $dispatcher);
    }
}

final class SubscriberEvent {}

final class DemoSubscriber implements Subscriber
{
    /** @var list<string> */
    public array $calls = [];

    public static function getSubscribedEvents(): array
    {
        return [
            SubscriberEvent::class => [
                ['onPrimary', 100],
                ['onSecondary', 10],
            ],
        ];
    }

    public function onPrimary(): void
    {
        $this->calls[] = 'primary';
    }

    public function onSecondary(): void
    {
        $this->calls[] = 'secondary';
    }
}

final class ClassStringSubscriberEvent
{
    public bool $handled = false;
}

final class ClassStringSubscriber implements Subscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            ClassStringSubscriberEvent::class => 'handle',
        ];
    }

    public function handle(ClassStringSubscriberEvent $event): void
    {
        $event->handled = true;
    }
}

final class InvalidSubscriber implements Subscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            SubscriberEvent::class => [['']],
        ];
    }
}
