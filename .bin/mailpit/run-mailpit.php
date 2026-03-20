<?php

$isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

$binary = __DIR__.DIRECTORY_SEPARATOR.
    ($isWin ? 'mailpit.exe' : 'mailpit');

if (! file_exists($binary)) {
    fwrite(STDERR, "Mailpit binary not found. Run updater first.\n");
    exit(1);
}

$cmd = '"'.$binary.'" --smtp-auth-accept-any --smtp-auth-allow-insecure --use-message-dates --tags-username';

passthru($cmd);
