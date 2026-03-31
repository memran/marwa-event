<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Marwa\Event\Bus\EventBus;
use Marwa\Event\Contracts\StoppableEvent;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Resolver\ListenerResolver;

final class UserRegistered extends StoppableEvent
{
    public function __construct(public string $email) {}
}

final class AuditRegistration
{
    public function __invoke(UserRegistered $event): void
    {
        echo "Audit log for {$event->email}" . PHP_EOL;
    }
}

$resolver = new ListenerResolver();
$provider = new ListenerProvider($resolver);
$dispatcher = new EventDispatcher($provider);
$bus = new EventBus($provider, $dispatcher);

$bus->listen(UserRegistered::class, static function (UserRegistered $event): void {
    echo "Welcome {$event->email}" . PHP_EOL;
}, 100);

$bus->listen(UserRegistered::class, AuditRegistration::class, 10);

$bus->dispatch(new UserRegistered('user@example.com'));
