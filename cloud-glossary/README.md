# Cloud Glossary

Cloud Glossary is a WordPress plugin for managing **cloud concepts** and their AWS/Azure/GCP mappings.

Current status: concept-first data model + admin + REST + frontend shortcode are implemented.

## What Is Implemented

- Custom post type: `cloud_service` (one record = one generic concept)
- Taxonomy: `cloud_category`
- Meta box with provider blocks: AWS / Azure / GCP
- Central order field: `_cg_order`
- Provider fields per block:
  - `name`
  - `short_description`
  - `official_docs_url`
  - `related_posts`
- REST API:
  - `GET /wp-json/cloud-glossary/v1/services`
  - `GET /wp-json/cloud-glossary/v1/services/{id}`
- Frontend shortcode: `[cloud_glossary]`

## Installation

1. Copy `cloud-glossary/` into `wp-content/plugins/`.
2. Activate **Cloud Glossary** in WP Admin > Plugins.
3. On activation, rewrite rules are flushed and default category terms are created.

## Quick Usage

1. Open **Cloud Szolgáltatások** in admin.
2. Create a new concept entry:
  - title = generic concept name
  - content = generic concept description
3. Select a category.
4. Fill AWS / Azure / GCP blocks in **Szolgáltatás részletei (szolgáltatónként)**.
5. Save/publish.
6. Insert `[cloud_glossary]` into a page/post.

## Data Model

### Post Type

- `cloud_service`

### Taxonomy

- `cloud_category`

### Meta Keys

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

## Security Model

- Meta save: nonce + capability + autosave guards
- AJAX endpoints: nonce + `edit_posts` capability checks
- Input sanitation on save

## Next Docs

- `docs/DEVELOPMENT.md`
