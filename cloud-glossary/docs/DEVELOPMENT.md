# Cloud Glossary – Fejlesztési Útmutató

## Alapmodell

A plugin egy **koncepció-központú modellt** használ:

- Egy `cloud_service` bejegyzés egy generikus fogalmat képvisel.
- `post_title`: generikus fogalom neve.
- `post_content`: generikus fogalom leírása.
- Szolgáltatónkénti értékek metaadat-blokkokban tárolódnak (`aws`, `azure`, `gcp`).

## Fő Összetevők

- `cloud-glossary.php`: indítás, konstansok, hook-ok
- `includes/class-cg-cpt.php`: CPT + kategória taxonomia regisztrálása
- `includes/class-cg-meta.php`: szolgáltató blokk meta felület, mentési kezelők, meta regisztráció, automatikus kitöltés AJAX
- `includes/class-cg-admin.php`: lista felhasználói élmény, szűrők, másolás funkció, használati képernyő
- `includes/class-cg-rest.php`: `cloud-glossary/v1` végpontok + gyorsítótár invalidálása
- `includes/class-cg-shortcode.php`: `[cloud_glossary]` megjelenítés és eszközök betöltése
- `assets/js/glossary.js`: frontend megjelenítési logika

## Metaadat Specifikáció

Központi mező:

- `_cg_order` (egész szám)

Szolgáltatónkénti mezők (`aws`/`azure`/`gcp`):

- `_cg_{service_provider}_name`
- `_cg_{service_provider}_short_description`
- `_cg_{service_provider}_official_docs_url`
- `_cg_{service_provider}_related_posts`

`_cg_{service_provider}_related_posts` szerkezete:

- `{ post_id: int, custom_title: string }` elemekből álló tömb

## REST API Szerződés

`GET /wp-json/cloud-glossary/v1/services` fogalom sorokat ad vissza az alábbiakkal:

- `id`, `slug`, `title`, `description`, `category`, `order`
- `providers.aws|azure|gcp` az alábbiak szerint:
  - `name`, `short_description`, `official_docs_url`, `related_posts`

## Biztonságos Bővítési Szabályok

1. Bármely új szolgáltatótípusú mező hozzáadása minden rétegben szükséges:
  - meta box megjelenítés
  - mentési validálás
  - register_post_meta séma
  - REST szerializáló
  - frontend megjelenítő
2. Tartsd meg a `_cg_` előtagot a plugin-hoz tartozó metaadatokhoz.
3. Bejegyzés-referenciákat ellenőrizz mentés előtt.
4. Tartsd szinkronban a gyorsítótár invalidálási hookokat az adatváltozásokkal.

## Kézi Regressziós Ellenőrző Lista

1. Hozz létre egy fogalmat AWS/Azure/GCP értékekkel.
2. Mentés után újratöltsd a szerkesztési képernyőt, ellenőrizd az adatok megmaradását.
3. Ellenőrizd a REST válasz szerkezetét.
4. Ellenőrizd, hogy a `[cloud_glossary]` egy sort jelenít meg fogalonként.
5. Ellenőrizd, hogy az info modal megjeleníti a szolgáltató leírását és dokumentációs linket.

