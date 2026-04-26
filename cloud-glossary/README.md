# Cloud Glossary

A Cloud Glossary egy WordPress plugin, amely **felhő fogalmak** és azok AWS/Azure/GCP leképezésének kezelésére szolgál.

Aktuális státusz: a koncepció-központú adatmodell + admin + REST + frontend shortcode implementálva van.

## Mit Implementáltunk

- Custom post type: `cloud_service` (egy rekord = egy generikus koncepció)
- Taxonomia: `cloud_category`
- Meta box szolgáltató blokkokkal: AWS / Azure / GCP
- Központi sorrendi mező: `_cg_order`
- Szolgáltató mezők blokkonként:
  - `name` (név)
  - `short_description` (rövid leírás)
  - `official_docs_url` (hivatalos dokumentáció URL)
  - `related_posts` (kapcsolódó bejegyzések)
- REST API:
  - `GET /wp-json/cloud-glossary/v1/services`
  - `GET /wp-json/cloud-glossary/v1/services/{id}`
- Frontend shortcode: `[cloud_glossary]`

## Telepítés

1. Másold a `cloud-glossary/` mappát a `wp-content/plugins/` könyvtárba.
2. Aktiváld a **Cloud Glossary** plugint a WP Admin > Bővítmények menüben.
3. Aktiváláskor a rewrite rules kiürülnek és az alapértelmezett kategória kifejezések létrejönnek.

## Gyors Használati Útmutató

1. Nyisd meg a **Cloud Szolgáltatások** menüt az adminisztrációban.
2. Hozz létre egy új koncepció bejegyzést:
  - cím = generikus koncepció neve
  - tartalom = generikus koncepció leírása
3. Válassz egy kategóriát.
4. Töltsd ki az AWS / Azure / GCP blokkokat a **Szolgáltatás részletei (szolgáltatónként)** szekcióban.
5. Mentés/közzététel.
6. Szúrj be `[cloud_glossary]`-t egy oldalba vagy bejegyzésbe.

## Adatmodell

### Post Type

- `cloud_service`

### Taxonomia

- `cloud_category`

### Meta Kulcsok

- `_cg_order`
- `_cg_aws_name`
- `_cg_aws_short_description`
- `_cg_aws_official_docs_url`
- `_cg_aws_related_posts`
- `_cg_azure_name`
- `_cg_azure_short_description`
- `_cg_azure_official_docs_url`
- `_cg_azure_related_posts`
- `_cg_gcp_name`
- `_cg_gcp_short_description`
- `_cg_gcp_official_docs_url`
- `_cg_gcp_related_posts`

## Biztonsági Modell

- Meta mentés: nonce + capability + autosave védelem
- AJAX végpontok: nonce + `edit_posts` capability ellenőrzések
- Bemeneti adatok szanitálása mentéskor

## További Dokumentáció

- `docs/DEVELOPMENT.md`
