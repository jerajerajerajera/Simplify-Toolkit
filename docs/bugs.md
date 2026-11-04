# Bugregister Simplify Toolkit

> Elke gefikste bug staat hier, met de test die hem bewaakt (WR-11). Een terugkerende bug is een regressie: zoek
> eerst uit waarom de test hem niet ving. Een rij weghalen mag niet, de test weghalen ook niet. Nieuwe nummers geeft de
> repo-thread zelf met het prefix van deze repo (WR-8); Startvulling 2-11 (V35): de
> bugs die twee keer of vaker terugkwamen, uit CHANGELOG en projectnotities. "Nog geen test" is een open punt voor
> de repo-thread.

| Nr | Datum | Wat zag de gebruiker | Oorzaak | Fix (versie) | Regressietest | Teruggekomen | Regel |
|---|---|---|---|---|---|---|---|
| B9 | 1.2.1, 1.3.1 | Iets loopt buiten zijn vak of de pagina scrolt: lange whitelist over Operators heen (1.2.1), instellingspagina scrolt met Simplify (1.3.1) | Hoogte niet vast; formulier zonder `s-fit` | 1.2.1, 1.3.1 | `tests/panel/smoke.php`: "the admin settings page renders" (formulier heeft `s-fit`) en "the Players page lists whitelist, operators and bans" (lijsten scrollen in hun eigen vak) | 1× | P-7 |
| TK-B1 | 2026-11-03 | Connections (Simplify 2.0.0-beta.3) toont alleen Steam; CurseForge ontbreekt | Toolkit gaf geen `connections()`-rij (add-on-API 6); de eigen sleutel stond alleen op de Plugins-pagina | Unreleased | `tests/addon.php` (connections geeft `CurseForgeConnection`; rood op a005a07) en `tests/panel/smoke.php`: "the CurseForge row on Simplify's Connections page …" | 0× | P-6 |

## Patronen

Bugs met dezelfde oorzaak, en de regel of check die het hele patroon voorkomt.
- **Pagina scrolt** (B9: kern 6×, Toolkit, Server Maker, Explain, Hosting): P-7. Elke instellingspagina van een add-on
  heeft `s-fit` en een smoke-stap die dat controleert; elk kernscherm een audit `scroll-*`.
- **Updatepad** (B11, kern 1.4.4 en 1.5.1): P-28. Upgradetest met echte 0.7.0-instellingen in `checks.yml`.
- **Testpaneel lijkt niet op het echte paneel** (B15 Egg Updates 1.1.3/1.1.4, kern-audit): P-31. Smoke en audit bootsen
  het echte paneel na (volle nodes, echte eggs, 20+ servers, nep-plugin).
| TK-B2 | 2026-11-04 | De Pelican Hub weigert 2.0.0-beta.4: "Banned file or process operations detected (tests/addon.php:7 eval())" | `tests/addon.php` maakte de stand-in voor `App\Models\User` met `eval()`; `tests/hub-check.py` kende `eval` niet als verboden naam | Unreleased | `tests/hub-check.py` (`eval`, `assert`, `create_function` in BANNED; rood op a3dbd53) | 0× | P-26 |
