# WordPress Plugins különböző megoldásokra

Ez a tárhely különböző WordPress bővítményeket tartalmaz, amelyek különféle funkciókat és szolgáltatásokat kínálnak a WordPress weboldalak számára. 

## Tartalom

## Bővítmények listája

### 1. CloudMentor Events

**Verzió:** 1.0.8  
**Leírás:** Kompakt eseménylista Cloud és AI technológiai határidők megjelenítéséhez. Themify kompatibilis.

#### Funkciók

- **Custom Post Type:** Egyszerű eseménykezelés a WordPress adminban dedikált bejegyzés típussal
- **Platform kategóriák:** Azure, AWS, GCP és egyéb felhő platformok szűrése
- **Esemény típusok:** Új, Beállítás, Biztonság, Kivezetés, Megszűnik, és egyéb típusok megkülönböztetése
- **Soft/Hard határidők:** Megkülönböztethető ajánlott és kötelező határidők kezelése
- **Kompakt nézet:** Kattintásra lenyíló részletek a jobb áttekinthetőség érdekében
- **Widget támogatás:** Sidebar-ban is elhelyezhető widget formában
- **Shortcode támogatás:** Rugalmas beillesztés bárhová shortcode használatával
- **Themify kompatibilis:** Teljes integráció Themify témákkal
- **Reszponzív design:** Mobil-barát megjelenés minden eszközön
- **Akadálymentesség:** ARIA támogatás és billentyűzet navigáció

#### Shortcode használat

Alapértelmezett megjelenítés:
```
[cloud-events]
```

Egyedi limittel:
```
[cloud-events limit="10"]
```

Szűrés platform szerint:
```
[cloud-events platform="azure"]
```

Widget formában a sidebar-ban is használható a "CloudMentor Events" widget hozzáadásával.

#### Követelmények

- WordPress 6.2 vagy újabb
- PHP 8.0 vagy újabb
- Tesztelve WordPress 6.7.2-ig

---

### 2. Content Guard (CloudMentor)

**Verzió:** 0.0.5  
**Leírás:** Tartalom védelmi plugin WordPress oldalakhoz és bejegyzésekhez

#### Funkciók

- **Teljes tartalom védelem:** A plugin megvédi az összes oldalt és bejegyzést, így csak bejelentkezett felhasználók férhetnek hozzájuk
- **Kivételek kezelése:** Rugalmasan megadhatók azok az oldalak, amelyek kivételként bejelentkezés nélkül is elérhetők
- **Kategória alapú kivételek:** Bejegyzés-kategóriák szintjén is beállíthatók kivételek - a kiválasztott kategóriákba tartozó bejegyzések szabadon elérhetők
- **Átirányítás testreszabása:** Beállítható átirányítási céloldal, ahova a nem bejelentkezett felhasználók kerülnek, ha védett tartalmat próbálnak elérni
- **Hurok védelem:** A plugin automatikusan kezeli az átirányítási hurkokat, biztosítva a zavartalan működést

#### Használat

A beállítások a WordPress admin felületen az „Beállítások" > „Content Guard" menüpontban érhetők el. Itt:
- Kiválaszthatók a kivételként engedélyezett oldalak
- Megadhatók a kivételként engedélyezett bejegyzés-kategóriák
- Beállítható az átirányítás céloldala nem bejelentkezett felhasználók számára

#### Követelmények

- WordPress 6.2 vagy újabb
- PHP 8.0 vagy újabb
- Tesztelve WordPress 6.7.2-ig

---

### 3. Lab Launcher (CloudMentor)

**Verzió:** 1.1.1  
**Leírás:** CloudMentor Lab indító plugin Azure és AWS felhő platformokhoz

#### Funkciók

- **Felhő labor indítás:** WordPress felületről közvetlenül indíthatók Azure és AWS labor környezetek
- **Felhasználó alapú kezelés:** Minden felhasználó saját labor példányokat indíthat és kezelhet
- **REST API integráció:** Teljes REST API támogatás a backend szolgáltatással való kommunikációhoz
- **Labor státusz követés:** Valós idejű státusz követés a laborok állapotáról (pending, success, error)
- **Webhook támogatás:** Külső rendszerek értesíthetik a plugint a labor státusz változásokról
- **Labor ellenőrzés:** Beépített labor verifikációs funkció a teljesítések nyomon követéséhez
- **Shortcode támogatás:** Egyszerű shortcode-ok használata WordPress oldalakon és bejegyzésekben
- **Admin felület:** Átfogó adminisztrációs felület kurzusok, laborok és felhasználói státuszok kezeléséhez
- **TTL kezelés:** Beállítható labor élettartam (Time To Live) a költségek optimalizálásához

#### Fő komponensek

- **API hívások:** Biztonságos kommunikáció a CloudMentor backend szolgáltatással
- **Kurzus kezelő:** Admin felület a kurzusok és laborok konfigurálásához
- **Felhasználói státusz:** Részletes státusz követés felhasználónként és labor típusonként
- **Shortcode generátor:** Admin felület shortcode-ok egyszerű generálásához

#### REST API végpontok

- `/lab-launcher/v1/start-lab` - Labor indítása
- `/lab-launcher/v1/verify-lab` - Labor ellenőrzése
- `/lab-launcher/v1/lab-status-update` - Státusz frissítés
- `/lab-launcher/v1/lab-status-webhook` - Webhook státusz fogadás

#### Követelmények

- WordPress 6.2 vagy újabb
- PHP 8.0 vagy újabb
- Tesztelve WordPress 6.7.2-ig
- CloudMentor backend szolgáltatás hozzáférés

---

## Licenc

Mindhárom bővítmény MIT/GPL licenc alatt érhető el.




