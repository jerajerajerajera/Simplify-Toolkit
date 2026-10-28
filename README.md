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

Nothing yet. Each outside service is listed here when it is added.

## Development

- `bash tests/run.sh` runs the checks that need no panel, including `tests/hub-check.py`.
- `pint` formats the code (Laravel preset).
- `python3 scripts/build-package.py` builds the zip for the Hub.

## License

Source available, see [LICENSE.md](LICENSE.md). Copyright (c) 2026 Jeroen (jerajerajerajera).
