# Changelog

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
