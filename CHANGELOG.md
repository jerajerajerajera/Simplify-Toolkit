# Changelog

## 1.2.0

- Players page (back from PelicanSimplify 0.7.0): who is online, the whitelist, operators and bans. Type a name or click
  one in a list, then Whitelist, Unwhitelist, OP, DeOP, Kick, Ban or Unban; OP, kick and ban ask first. Works while the
  server runs.
- Automatic backups (back from 0.7.0): a backup every so many minutes per server, keeping a number of recovery points,
  skipped while the server has not run. Only these automatic backups are ever deleted, and a new one is made before the
  oldest goes. Root admins set them per server; Toolkit's settings pause them for the panel and set what new servers
  get. Plans from 0.7.0 are taken over.
- Restart reminder (back from 0.7.0): after a change on the Minecraft or Plugins page, "Restart needed" shows on the
  server's card and in the page head, with Restart now, in 5 to 60 minutes, or dismiss. It goes away once the server
  has started again.
- A plugin that was switched off after a start: next to its log lines, Simplify Explain's button asks why it did not load.
- Plugins and mods: the list before you search says it shows the popular ones for this server.

## 1.1.6

- Mods page on a modpack server: the modpack is one row in the installed list, with its name, version, the number of
  mods and a link to the modpack. Its mods fold away; the arrow on the row shows them one by one. Mods you added here,
  and mods that were switched off after a start, stay in the list.
- The installed list opens and reacts much faster with many mods: the pause and remove buttons are light buttons with a
  plain tooltip, and who may use them is checked once instead of once per row.
- In Simplify's server page each column scrolls on its own: scrolling the installed list no longer moves the search
  results.

## 1.1.5

- Toolkit settings: Save key, Test and Remove sit in the head of the page instead of a row of buttons inside the form. In
  Simplify's host mode they are in the title bar.
- Plugins/Mods: the button for your own CurseForge key sits in the head of its card.
- Minecraft: the MOTD preview runs edge to edge in its card, instead of a box inside the card.

## 1.1.4

- The Plugins and Mods page opens at once: its boxes stand straight away with placeholder rows, and the search results
  and installed plugins fill in once Wings and the plugin sites have answered. Before, the page only appeared after all
  of that, and the boxes jumped as their content arrived. The server's files are also read once instead of twice.

## 1.1.3

- The Plugins and Mods page follows Simplify's style: the pause, play, log and remove buttons of each installed plugin are
  Simplify icon buttons (a quiet bordered square, accent colour on hover) instead of large coloured icons, and your own
  CurseForge key has its own card under the installed plugins instead of a loose button above the page.

## 1.1.2

- Simplify 1.3's setup now also asks for the panel's CurseForge key, so a new panel is set up in one go. The key is checked with CurseForge before it is saved. On older Simplify versions, or without Simplify, nothing changes.

## 1.1.1

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
