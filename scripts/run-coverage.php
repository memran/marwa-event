<?php

declare(strict_types=1);

$hasCoverageDriver = extension_loaded('xdebug') || extension_loaded('pcov');

if (!$hasCoverageDriver) {
    fwrite(
        STDERR,
        "Coverage requires Xdebug or PCOV. Install a coverage driver, then rerun composer test:coverage." . PHP_EOL
    );
    exit(1);
}

$command = 'XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --coverage-clover build/logs/clover.xml';
passthru($command, $exitCode);

exit($exitCode);
