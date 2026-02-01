# CloudMentor Events

Kompakt eseménylista WordPress plugin Cloud és AI technológiai határidők megjelenítéséhez.

![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![License](https://img.shields.io/badge/License-GPLv2-green)
![Tested up to](https://img.shields.io/badge/Tested%20up%20to-6.7.2-brightgreen)

## Leírás

A CloudMentor Events plugin lehetővé teszi, hogy közelgő technológiai határidőket, változásokat és eseményeket jeleníts meg a WordPress oldaladon. Ideális Cloud és AI témájú blogokhoz, ahol fontos nyomon követni az Azure, AWS, GCP és egyéb platformok változásait.

### Főbb funkciók

- **Custom Post Type**: Egyszerű eseménykezelés a WordPress adminban
- **Platform kategóriák**: Azure, AWS, GCP és egyéb platformok szűrése
- **Esemény típusok**: Új, Beállítás, Biztonság, Kivezetés, Megszűnik, stb.
- **Soft/Hard határidők**: Megkülönböztethető ajánlott és kötelező határidők
- **Kompakt nézet**: Kattintásra lenyíló részletek
- **Widget támogatás**: Sidebar-ban is elhelyezhető
- **Shortcode**: Rugalmas beillesztés bárhová
- **Themify kompatibilis**: Teljes integráció Themify témákkal
- **Reszponzív design**: Mobil-barát megjelenés
- **Akadálymentesség**: ARIA támogatás, billentyűzet navigáció

## Telepítés

1. Töltsd le a plugint
2. Másold a `cloudmentor-events` mappát a `/wp-content/plugins/` könyvtárba
3. Aktiváld a plugint a WordPress admin felületen
4. Állítsd be a **Beállítások > CloudMentor Events** menüben

## Használat

### Új esemény létrehozása

1. Menj a **Cloud Események > Új hozzáadása** menüpontra
2. Add meg az esemény címét (max. 50 karakter ajánlott)
3. Állítsd be a határidő dátumát
4. Válaszd ki a Platform Kategóriát (pl. Azure)
5. Válaszd ki az Esemény Típust (pl. Biztonság)
6. Opcionálisan add meg a részletes leírást és forrás URL-t
7. Publikáld az eseményt

### Shortcode használata

Alapértelmezett megjelenítés:
```
[cloud-events]
```

Testreszabott megjelenítés:
```
[cloud-events count="3" category="azure" type="biztonsag" show_type="true"]
```

### Elérhető shortcode paraméterek

| Paraméter | Leírás | Alapértelmezett |
|-----------|--------|-----------------|
| `count` | Megjelenített események száma (1-20) | 5 |
| `category` | Platform szűrő (azure, aws, gcp) | - |
| `type` | Típus szűrő (biztonsag, kivezetes, stb.) | - |
| `show_category` | Kategória megjelenítése | true |
| `show_type` | Típus megjelenítése | true |
| `date_format` | Dátum formátum (hungarian/iso/relative) | hungarian |
| `show_past` | Múltbeli események megjelenítése | false |
| `class` | Extra CSS osztály | - |

### Widget használata

1. Menj a **Megjelenés > Widgetek** menüpontra
2. Húzd a "CloudMentor Események" widgetet a kívánt helyre
3. Állítsd be a címet és szűrőket

## Testreszabás

### CSS testreszabás

A plugin CSS osztályai:

```css
.cme-events-list          /* Fő konténer */
.cme-event-item           /* Esemény elem */
.cme-event-header         /* Kattintható fejléc */
.cme-event-date           /* Dátum */
.cme-event-category       /* Platform kategória badge */
.cme-event-type           /* Típus badge */
.cme-event-title          /* Esemény címe */
.cme-event-details        /* Lenyíló részletek */
.cme-event-description    /* Leírás */
.cme-source-link          /* Forrás link */
```

### Sürgősségi osztályok

```css
.cme-urgency-critical     /* 7 napon belüli */
.cme-urgency-soon         /* 30 napon belüli */
.cme-urgency-upcoming     /* 90 napon belüli */
.cme-urgency-future       /* 90 napon túli */
.cme-urgency-past         /* Lejárt */
```

### Színsémák

- `cme-scheme-default` - Világos téma
- `cme-scheme-dark` - Sötét téma
- `cme-scheme-minimal` - Minimalista
- `cme-scheme-colorful` - Színes

## Fejlesztői dokumentáció

### JavaScript API

```javascript
// Összes esemény kinyitása
CMEEvents.expandAll();

// Összes esemény bezárása
CMEEvents.collapseAll();

// Esemény változás figyelése
$(document).on('cme:toggle', function(e, data) {
    console.log('Event toggled:', data.item, 'Expanded:', data.expanded);
});
```

### Filter hookok

```php
// Események lekérdezés módosítása
add_filter('cme_events_query_args', function($args) {
    // Módosítsd a WP_Query argumentumokat
    return $args;
});
```

## Követelmények

- WordPress 6.2+
- PHP 8.0+
- Tesztelve: WordPress 6.7.2

## Changelog

### 1.0.7
- Egyszerűsített nézet: csak vertikális elrendezés
- Eltávolított beállítások: Elrendezés szekció (view_mode, horizontal_columns)
- Widget és shortcode is 300px-en jól működik

### 1.0.5
- Horizontális nézet széles képernyőkön (1200px+)
- Grid layout: 2 oszlop (1200px+), 3 oszlop (1600px+), 4 oszlop (2000px+)
- Widget-ben megmarad a vertikális nézet
- Mai dátum javítás: nem áthúzva, hanem figyelmeztető pulzáló animáció
- "Ma" események speciális ⚠️ jelölése

### 1.0.4
- Relatív idő tooltip a dátumoknál
- Archivált események megjelenítési beállítások
- Nagyobb részletek konténer (700px/1000px)

### 1.0.3
- Opcionális változás típus hozzáadása
- Színes ikonos jelzők szöveg helyett
- Widget minimum szélesség: 300px

### 1.0.2
- Magyar nyelvű határidő címkék
- "Hasznos link" szöveg

### 1.0.1
- Plugin URI és verzió követelmények

### 1.0.0
- Első kiadás
- Custom Post Type és Taxonomy-k
- Shortcode és Widget támogatás
- Themify kompatibilitás
- 4 színséma
- Reszponzív design

## Szerző

**CloudMentor**

- Website: [cloudmentor.hu](https://cloudmentor.hu)
- GitHub: [github.com/cloudsteak/wordpress-plugins](https://github.com/cloudsteak/wordpress-plugins)

## Licenc

GPLv2 vagy újabb - [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
