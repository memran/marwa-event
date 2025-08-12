# Marwa Event

**Blazing-fast, PSR‑14–compliant event dispatch library** with Laravel‑style ergonomics (no global helpers).  
Performance-first, container-friendly, and simple to test.

## Features

- ✅ **PSR‑14 compliant**: Uses `ListenerProviderInterface` and `EventDispatcherInterface`
- ⚡ **Fast**: Priority queues via `SplPriorityQueue`, no reflection on the hot path
- 🧠 **Smart listener resolution**: `callable`, `Class@method`, `['Class','method']`, or invokable class — lazy-resolved
- 🧩 **Container-aware**: Optional PSR‑11 container for listener instantiation
- 🧵 **Stoppable events**: Base `StoppableEvent` provided
- 🧪 **Testable**: Clean, SOLID design with PHPUnit examples

## Install

```bash
composer require memran/marwa-event
```

> For local development with this repo:
>
> ```bash
> composer install
> vendor/bin/phpunit
> ```

## Quick Start

```php
use Marwa\Event\Resolver\ListenerResolver;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Bus\EventBus;

// Bootstrap (no container)
$resolver   = new ListenerResolver();           // optionally pass a PSR-11 container
$provider   = new ListenerProvider($resolver);
$dispatcher = new EventDispatcher($provider);
$bus        = new EventBus($provider, $dispatcher);

// Event
final class UserRegistered extends Marwa\Event\Contracts\StoppableEvent {
    public function __construct(public string $email) {}
}

// Listeners
$bus->listen(UserRegistered::class, function (UserRegistered $e) {
    // send welcome
}, 100);

$bus->listen(UserRegistered::class, [App\Listeners\Audit::class, 'handle'], 0);
$bus->listen(UserRegistered::class, App\Listeners\NotifyAdmin::class); // invokable

// Dispatch
$bus->dispatch(new UserRegistered('user@example.com'));
```

## Subscribers

```php
use Marwa\Event\Contracts\Subscriber;

final class UserSubscriber implements Subscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => [
                ['welcome', 50],
                ['audit', 0],
            ],
        ];
    }

    public function welcome(UserRegistered $e): void {/* ... */}
    public function audit(UserRegistered $e): void {/* ... */}
}
```

Register:

```php
$bus->subscribe(UserSubscriber::class);
```

## Architecture

- `Contracts/StoppableEvent` — base class implementing `StoppableEventInterface` with `stopPropagation()`
- `Contracts/Subscriber` — map events to methods with optional priorities
- `Resolver/ListenerResolver` — converts listener notations into callables (container-aware)
- `Core/ListenerProvider` — per-event priority queues + cached type graph for parent/interfaces
- `Core/EventDispatcher` — minimal synchronous dispatcher, optional exception swallowing
- `Bus/EventBus` — thin facade-like API: `listen()`, `subscribe()`, `dispatch()`, `forget()`

## API

```php
int   EventBus::listen(string $eventClass, callable|string|array $listener, int $priority = 0)
bool  EventBus::forget(int $id)
void  EventBus::subscribe(Subscriber|string $subscriber)
object EventBus::dispatch(object $event)
```

### Listener formats

- `callable` — `function (MyEvent $e) {}`
- `"Class@method"`
- `["Class", "method"]`
- `"Class"` — invokable class

## Testing

```bash
vendor/bin/phpunit
```

### Example Test

```php
public function testPriorityOrder(): void
{
    $bus = $this->makeBus();
    $seq = [];

    $bus->listen(MyEvent::class, function () use (&$seq) { $seq[] = 2; }, 0);
    $bus->listen(MyEvent::class, function () use (&$seq) { $seq[] = 1; }, 100);

    $bus->dispatch(new MyEvent());

    $this->assertSame([1, 2], $seq);
}
```

## Production Notes

- High priority values run first (max-heap)
- All event type matches are cached after the first dispatch
- Use PSR‑11 container to enable dependency-injected listeners

## Versioning & Compatibility

- PHP `>= 8.1`
- PSR‑14, PSR‑11 (optional)

## License

MIT © Mohammad Emran.
