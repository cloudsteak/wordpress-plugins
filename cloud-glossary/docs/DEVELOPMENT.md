# Cloud Glossary Development Guide

## Core Model

The plugin uses a **concept-first model**:

- One `cloud_service` post represents one generic concept.
- `post_title`: generic concept name.
- `post_content`: generic concept description.
- Provider-specific values are stored in meta blocks (`aws`, `azure`, `gcp`).

## Main Components

- `cloud-glossary.php`: bootstrap, constants, hooks
- `includes/class-cg-cpt.php`: CPT + category taxonomy registration
- `includes/class-cg-meta.php`: provider block meta UI, save handlers, meta registration, autocomplete AJAX
- `includes/class-cg-admin.php`: list UX, filters, duplicate action, usage screen
- `includes/class-cg-rest.php`: `cloud-glossary/v1` endpoints + cache invalidation
- `includes/class-cg-shortcode.php`: `[cloud_glossary]` render and asset enqueue
- `assets/js/glossary.js`: frontend rendering logic

## Meta Contract

Central field:

- `_cg_order` (integer)

Provider fields (`aws`/`azure`/`gcp`):

- `_cg_{provider}_name`
- `_cg_{provider}_short_description`
- `_cg_{provider}_official_docs_url`
- `_cg_{provider}_related_posts`

`_cg_{provider}_related_posts` shape:

- array of `{ post_id: int, custom_title: string }`

## REST Contract

`GET /wp-json/cloud-glossary/v1/services` returns concept rows with:

- `id`, `slug`, `title`, `description`, `category`, `order`
- `providers.aws|azure|gcp` with:
  - `name`, `short_description`, `official_docs_url`, `related_posts`

## Safe Extension Rules

1. Any new provider-like field must be added in all layers:
  - meta box render
  - save validation
  - register_post_meta schema
  - REST serializer
  - frontend renderer
2. Keep `_cg_` prefix for plugin-owned meta.
3. Validate post references before save.
4. Keep cache invalidation hooks aligned with data changes.

## Manual Regression Checklist

1. Create a concept with AWS/Azure/GCP values.
2. Save and reload edit screen, verify data persistence.
3. Verify REST payload shape.
4. Verify `[cloud_glossary]` renders one row per concept.
5. Verify info modal shows provider description + docs link.

