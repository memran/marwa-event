<?php

declare(strict_types=1);

$hasCoverageDriver = extension_loaded('xdebug') || extension_loaded('pcov') || PHP_SAPI === 'phpdbg';

if (!$hasCoverageDriver) {
    fwrite(
        STDERR,
        "Mutation testing requires Xdebug, PCOV, or phpdbg. Install a coverage-capable driver, then rerun composer mutate." . PHP_EOL
    );
    exit(1);
}

$command = 'vendor/bin/infection --threads=max --min-msi=80 --min-covered-msi=90';
passthru($command, $exitCode);

exit($exitCode);
