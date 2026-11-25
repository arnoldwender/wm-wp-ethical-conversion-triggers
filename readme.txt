=== WM Ethical Conversion Triggers ===
Contributors: arnoldwender
Tags: woocommerce, social proof, scarcity, low stock, orders
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Order and low-stock notices for WooCommerce product pages, shown only when the number is real and verifiable.

== Description ==

Built for regulated e-commerce markets such as Germany, where invented urgency or invented stock constitutes unfair competition (UWG § 5). Two notices on WooCommerce product pages, each gated by a strict condition:

* "N Bestellungen dieses Artikels in den letzten 7 Tagen." is shown only when N > 0. N counts distinct paid orders (WooCommerce's paid statuses, processing and completed by default) that contain the product, excluding refunds, queried from `wc_order_product_lookup` and `wc_order_stats`. It counts paid orders, never estimated visitors or raw item quantities.
* "Nur noch N Stück auf Lager." is shown only when WooCommerce manages the stock of that product and between 1 and 5 units remain (configurable via filter).
* Ships with a synchronized `.pot` catalog and 28 compiled `.po`/`.mo` locale packs (`ar`, `bg_BG`, `cs_CZ`, `da_DK`, `de_DE`, `el`, `en_GB`, `en_US`, `es_ES`, `et`, `fi`, `fi_FI`, `fr_FR`, `ga`, `hr_HR`, `hu_HU`, `it_IT`, `lt_LT`, `lv`, `mt_MT`, `nl_NL`, `pl_PL`, `pt_PT`, `ro_RO`, `sk_SK`, `sl_SI`, `sv_SE`, `tr_TR`).

When neither condition holds, nothing is printed: not even the container. The live region (`aria-live="polite"`, `role="status"`) is emitted only when it carries content, so a screen reader never announces an empty container.

**Requires WooCommerce.** Without WooCommerce active, the plugin registers only an administrative notice and its WM Suite Hub module, which the hub then lists as `ERROR`.

= Block Editor & GenerateBlocks Integration =

* **WordPress 6.5+ Block Bindings:** Bind core Paragraph or Heading blocks to the `wm-ethical-triggers/metrics` source (`key`: `summary`, `sales_notice`, or `stock_notice`).
* **GenerateBlocks 2.x Dynamic Tag:** Use `{{wm_ethical_trigger}}` inside GenerateBlocks dynamic content fields.
* **Developer Hooks:** Customize thresholds via `wm_ethical_triggers_config` and hook into `wm_ethical_triggers_ready` or `wm_ethical_triggers_stock_updated`.

= WM Suite Hub =

With WM Suite Hub active, the plugin joins the hub's module list through the `wm_register_suite_module` filter. Health is `ERROR` without WooCommerce, `DEGRADED` when one of the two analytics tables (`wc_order_product_lookup`, `wc_order_stats`) is missing, otherwise `HEALTHY`; the `self_test` action checks WooCommerce, the tables, the managed-stock rule and that the webhook refuses an unsigned request.

= REST endpoint =

`POST /wp-json/wm-ect/v1/stock-update` updates a product's managed stock from an external ERP or warehouse system. It requires an HMAC-SHA256 signature in the `x-wc-webhook-signature` header, compared in constant time via `hash_equals()`. The secret is read from `WM_ECT_WEBHOOK_SECRET` or the `wm_ect_webhook_secret` option; without a configured secret every request is refused.

**No Support & Zero Liability ("AS IS"):** This plugin is provided free of charge as personal open-source software with **zero support** and **no private support**. Do not contact the author via email, `arnoldwender.com`, or direct messages. Report reproducible defects exclusively at `https://github.com/arnoldwender/wm-wp-ethical-conversion-triggers/issues` (no SLA or response guarantee). Under GPLv2+ Sections 11-12 it is distributed **"AS IS"** without warranty, legal advice, or author liability.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the `wm-ethical-conversion-triggers` folder to `/wp-content/plugins/`.
3. Activate the plugin through the **Plugins** screen. Product notices render automatically at `woocommerce_before_add_to_cart_form`.
4. Optional: visit **Settings > Ethical Triggers** if you wish to opt into removing plugin options and cached transients upon plugin uninstallation (disabled by default).

== Frequently Asked Questions ==

= Is technical or private support provided? =

No. This plugin comes with zero support (`0` support). Private inquiries sent via email, `arnoldwender.com`, or social/chat channels are not answered. Reproducible bugs belong solely on GitHub Issues (`https://github.com/arnoldwender/wm-wp-ethical-conversion-triggers/issues`) with no guarantee of reply or resolution. The software is provided **"AS IS"** at your sole risk with zero warranty or liability (see `SUPPORT.md` in the repository).

== Screenshots ==

1. Settings page under Settings > Ethical Triggers configuring threshold and opt-in uninstall
2. Live WooCommerce product page showing both verified notices from real paid HPOS orders and managed stock

== Changelog ==


= 1.2.1 =
* Fixed: The order notice on product pages («%d Bestellung dieses Artikels in den letzten 7 Tagen.») repeated one form for several numbers in Arabic, Croatian, Czech, Irish, Latvian, Lithuanian, Maltese, Polish, Romanian, Slovak and Slovenian, for example Czech «5 objednávky» and Polish «5 zamówienia»; each number now gets the form its language uses (Czech «5 objednávek», Polish «5 zamówień»). The READMEs quote the notice in its singular and plural form instead of «Bestellung(en)», and their version badge reads 1.2.1. The plugin's code is unchanged.

= 1.2.0 =
* Fixed: the plugin never appeared in WM Suite Hub. It called a function the hub does not define, or fired the hub's filter as an action with an array; the hub collects objects that implement its `WM_Plugin_Module_Interface` through the `wm_register_suite_module` filter.
* Added `Spoke_Adapter`: health (`ERROR` without WooCommerce, `DEGRADED` without the analytics tables), telemetry, an SBOM entry with the SHA-256 of the main file and a `self_test` action. The filter is hooked before the WooCommerce check, so the hub lists the plugin as `ERROR` instead of not at all.

= 1.1.4 =
* Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.
* Added root `SUPPORT.md`, GitHub Issue templates, explicit zero-support & "AS IS" liability notices in READMEs and `readme.txt`, and normalized canonical GPLv2+ `arnoldwender.com` / `arnoldwender/wm-wp-ethical-conversion-triggers` links.
* Updated `.wordpress-org/` visual banners to personal `ARNOLD WENDER · OPEN SOURCE` branding.

= 1.1.3 =
* Add `.wordpress-org/` vector icons, banners (`772x250`, `1544x500`), live WooCommerce/admin screenshots (`screenshot-1..2.png`), and `== Screenshots ==` descriptions.
* Rewrite `README.md` in technical English, preserve full Spanish documentation in `README.es.md`, add `README.de.md` and `llms.txt`, and link the official plugin page (`https://arnoldwender.com/wordpress-plugins/wm-ethical-conversion-triggers/`).

= 1.1.2 =
* Point Plugin URI to the official plugin page.

= 1.1.1 =
* Fixed: Inlined the literal `checked="checked"` attribute expression inside `Settings::render_delete_field()` to satisfy WordPress.org Plugin Check 2.1.0 escaping verification with zero errors.

= 1.1.0 =
* Added opt-in `uninstall.php` routine (`wm_ethical_triggers_delete_data_on_uninstall`, disabled by default) and **Settings > Ethical Triggers** admin screen.
* Added `declare(strict_types=1);` across every PHP file and migrated frontend CSS to `:root` custom properties (`--wm-ect-*`).
* Added WordPress 6.5+ Block Bindings source (`wm-ethical-triggers/metrics`), GenerateBlocks 2.x Dynamic Tag (`{{wm_ethical_trigger}}`), WM Suite Hub spoke adapter, and `wm_ethical_triggers_config` / `wm_ethical_triggers_ready` / `wm_ethical_triggers_stock_updated` developer hooks.
* Added complete translations (`.po` and `.mo`) across 28 locales, CycloneDX 1.6 `bom.json` SBOM, and WordPress Playground blueprint.
* Fixed sales notice query to count distinct paid non-refunded orders over 7 days rather than summing units across unpaid orders, and ensured local frontend CSS/JS assets are tracked.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.1 =
Translation fix only: plural forms in 11 languages. No code, data or settings change.
