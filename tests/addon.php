<?php

// ToolkitAddon against Simplify's add-on contract, without Simplify or a panel.
declare(strict_types=1);

require __DIR__.'/fixtures/Addon.php';
require __DIR__.'/fixtures/Button.php';
require __DIR__.'/../src/ToolkitAddon.php';

function __(string $key): string
{
    return $key;
}

use Simplify\Toolkit\ToolkitAddon;

$addon = new ToolkitAddon;
$failures = [];
if ($addon->id() !== 'simplify-toolkit') {
    $failures[] = 'the id is the plugin id';
}
if ($addon->description() !== 'simplify-toolkit::toolkit.addon.description') {
    $failures[] = 'the description is translated';
}
if ($failures) {
    echo "FAIL add-on:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS add-on contract\n";
