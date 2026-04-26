# Cloud Glossary

Cloud Glossary is a WordPress plugin for managing cloud service entries (AWS, Azure, GCP, generic) with a dedicated custom post type, taxonomies, and admin productivity tools.

Current status: **Phase 1-3 implemented**.

## What Is Implemented

- Custom post type: `cloud_service`
- Taxonomies: `cloud_provider`, `cloud_category`
- Activation seed terms (providers + default categories)
- Meta box on `cloud_service` edit screen:
  - `_cg_short_description`
  - `_cg_official_docs_url`
  - `_cg_equivalents`
  - `_cg_related_posts`
  - `_cg_order`
- Meta registration in REST via `register_post_meta`
- Admin list improvements:
  - custom columns
  - sortable columns
  - provider/category filters
  - duplicate row action
- Admin autocomplete AJAX endpoints:
  - `cg_search_services`
  - `cg_search_posts`
- Custom REST namespace:
  - `GET /wp-json/cloud-glossary/v1/services`
  - `GET /wp-json/cloud-glossary/v1/services/{id}`
  - transient cache key: `cg_services_cache` (1 hour)

## Installation

1. Copy `cloud-glossary/` into `wp-content/plugins/`.
2. Activate **Cloud Glossary** in WP Admin > Plugins.
3. On activation, rewrite rules are flushed and default terms are created.

## Quick Usage

1. Open **Cloud Szolgáltatások** in admin.
2. Create a new `cloud_service` item.
3. Assign one provider and one category.
4. Fill in Service Details meta box.
5. Save/publish.
6. Use list filters/sorting to manage entries at scale.

## Data Model

### Post Type

- `cloud_service`

### Taxonomies

- `cloud_provider`: `aws`, `azure`, `gcp`, `generic`
- `cloud_category`: `halozat`, `biztonsag`, `terheleselosztas`, `compute`, `adat`, `egyeb`

### Meta Keys

- `_cg_short_description` (string)
- `_cg_official_docs_url` (string)
- `_cg_equivalents` (array of `cloud_service` IDs)
- `_cg_related_posts` (array of `{ post_id, custom_title }`)
- `_cg_order` (integer)

## REST Notes

Because `cloud_service` uses `show_in_rest = true` and `rest_base = cloud-services`:

- `GET /wp-json/wp/v2/cloud-services`
- `GET /wp-json/wp/v2/cloud-services/{id}`

Meta appears under `meta` for users with proper capability.

## Security Model

- Meta save: nonce + capability + autosave guards
- AJAX endpoints: nonce + `edit_posts` capability checks
- Input sanitation on save

## Known Scope (Not Yet Implemented)

- `[cloud_glossary]` frontend shortcode UI
- CSV import/export
- Compare mode, modal UX, schema output, tests

## Next Docs

For internal architecture and contribution workflow, see:

- `docs/DEVELOPMENT.md`
