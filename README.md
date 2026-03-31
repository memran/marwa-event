# Marwa Event

[![CI](https://github.com/memran/marwa-event/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/memran/marwa-event/actions/workflows/ci.yml)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![PHPStan 2.x](https://img.shields.io/badge/PHPStan-2.x-2E3440)
![PHPUnit 10](https://img.shields.io/badge/PHPUnit-10-3C9CD7)
![Infection Ready](https://img.shields.io/badge/Infection-ready-6E40C9)
![License MIT](https://img.shields.io/badge/License-MIT-green.svg)

Lightweight PSR-14 event dispatching for PHP 8.2+ with predictable synchronous delivery, stable priority ordering, optional PSR-11 container integration, and a small framework-agnostic API.

## Requirements

- PHP `>=8.2`
- Composer

## Installation

```bash
composer require memran/marwa-event
```

For local development:

```bash
composer install
composer ci
```

## Features

- PSR-14 aligned dispatcher and listener provider
- Stable priority ordering, including ties and mixed parent/interface listeners
- Flexible listener definitions: callable, `"Class@method"`, `["Class", "method"]`, and invokable class strings
- Optional PSR-11 container resolution for listeners and subscriber class strings
- Stoppable events via `Marwa\Event\Contracts\StoppableEvent`
- Fail-fast validation for invalid listener and subscriber definitions
- PHPUnit, PHPStan 2.x, PHP-CS-Fixer, GitHub Actions CI, and Infection setup

## Quick Start

```php
use Marwa\Event\Bus\EventBus;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Resolver\ListenerResolver;

$resolver = new ListenerResolver();
$provider = new ListenerProvider($resolver);
$dispatcher = new EventDispatcher($provider);
$bus = new EventBus($provider, $dispatcher);

$bus->listen(UserRegistered::class, SendWelcomeMail::class, 100);
$bus->listen(UserRegistered::class, [AuditListener::class, 'handle'], 50);
$bus->listen(UserRegistered::class, static function (UserRegistered $event): void {
    // metrics or notifications
}, 10);

$bus->dispatch(new UserRegistered('user@example.com'));
```

## Examples

### Define a Stoppable Event

```php
use Marwa\Event\Contracts\StoppableEvent;

final class UserRegistered extends StoppableEvent
{
    public function __construct(public string $email) {}
}
```

### Register a Subscriber

```php
use Marwa\Event\Contracts\Subscriber;

final class UserSubscriber implements Subscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistered::class => [
                ['sendWelcome', 100],
                ['audit', 50],
            ],
        ];
    }

    public function sendWelcome(UserRegistered $event): void {}

    public function audit(UserRegistered $event): void {}
}

$bus->subscribe(new UserSubscriber());
```

### Remove a Listener

```php
$listenerId = $bus->listen(UserRegistered::class, SendWelcomeMail::class, 100);

$bus->forget($listenerId);
```

### Listen on an Interface or Parent Event Type

```php
interface DomainEvent {}

class BaseOrderEvent implements DomainEvent {}

final class OrderPlaced extends BaseOrderEvent {}

$bus->listen(DomainEvent::class, LogDomainEvent::class, 100);
$bus->listen(BaseOrderEvent::class, UpdateReadModel::class, 50);
$bus->listen(OrderPlaced::class, SendOrderEmail::class, 10);

$bus->dispatch(new OrderPlaced());
```

### Use Container-Aware Resolution

```php
use Marwa\Event\Bus\EventBus;
use Marwa\Event\Core\EventDispatcher;
use Marwa\Event\Core\ListenerProvider;
use Marwa\Event\Resolver\ListenerResolver;

$resolver = new ListenerResolver($container);
$provider = new ListenerProvider($resolver);
$dispatcher = new EventDispatcher($provider);
$bus = new EventBus($provider, $dispatcher, $container);

$bus->listen(UserRegistered::class, SendWelcomeMail::class);
$bus->subscribe(UserSubscriber::class);
```

When a PSR-11 container is provided, listener and subscriber class strings are resolved from the container first.

### Swallow Listener Exceptions Explicitly

```php
$dispatcher = new EventDispatcher($provider, swallowExceptions: true);
$bus = new EventBus($provider, $dispatcher);
```

The default remains `false`, which is usually the safer production choice.

## Project Layout

```text
src/
  Bus/         Facade-style API
  Contracts/   Shared interfaces and base event types
  Core/        Dispatcher and listener provider
  Resolver/    Listener notation resolution
tests/         PHPUnit test suite
example.php    Minimal runnable example
```

## Development Workflow

```bash
composer test          # PHPUnit
composer test:coverage # Coverage output; requires Xdebug or PCOV
composer analyse       # PHPStan 2.x
composer mutate        # Infection mutation testing; requires Xdebug, PCOV, or phpdbg
composer lint          # PHP-CS-Fixer dry run
composer fix           # PHP-CS-Fixer apply fixes
composer ci            # test + analyse + lint
```

## Notes

- Higher priority values run first.
- Equal priorities keep registration order.
- Invalid listeners and subscribers throw `InvalidArgumentException`.
- Exceptions bubble by default unless `EventDispatcher` is explicitly configured otherwise.

## Quality and Release

The repository includes PHPUnit 10, PHPStan 2.x, PHP-CS-Fixer, GitHub Actions CI, and Infection configuration. Run `composer ci` before opening a PR or cutting a release.

Mutation testing is available through Infection. Run `composer mutate` after `composer test` when you want to measure how well the suite detects behavioral regressions. It requires Xdebug, PCOV, or `phpdbg` so Infection can collect coverage for the initial test pass.

## Contributing

See [AGENTS.md](AGENTS.md) for repository-specific contributor guidance.
