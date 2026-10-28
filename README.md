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

Server files (`server.properties`, `eula.txt`, `server-icon.png`) are read and written through Wings, like
Pelican's own file manager. Outside services, only when you use the feature:

| Service | When | What is sent |
| --- | --- | --- |
| `api.modrinth.com`, `cdn.modrinth.com` | "Use the modpack's icon" on a Modrinth modpack | the modpack's project id |
| `api.feed-the-beast.com` and its image host | "Use the modpack's icon" on an FTB modpack | the modpack's id |

## Development

- `bash tests/run.sh` runs the checks that need no panel, including `tests/hub-check.py`.
- `pint` formats the code (Laravel preset).
- `python3 scripts/build-package.py` builds the zip for the Hub.

## License

Source available, see [LICENSE.md](LICENSE.md). Copyright (c) 2026 Jeroen (jerajerajerajera).
