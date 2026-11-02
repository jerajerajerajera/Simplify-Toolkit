<!-- GEMEENSCHAPPELIJK DEEL: in alle 6 Simplify-repo's gelijk. De regels staan in docs/regels.md, niet hier (V40). -->
# Werkwijze Simplify

Simplify is één product: de kern `simplify` plus de add-ons `simplify-servermaker`, `-toolkit`, `-eggupdates`,
`-explain` en `-hosting`. De splitsing is alleen verpakking: elke add-on volgt dezelfde UI en dezelfde regels.
Schrijf met de eigenaar in het Nederlands; code, README, CHANGELOG, commits en PR's in het Engels.

## Lees eerst

De regels staan niet in dit bestand. Lees bij de start, in deze volgorde:

1. `docs/regels.md`: werkregels (WR) en productregels (P). De kernrepo is de bron; elke add-on heeft een identieke kopie.
2. `docs/besluiten.md`: zoek hier eerst voor je zelf iets kiest (kopie van `plannen/besluiten.md` in project-files).
3. `docs/bugs.md`: bekende bugs en hun tests. Raak je die code, draai die tests.
4. `docs/prompts/startbrieven.md`: het algemene deel en de brief van je rol.

Bij verschil tussen dit bestand, de chat en `docs/regels.md` geldt `docs/regels.md`.

## Testen

- `bash tests/run.sh` (draait ook `tests/hub-check.py` en `tests/lang.php`), `pint --test` en in CI `checks.yml`
  met panel-smoke op elke Pelican-versie in de matrix. Wat "klaar" is, staat in P-31.
- Geen `composer install` of `yarn install` in de cloudcontainer; het echte paneel draait op GitHub Actions.
- `tests/docs-sync.sh` (in CI) controleert dat `docs/regels.md`, `docs/besluiten.md` en `docs/prompts/` gelijk
  zijn aan de kern op `main`.

## Deze repo: Toolkit (`simplify-toolkit`)

- Gereedschap voor servers: plugins en mods (Modrinth, Hangar, SpigotMC, CurseForge), Minecraft-instellingen
  (MOTD, server.properties, servericoon), spelersbeheer, populaire plugins, geplande herstart, automatische back-ups.
- Neemt de gegevens van de oude Mods-add-on over (`tests/panel/takeover.php`). Die overname blijft werken.
- Stabiele API voor andere add-ons (README, "For other Simplify add-ons"): `MotdEditor` (Server Maker) en
  `AutomaticBackups::save()`/`panel()`. Signatuur nooit stil veranderen; eerst de Server Maker-thread melden.
- `tests/panel/smoke.php` telt de stappen (`$expected`): pas dat getal aan als je een stap toevoegt.
- In de functielijst (`docs/REFERENCE-070.md` in de kern) horen hier o.a. T1 spelersbeheer, T3 populaire plugins,
  T4 Explain-knop bij uitgezette plugin, K6 herstart, B1 automatische back-ups.
- Doorklikken: op een draaiende Minecraft-server met console open; elke Toolkit-pagina via het servermenu (alleen het
  contentvak laadt), opslaan, en terug naar de console zonder herladen.
