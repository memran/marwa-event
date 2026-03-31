# Repository Guidelines

## Project Structure & Module Organization
`src/` contains the library code under the `Marwa\Event\` PSR-4 namespace. Key areas are `Bus/` for the facade-style API, `Core/` for dispatching and listener storage, `Resolver/` for lazy listener resolution, and `Contracts/` for shared interfaces and base event types. `tests/` holds PHPUnit coverage, currently centered on dispatch order and propagation behavior. `example.php` is a runnable usage sample, and `README.md` documents the public API.

## Build, Test, and Development Commands
Install dependencies with `composer install`. Run the test suite with `vendor/bin/phpunit`. Validate autoloading or try the package manually with `php example.php` after installing dependencies. There are no custom Composer scripts in `composer.json`, so prefer explicit commands when documenting or automating local workflows.

## Coding Style & Naming Conventions
Follow the existing PHP style: `declare` is not used, braces are K&R-style, indentation is 4 spaces, and types are declared wherever possible. Keep classes `final` unless extension is intentional. Use PascalCase for classes like `EventBus`, camelCase for methods like `getListenersForEvent()`, and place files to match the PSR-4 namespace exactly, for example `src/Core/EventDispatcher.php`. Match the current lightweight docblock style and avoid adding broad framework assumptions.

## Testing Guidelines
Add PHPUnit tests in `tests/` with file names ending in `Test.php` and descriptive methods such as `testStopPropagation()`. Cover listener priority, subscriber registration, stoppable events, and resolver edge cases when behavior changes. Run `vendor/bin/phpunit` before opening a PR; this repository does not currently define a separate coverage threshold, so prioritize meaningful behavioral coverage.

## Commit & Pull Request Guidelines
The current history uses Conventional Commits, for example `feat: initial PSR-14 event dispatcher (no globals)`. Continue with prefixes like `feat:`, `fix:`, and `test:`. PRs should explain the behavior change, note any API impact, include the test command you ran, and update `README.md` or `example.php` when public usage changes.

## Security & Configuration Tips
Target PHP `>=8.1` and keep dependencies aligned with `composer.json`. Avoid introducing global helpers or container-specific coupling; this package is intentionally PSR-11/PSR-14 oriented and should stay framework-agnostic.
