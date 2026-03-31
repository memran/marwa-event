# Marwa Event

PSR-14 compliant event dispatching for PHP applications that want a small, framework-agnostic library with simple listener registration and predictable synchronous execution.

## Requirements

- PHP `>=8.1`
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

$bus->dispatch(new UserRegistered('user@example.com'));
```

## Features

- PSR-14 `EventDispatcherInterface` and `ListenerProviderInterface` aligned
- Listener formats: callable, `"Class@method"`, `["Class", "method"]`, and invokable class strings
- Parent class and interface listener matching with priority ordering
- Optional PSR-11 container support for listener instantiation
- Stoppable events via `Marwa\Event\Contracts\StoppableEvent`

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

Common Composer scripts:

```bash
composer test          # PHPUnit
composer test:coverage # PHPUnit with Clover output (requires Xdebug or PCOV)
composer analyse       # PHPStan
composer lint          # PHP-CS-Fixer dry run
composer fix           # PHP-CS-Fixer apply fixes
composer ci            # test + analyse + lint
```

## Usage Notes

- Higher priority values run first.
- Equal priorities keep registration order.
- Invalid listener or subscriber definitions fail fast with `InvalidArgumentException`.
- `EventDispatcher` can optionally swallow listener exceptions, but the default is to surface them.

## Testing and Static Analysis

The repository includes PHPUnit 10, PHPStan, PHP-CS-Fixer, and a GitHub Actions workflow that runs validation, tests, static analysis, linting, and coverage.

## Security and Production Notes

- No global helpers or framework coupling are required.
- Listener class strings are instantiated directly unless a PSR-11 container is supplied.
- Keep listener classes explicit and trusted; this library does not load arbitrary code or deserialize payloads.
- Review public API changes in `README.md`, `example.php`, and tests together before release.

## Contributing

See [AGENTS.md](AGENTS.md) for repository-specific contributor guidance.

## Release Notes

- `main` is expected to stay releasable.
- Run `composer ci` before tagging or publishing.
