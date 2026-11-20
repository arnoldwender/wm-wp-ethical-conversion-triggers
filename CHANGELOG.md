# Changelog

All notable changes to **WM Ethical Conversion Triggers** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-11-20

### Fixed
- The plugin never appeared in WM Suite Hub. `Integrations::register_suite_spoke()` called a function `wm_register_suite_module()` that the hub does not define, or fired the hub's filter as an action with an array; the hub builds its registry from the `wm_register_suite_module` filter and keeps only objects that implement `WM_Plugin_Module_Interface`. Measured on 2026-11-20 with the hub's own `Module_Registry` and the 24 plugins active: 22 of the 23 family plugins registered, this one did not.

### Added
- `includes/class-spoke-adapter.php` (`Spoke_Adapter`): health `ERROR` without WooCommerce, `DEGRADED` when `{prefix}wc_order_product_lookup` or `{prefix}wc_order_stats` is missing, otherwise `HEALTHY`; telemetry (`woocommerce_active`, `analytics_tables`, `webhook_secret_configured`, `delete_data_on_uninstall`); an SBOM entry with the SHA-256 of the main file; the `self_test` action. The filter is hooked in `Plugin::init_hooks()` before the WooCommerce check, so without WooCommerce the hub lists the plugin as `ERROR` instead of not at all.
- `Webhook_Validator::configured_secret()`, read by the signature check and by the hub telemetry.

## [1.1.4] - 2026-11-19

- Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.

- Added root `SUPPORT.md`, `.github/ISSUE_TEMPLATE/{config.yml,bug_report.yml}`, and explicit zero-support / "AS IS" liability notices across `README.md`, `README.de.md`, `README.es.md`, `readme.txt`, `SECURITY.md`, and `llms.txt`.
- Migrated plugin author/catalog/repository metadata to personal open-source distribution (`Arnold Wender`, `https://arnoldwender.com/wordpress-plugins/wm-ethical-conversion-triggers/`, `https://github.com/arnoldwender/wm-wp-ethical-conversion-triggers`), normalized FSF GPL-2.0 `LICENSE` metadata where applicable, and updated `.wordpress-org/` banner branding to `ARNOLD WENDER · OPEN SOURCE`.

## [1.1.3] - 2026-11-19

### Added
- Official `.wordpress-org/` visual kit (`icon.svg`, `icon-128x128.png`, `icon-256x256.png`, `banner-772x250.png`, `banner-1544x500.png`) and live Playwright WooCommerce/admin screenshots (`screenshot-1.png`, `screenshot-2.png`) with `== Screenshots ==` in `readme.txt`.
- Trilingual documentation (`README.md` in English, `README.de.md` in German, and preserved Spanish documentation in `README.es.md`) plus root `llms.txt` with live previews and direct plugin catalog URL (`https://arnoldwender.com/wordpress-plugins/wm-ethical-conversion-triggers/`).

### Changed
- Excluded `versionen/` in `.distignore` and aligned release metadata and SBOM (`bom.json`) at `v1.1.3`.

## [1.1.2] - 2026-11-19

### Changed
- Point `Plugin URI` to `https://arnoldwender.com/wordpress-plugins/wm-ethical-conversion-triggers/` and align release metadata at `v1.1.2`.

## [1.1.1] - 2026-11-19

### Fixed
- Inlined literal `' checked="checked"'` attribute output in `Settings::render_delete_field()` so `WordPress.Security.EscapeOutput.OutputNotEscaped` in Plugin Check 2.1.0 passes with zero errors.

## [1.1.0] - 2026-11-16

### Added
- Opt-in `uninstall.php` cleanup routine governed by `wm_ethical_triggers_delete_data_on_uninstall` (default `'0'`, preserving options and cached transients unless explicitly enabled) with an accessible **Settings > Ethical Triggers** configuration screen.
- WordPress 6.5+ Block Bindings source `wm-ethical-triggers/metrics` (`summary`, `sales_notice`, `stock_notice`) and GenerateBlocks 2.x Dynamic Tag `{{wm_ethical_trigger}}`.
- WM Suite Hub spoke registration via `wm_register_suite_module` and developer extensibility hooks (`wm_ethical_triggers_config`, `wm_ethical_triggers_ready`, `wm_ethical_triggers_stock_updated`).
- Complete gettext translation catalogs (`.po` and `.mo`) for 28 locales alongside the synchronized `wm-ethical-conversion-triggers.pot` template.
- CycloneDX 1.6 `bom.json` SBOM (`GPL-2.0-or-later`), `.license-allowlist.yml`, `AGENTS.md` symlink, and WordPress Playground `.wordpress-org/blueprints/blueprint.json`.

### Changed
- Enforced `declare(strict_types=1);` and typed signatures across all PHP files.
- Refactored `assets/css/ethical-triggers.css` to define colors, spacing, radius, and typography tokens in `:root` (`--wm-ect-*`).

### Fixed
- Counted distinct paid non-refunded WooCommerce orders in `wc_order_product_lookup` + `wc_order_stats` instead of summing raw quantities across unpaid orders.
- Tracked local `assets/css/ethical-triggers.css` and `assets/js/ethical-triggers.js` in version control.

## [1.0.0] - 2026-09-14

### Added
- Initial release with verified WooCommerce 7-day order count, managed low-stock notice, transient caching, and HMAC-SHA256 REST stock-update webhook.
