<?php

// Which backups are Toolkit's own, and how many it may make and keep, without a panel (TK-B3).
declare(strict_types=1);

require __DIR__.'/../src/Services/AutomaticBackups.php';

use Simplify\Toolkit\Services\AutomaticBackups;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

// Its own backups are found in every language, not only in the panel's current one: after a change of APP_LOCALE
// the old ones are still cleaned up.
foreach (glob(__DIR__.'/../lang/*/backups.php') as $file) {
    $name = (string) ((require $file)['name'] ?? '');
    check(AutomaticBackups::isOwn($name.' · 2026-11-04 20:00'), basename(dirname($file)).': "'.$name.'" is an automatic backup');
}
check(AutomaticBackups::isOwn('Automatic · 2026-11-04 20:00') && AutomaticBackups::isOwn('Automatisch · 2026-11-04 20:00'), 'English and Dutch names');
check(! AutomaticBackups::isOwn('My world before the update') && ! AutomaticBackups::isOwn('Automatic · before the update')
    && ! AutomaticBackups::isOwn('Automatic'), 'a backup a user named is never one of its own');

// The server's backup limit counts: the automatic ones keep to the room the others leave, at least one.
check(AutomaticBackups::keep(7, 3, 0) === 3, 'a plan of 7 on a limit of 3 keeps 3');
check(AutomaticBackups::keep(2, 10, 1) === 2, 'room enough: the plan');
check(AutomaticBackups::keep(5, 3, 2) === 1 && AutomaticBackups::keep(5, 3, 3) === 1, 'others fill the limit: one recovery point');

// A new backup gets one slot more only when an own backup goes once it is done; otherwise Pelican's limit.
check(AutomaticBackups::slot(3, 1, 1, 0) === 3, 'room left: the limit itself');
check(AutomaticBackups::slot(3, 3, 3, 0) === 4, 'full with its own: one more until the oldest goes');
check(AutomaticBackups::slot(3, 3, 1, 2) === 4, 'full with one of its own: one more until it goes');
check(AutomaticBackups::slot(3, 3, 0, 3) === null, 'full with the user\'s own backups: none');
check(AutomaticBackups::slot(3, 4, 1, 3) === null, 'the user\'s own backups fill the limit: none');

if ($failures) {
    echo "FAIL automatic backups:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS automatic backups: own names in every language, the server's backup limit\n";
