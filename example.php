<?php

require_once 'vendor/autoload.php';

use Marwa\Event\Resolver\ListenerResolver;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Bus\EventBus;
use Marwa\Event\Contracts\StoppableEvent;

// 1) Define events
final class UserRegistered extends StoppableEvent
{
    public function __construct(public string $email) {}
}

final class AuditRegistration
{
    public function __invoke(UserRegistered $user)
    {

        $numbers = range(1, 100);
        var_dump($numbers);
        //echo "Welcome to " . $user->email . "\n";
    }
}
// Bootstrap (container-less example)
$resolver   = new ListenerResolver();
$provider   = new ListenerProvider($resolver);
$dispatcher = new EventDispatcher($provider);
$bus        = new EventBus($provider, $dispatcher);

// Register listeners
$bus->listen(UserRegistered::class, function (UserRegistered $event) {
    echo "Welcome {$event->email}";
}, 100);

// Dispatch event
$bus->dispatch(new UserRegistered('user@example.com'));
