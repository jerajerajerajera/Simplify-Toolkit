<?php

// Reading a start log: which watched plugins failed, and the line that says why.
declare(strict_types=1);

require __DIR__.'/../src/Services/Sources/Versions.php';
require __DIR__.'/../src/Services/StartCheck.php';

use Simplify\Toolkit\Services\StartCheck;

$log = <<<'LOG'
[12:00:01 INFO]: Starting minecraft server version 1.21.4
[12:00:02 ERROR]: Could not load 'plugins/BrokenThing-1.0.jar' in folder 'plugins'
org.bukkit.plugin.InvalidPluginException: Unsupported API version 1.22
	at org.bukkit.plugin.java.JavaPluginLoader.loadPlugin(JavaPluginLoader.java:10)
[12:00:03 INFO]: [Chunky] Enabling Chunky v1.4.10
[12:00:04 ERROR]: Error occurred while enabling WorldGuard v7.0.12 (Is it up to date?)
java.lang.NoClassDefFoundError: com/sk89q/worldedit/WorldEdit
[12:00:05 WARN]: [Essentials] Something minor
[12:00:06 INFO]: Done (5.1s)! For help, type "help"
LOG;
$watched = [
    1 => ['filename' => 'BrokenThing-1.0.jar', 'title' => 'Broken Thing'],
    2 => ['filename' => 'worldguard-bukkit-7.0.12.jar', 'title' => 'WorldGuard'],
    3 => ['filename' => 'Chunky-Bukkit-1.4.10.jar', 'title' => 'Chunky'],
    4 => ['filename' => 'EssentialsX-2.21.jar', 'title' => 'EssentialsX'],
];
$failed = StartCheck::failures($log, $watched);

$failures = [];
if (array_keys($failed) !== [1, 2]) {
    $failures[] = 'only the two broken plugins fail: '.json_encode(array_keys($failed));
}
if (($failed[1]['reason'] ?? '') !== "Could not load 'plugins/BrokenThing-1.0.jar' in folder 'plugins'") {
    $failures[] = 'the reason is the line without its time stamp: '.json_encode($failed[1]['reason'] ?? null);
}
if (! str_contains(implode("\n", $failed[2]['lines'] ?? []), 'NoClassDefFoundError')) {
    $failures[] = 'the lines include the cause';
}
if (StartCheck::failures("[12:00:01 INFO]: Done (1s)!\n", $watched) !== []) {
    $failures[] = 'a clean start has no failures';
}
if ($failures) {
    echo "FAIL start check:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS start check\n";
