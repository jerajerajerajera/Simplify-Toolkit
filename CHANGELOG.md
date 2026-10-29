# Changelog

## Unreleased

- Toolkit moves to the end of the Simplify group in the admin menu, which is now in A-Z order.

## 1.1.0

- Simplify Mods is now Simplify Toolkit (id `simplify-toolkit`): the place for handy tools that are not in the
  Simplify core. It shows as "Toolkit" in Simplify's add-on list and in the Simplify group of the admin menu.
- Coming from Simplify Mods: install Simplify Toolkit first, then uninstall Simplify Mods. Installed plugins, chosen
  loaders and saved CurseForge keys carry over.
- With Simplify 1.1 or later, a running Minecraft server shows its players, MOTD, version and server icon on its
  card and page in Simplify. Without Simplify, or with Simplify 1.0, nothing changes.

## 1.0.2

- Mods now sits in the Simplify group of Pelican's admin menu, together with the other Simplify add-ons,
  in the same order everywhere. This works with or without the Simplify core.

## 1.0.1

- Shows as "Mods" in Simplify's add-on list and settings, like the other add-ons.

## 1.0.0

- First version as its own plugin. Updates come from the Pelican Hub only.
- Minecraft page on each Java Minecraft server: the MOTD with colours as players see it, game mode, difficulty,
  players, distances, PvP, hardcore, flight and whitelist, without editing server.properties. Other lines in the
  file stay as they were. Accept the Minecraft EULA from the same page.
- Server icon on the same page: upload any picture (the middle square becomes 64 × 64), use the modpack's own icon
  on Modrinth and FTB modpacks, or remove it.
- Plugins page on Paper, Purpur, Folia, Spigot, Velocity and other plugin servers, Mods page on Fabric, Forge,
  NeoForge and Quilt: search Modrinth, Hangar and SpigotMC at once, one card per plugin, and install the newest
  release made for the server's version. A release for another version can be installed after a warning. Switch
  installed plugins off and on, or remove them; replaced and removed files go to a backup folder. When the egg does
  not say which loader or version the server runs, the page asks once.
- CurseForge on the Plugins and Mods pages once a CurseForge API key is saved: by an admin for the whole panel, or by
  a user for themselves. Keys are tested before saving, stored encrypted and never shown again.
- Protection after a start: a plugin installed through Simplify Mods is checked on the server's next start. When the
  log says it failed to load, it is switched off so the start after that is clean, with the log line and the lines
  around it on the Plugins page.
- With Simplify: players online, software, modpack and plugins on the server page, the software on the server tile,
  and a Settings button in the add-on list. With Simplify Embed: software, version, modpack, players and MOTD in the
  public server list.
