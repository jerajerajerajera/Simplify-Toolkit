<?php

// ModsAddon against Simplify's add-on contract, without Simplify or a panel.
declare(strict_types=1);

require __DIR__.'/fixtures/Addon.php';
require __DIR__.'/fixtures/Button.php';
require __DIR__.'/../src/ModsAddon.php';

function __(string $key): string
{
    return $key;
}

use Simplify\Mods\ModsAddon;

$addon = new ModsAddon;
$failures = [];
if ($addon->id() !== 'simplify-mods') {
    $failures[] = 'the id is the plugin id';
}
if ($addon->description() !== 'simplify-mods::mods.addon.description') {
    $failures[] = 'the description is translated';
}
if ($failures) {
    echo "FAIL add-on:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS add-on contract\n";
