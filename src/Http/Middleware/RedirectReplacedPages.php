<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Simplify\Toolkit\Filament\Server\Pages\ServerSettings;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pelican's Settings and Startup pages, and Toolkit's former Minecraft page, send their visitors to Toolkit's Settings
 * page, at the right section, so bookmarks and links keep working. Livewire's own requests pass untouched.
 */
final class RedirectReplacedPages
{
    /** Route name ending => section of the Settings page. */
    public const PAGES = [
        '.pages.settings' => 'settings-server',
        '.pages.startup' => 'settings-startup',
        '.pages.minecraft' => 'settings-minecraft',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $name = (string) $request->route()?->getName();
        if ($request->isMethod('GET') && str_starts_with($name, 'filament.server.') && ServerSettings::canAccess()) {
            foreach (self::PAGES as $ending => $section) {
                if (str_ends_with($name, $ending)) {
                    return redirect()->to(ServerSettings::getUrl(panel: 'server', tenant: Filament::getTenant()).'#'.$section);
                }
            }
        }

        return $next($request);
    }
}
