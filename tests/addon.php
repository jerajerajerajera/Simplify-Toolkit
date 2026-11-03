<?php

// ToolkitAddon against Simplify's add-on contract, without Simplify or a panel.
declare(strict_types=1);

// Stand-in for Pelican's user model, only for the type of connections().
eval('namespace App\\Models; class User {}');
require __DIR__.'/fixtures/Addon.php';
require __DIR__.'/fixtures/Button.php';
require __DIR__.'/../src/ToolkitAddon.php';

function __(string $key): string
{
    return $key;
}

use App\Models\User;
use Simplify\Toolkit\ToolkitAddon;

$addon = new ToolkitAddon;
$failures = [];
if ($addon->id() !== 'simplify-toolkit') {
    $failures[] = 'the id is the plugin id';
}
if ($addon->description() !== 'simplify-toolkit::toolkit.addon.description') {
    $failures[] = 'the description is translated';
}
// Simplify's Connections page lists the CurseForge row (add-on API 6); without it the page shows only Steam (TK-B1).
if (! method_exists($addon, 'connections') || $addon->connections(new User) !== ['Simplify\\Toolkit\\Livewire\\CurseForgeConnection']) {
    $failures[] = 'the CurseForge row is on the Connections page';
}
if ($failures) {
    echo "FAIL add-on:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS add-on contract\n";
