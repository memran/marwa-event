<?php

declare(strict_types=1);

use Marwa\Event\Resolver\ListenerResolver;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ListenerResolverTest extends TestCase
{
    public function testResolveSupportsInvokableClassAndMethodNotation(): void
    {
        $resolver = new ListenerResolver();

        $invokable = $resolver->resolve(ResolverInvokableListener::class);
        $methodCallable = $resolver->resolve(ResolverMethodListener::class . '@handle');
        $arrayCallable = $resolver->resolve([new ResolverArrayListener(), 'handle']);

        $event = new ResolverEvent();

        $invokable($event);
        $methodCallable($event);
        $arrayCallable($event);

        $this->assertSame(['invokable', 'method', 'array'], $event->calls);
    }

    public function testResolveUsesContainerWhenAvailable(): void
    {
        $container = new ResolverContainer();
        $resolver = new ListenerResolver($container);
        $callable = $resolver->resolve(ContainerBackedListener::class);

        $event = new ResolverEvent();
        $callable($event);

        $this->assertSame(['container'], $event->calls);
    }

    public function testResolveRejectsMissingMethod(): void
    {
        $resolver = new ListenerResolver();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Listener 'ResolverMethodListener@missing' is not callable.");

        $resolver->resolve(ResolverMethodListener::class . '@missing');
    }

    public function testResolveRejectsMissingClass(): void
    {
        $resolver = new ListenerResolver();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Listener class 'UnknownListener' does not exist.");

        $resolver->resolve('UnknownListener');
    }

    public function testResolveRejectsNonObjectContainerEntry(): void
    {
        $resolver = new ListenerResolver(new InvalidContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Container entry 'ContainerBackedListener' must resolve to an object.");

        $resolver->resolve(ContainerBackedListener::class);
    }
}

final class ResolverEvent
{
    /** @var list<string> */
    public array $calls = [];
}

final class ResolverInvokableListener
{
    public function __invoke(ResolverEvent $event): void
    {
        $event->calls[] = 'invokable';
    }
}

final class ResolverMethodListener
{
    public function handle(ResolverEvent $event): void
    {
        $event->calls[] = 'method';
    }
}

final class ResolverArrayListener
{
    public function handle(ResolverEvent $event): void
    {
        $event->calls[] = 'array';
    }
}

final class ContainerBackedListener
{
    public function __invoke(ResolverEvent $event): void
    {
        $event->calls[] = 'container';
    }
}

final class ResolverContainer implements ContainerInterface
{
    public function get(string $id): object
    {
        return new $id();
    }

    public function has(string $id): bool
    {
        return $id === ContainerBackedListener::class;
    }
}

final class InvalidContainer implements ContainerInterface
{
    public function get(string $id): string
    {
        return 'not-an-object';
    }

    public function has(string $id): bool
    {
        return $id === ContainerBackedListener::class;
    }
}
