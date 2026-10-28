# Simplify Mods

A [Pelican](https://pelican.dev) plugin that makes Minecraft servers easier to run:

- Add plugins (Paper, Purpur, Spigot, Velocity) and mods (Fabric, Forge, NeoForge, Quilt) from Modrinth, Hangar,
  SpigotMC and CurseForge, see which version fits your server, and switch them off or remove them.
- Change the MOTD, the main server settings and the server icon without editing `server.properties`.

It works on its own, and shows up inside [Simplify](https://github.com/jerajerajerajera/Simplify) when both are installed.

Simplify Mods is being built for version 1.0. This list grows with each change.

## Install

Install Simplify Mods from the [Pelican Hub](https://hub.pelican.dev). Updates come from the Hub only.

## What it sends where

Server files (`server.properties`, `eula.txt`, `server-icon.png`, the `plugins` and `mods` folders) are read and
written through Wings, like Pelican's own file manager. Outside services, only when you use the feature:

| Service | When | What is sent |
| --- | --- | --- |
| `api.modrinth.com` | Plugins or Mods page: searching, installing; "Use the modpack's icon"; the version of a Modrinth modpack | the search text, loader and Minecraft version; project and version ids |
| `hangar.papermc.io` | Plugins page on Paper, Purpur, Folia, Velocity and Waterfall | the search text, platform and version; the plugin's name |
| `api.spiget.org` (SpigotMC) | Plugins page on Paper, Spigot, Bukkit and BungeeCord | the search text; the resource id |
| `api.curseforge.com` | Plugins or Mods page, only when a CurseForge API key is set | the search text, loader and version; the project id; the key |
| `api.feed-the-beast.com` and its image host | "Use the modpack's icon" on an FTB modpack | the modpack's id |

Installing a plugin or mod asks Wings to download the file itself, only from `cdn.modrinth.com`,
`hangarcdn.papermc.io`, `api.spiget.org`, `edge.forgecdn.net` or `mediafilez.forgecdn.net`. A plugin that the site
only offers as a link to another website is not installed; the page links to the site instead.
Replaced and removed files are moved to `.simplify-mods-backups` on the server, never deleted. After installing a
plugin, the Plugins page reads `logs/latest.log` of the next start and switches the plugin off (`.jar.disabled`) when
it failed to load.

CurseForge only answers apps with an API key. An admin can save one for the whole panel under Advanced → Simplify
Mods, and every user can save their own from the Plugins or Mods page; a user's own key goes first. Keys are tested
with CurseForge before they are saved, stored encrypted with the panel's `APP_KEY`, and never shown again.

## Development

- `bash tests/run.sh` runs the checks that need no panel, including `tests/hub-check.py`.
- `pint` formats the code (Laravel preset).
- `python3 scripts/build-package.py` builds the zip for the Hub.

## License

Source available, see [LICENSE.md](LICENSE.md). Copyright (c) 2026 Jeroen (jerajerajerajera).
