# Cloud Glossary Development Guide

This document is for future development, onboarding, and safe extension of the plugin.

## 1. Architecture Overview

### Entry Point

- `cloud-glossary.php`
  - plugin header
  - constants
  - class autoloader (`CG_*` -> `includes/class-cg-*.php`)
  - activation/deactivation hooks

### Bootstrap

- `includes/class-cg-plugin.php`
  - singleton: `CG_Plugin::instance()`
  - initializes service classes:
    - `CG_CPT`
    - `CG_I18n`
    - `CG_Meta`
    - `CG_Admin`

### Domain Classes

- `includes/class-cg-cpt.php`
  - CPT + taxonomy registration
  - default term seeding
- `includes/class-cg-meta.php`
  - meta box rendering
  - save handler
  - meta registration for REST
  - admin AJAX search endpoints
- `includes/class-cg-admin.php`
  - list table columns/sorting/filtering
  - duplicate action
  - admin assets enqueue
- `includes/class-cg-i18n.php`
  - textdomain loading

## 2. Coding Rules (Project-Specific)

- Prefixes:
  - classes: `CG_`
  - functions: `cg_`
  - meta keys: `_cg_`
- User-facing strings must use `__('...', 'cloud-glossary')` / `esc_html__` etc.
- Do not use raw `$_GET`/`$_POST`; use `filter_input` + sanitize.
- Always verify capability before admin writes.
- For forms/AJAX, enforce nonces.

## 3. Current Feature Contract (Phase 1-2)

### CPT / Taxonomy Contract

- Post type: `cloud_service`
- Taxonomies: `cloud_provider`, `cloud_category`
- Keep slugs/rest_base values stable unless migration is explicitly planned.

### Meta Contract

- `_cg_short_description`: max 500 chars
- `_cg_official_docs_url`: stored as sanitized URL
- `_cg_equivalents`: array of published `cloud_service` IDs
- `_cg_related_posts`: array of published `post` references
- `_cg_order`: integer

If the shape changes, include:

1. migration strategy,
2. backward-compatible read logic,
3. data repair script if needed.

## 4. How To Extend Safely

### Add New Meta Field

1. Add UI in `CG_Meta::render_meta_box()`.
2. Add save logic in `CG_Meta::save_meta()` with validation/sanitize.
3. Register with `register_post_meta()` in `CG_Meta::register_meta()`.
4. Ensure duplicate flow copies it (`_cg_*` naming auto-copies).

### Add New Admin Action

1. Add row action link in `CG_Admin`.
2. Add `admin_action_*` handler with nonce + capability checks.
3. Redirect using `wp_safe_redirect()`.

### Add New AJAX Endpoint

1. Register endpoint in `CG_Meta::init()`.
2. Reuse centralized auth guard (or equivalent).
3. Return stable JSON shape.

## 5. Testing Checklist (Manual)

Run these before merge:

1. Create/edit `cloud_service` with all meta fields.
2. Validate list sorting/filtering and duplicate action.
3. Validate AJAX autocomplete behavior and permissions.
4. Verify REST response includes expected meta.
5. Verify no PHP warnings/notices in debug log.

## 6. Suggested Near-Term Roadmap

- Phase 3: custom REST namespace `cloud-glossary/v1` + caching invalidation hooks
- Phase 4: shortcode + base frontend UI (vanilla JS)
- Phase 5-6: search/filters/modal/compare/deep linking
- Phase 7: CSV import/export + settings
- Phase 8: schema + single template + i18n polish
- Phase 9: PHPUnit + e2e + changelog discipline

## 7. Release Hygiene

Before tagging a release:

1. bump plugin version in `cloud-glossary.php`
2. update `readme.txt` changelog
3. regenerate translation template if new strings added
4. smoke test activation/deactivation on clean WP

