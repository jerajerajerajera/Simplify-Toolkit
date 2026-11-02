# Bugregister Simplify Toolkit

> Elke gefikste bug staat hier, met de test die hem bewaakt (WR-11). Een terugkerende bug is een regressie: zoek
> eerst uit waarom de test hem niet ving. Een rij weghalen mag niet, de test weghalen ook niet. Nummers (B-n) geeft
> alleen de regisseur; "nr volgt" betekent dat de regisseur nog een nummer moet geven. Startvulling 2-11 (V35): de
> bugs die twee keer of vaker terugkwamen, uit CHANGELOG en projectnotities. "Nog geen test" is een open punt voor
> de repo-thread.

| Nr | Datum | Wat zag de gebruiker | Oorzaak | Fix (versie) | Regressietest | Teruggekomen | Regel |
|---|---|---|---|---|---|---|---|
| nr volgt | 1.2.1, 1.3.1 | Iets loopt buiten zijn vak of de pagina scrolt: lange whitelist over Operators heen (1.2.1), instellingspagina scrolt met Simplify (1.3.1) | Hoogte niet vast; formulier zonder `s-fit` | 1.2.1, 1.3.1 | `tests/panel/smoke.php` (instellingsformulier heeft `s-fit`); whitelist: nog geen test | 1× | P-7 |

## Patronen

Bugs met dezelfde oorzaak, en de regel of check die het hele patroon voorkomt.
- **Pagina scrolt** (kern 6×, Toolkit, Server Maker, Explain, Hosting): P-7. Elke instellingspagina van een add-on
  heeft `s-fit` en een smoke-stap die dat controleert; elk kernscherm een audit `scroll-*`.
- **Updatepad** (kern 1.4.4, 1.5.1): P-28. Upgradetest met echte 0.7.0-instellingen in `checks.yml`.
- **Testpaneel lijkt niet op het echte paneel** (Egg Updates 1.1.3/1.1.4, kern-audit): P-31. Smoke en audit bootsen
  het echte paneel na (volle nodes, echte eggs, 20+ servers, nep-plugin).
