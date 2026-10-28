<?php

declare(strict_types=1);

namespace Simplify\Mods\Filament\Components;

use Closure;
use Filament\Forms\Components\Field;

/**
 * The MOTD as players see it in their server list, edited in place with colours and styles. The state is the
 * MOTD text with § codes, as server.properties holds it.
 */
final class MotdEditor extends Field
{
    protected string $view = 'simplify-mods::components.motd-editor';

    private string|Closure $serverName = '';

    private string|Closure|null $iconUrl = null;

    public function serverName(string|Closure $name): self
    {
        $this->serverName = $name;

        return $this;
    }

    public function iconUrl(string|Closure|null $url): self
    {
        $this->iconUrl = $url;

        return $this;
    }

    public function getServerName(): string
    {
        return (string) $this->evaluate($this->serverName);
    }

    public function getIconUrl(): string
    {
        return (string) $this->evaluate($this->iconUrl);
    }
}
