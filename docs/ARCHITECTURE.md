# Architecture — WM Ethical Conversion Triggers
`wm-ethical-conversion-triggers` · version `1.2.0` from the plugin header · WordPress 6.0+ · PHP 8.0+ · text domain `wm-ethical-conversion-triggers`

Measured on the 1.2.0 tree (2026-11-20). Every figure below comes from the code, not from any document — the commands are given so they can be re-run.

```bash
# the inventory in this file
python3 the fleet's architecture inventory tool wm-ethical-conversion-triggers
```

---

## What runs where

```
plugins_loaded        Plugin::init_hooks()
                        hooks wm_register_suite_module FIRST, then checks
                        Plugin::woocommerce_active()
                        WooCommerce absent  ->  ONLY an admin notice (and that filter).
                        Nothing else boots. is_product() and wc_get_product() are
                        called only behind function_exists — without that guard the
                        front page of a site without WooCommerce answered 500
                        (measured 2026-09-14).

plugins_loaded:99     WM Suite Hub builds its registry from wm_register_suite_module
                        Integrations::register_suite_module()
                        hub contract absent   ->  the list goes back untouched
                        hub contract present  ->  + Spoke_Adapter (health, telemetry,
                                                  SBOM entry, self_test)
                        health: ERROR without WooCommerce, DEGRADED without
                        wc_order_product_lookup or wc_order_stats, else HEALTHY

product page          Frontend::render_ethical_trigger()
                        scarcity   <- StockVerifier::get_verified_stock()
                                      returns NULL when get_manage_stock() is false
                        social proof <- a count from wc_order_product_lookup
                        nothing to show  ->  NO container is emitted at all

POST /wp-json/wm-ect/v1/stock-update
                        HMAC-SHA256 in x-wc-webhook-signature, compared with
                        hash_equals() in constant time
                        secret from the WM_ECT_WEBHOOK_SECRET constant or the
                        wm_ect_webhook_secret option — with neither, every
                        request is refused
```

---

## Wiring — what to touch for a given change

| Change | Files, in order | Trap |
|---|---|---|
| Change what the trigger shows | `includes/class-frontend.php` **and** its guard, together | the guard in `render_ethical_trigger()` must be the **exact negation** of what is painted. It failed once by not being: "zero sales + managed stock" slipped through and emitted an empty `<div aria-live="polite" role="status">`, which a screen reader announces just the same |
| Change the stock source | `includes/class-stock-verifier.php` | `get_verified_stock()` returns `null` when stock is not managed. **That `null` is never replaced by 0 or a default** |
| Change the social-proof count | the `wc_order_product_lookup` query | never a counter of our own and never an estimate |
| Touch the webhook | `includes/class-webhook-validator.php` | `hash_equals()`, never `==`. A `==` comparison of HMACs is both timing-leaky and, for strings PHP reads as numbers, outright wrong |
| Enqueue anything | `/assets/`, local only | CI fails the build when a `wp_enqueue_*` points at a non-local host |
| Change what WM Suite Hub shows | `includes/class-spoke-adapter.php`; the hook in `includes/class-plugin.php` | the hub keeps only objects that implement its `WM_Plugin_Module_Interface` — a manifest array or a function call never appears (that was the plugin up to 1.1.4). Health is computed, never a fixed `HEALTHY`; the filter stays before the WooCommerce check or the hub loses the plugin exactly when WooCommerce is missing |

---

## Fragile spots

1. **Both numbers must be real or absent.** No managed stock ⇒ no scarcity number. No real sales ⇒ no social proof. **Inventing either is unfair competition in Germany (UWG § 5), not a style preference** — a "only 3 left" that is not measured, or a "27 people bought this" that is estimated, is an *irreführende geschäftliche Handlung*.
2. **Nothing to show means no container.** An empty live region is announced like a full one.
3. **WooCommerce is a requirement, not an assumption.** See the flow above — the missing `function_exists` guard was a 500 on the front page.
4. **`wm_ect_webhook_secret` is read and never written by this plugin.** The intended path is the `WM_ECT_WEBHOOK_SECRET` constant in `wp-config.php`, which is correct — a shared secret does not belong in the options table or in a repository. Without either, the endpoint refuses everything, which is the right default.
5. **The suite exits 1 on failure** so CI catches it, and a fix is checked **in both directions**: green with the fix, red with it reverted. A test that passes either way defends nothing.

---

<!-- wm-measured-inventory -->

## Measured inventory

Regenerated from the code, never hand-edited. Everything above this line is written by hand and stays.

```bash
python3 the fleet's architecture inventory tool wm-ethical-conversion-triggers --append-to docs/ARCHITECTURE.md
```

## Inventory by layer

**9 PHP files, 1047 lines** (excluding `tests/`, `vendor/`, `node_modules/`, `languages/`).

| Layer | Files | Lines |
|---|---:|---:|
| `(raíz)` | 2 | 120 |
| `includes` | 7 | 927 |

---

## Classes

| Class | File |
|---|---|
| `Frontend` | [`includes/class-frontend.php`](../includes/class-frontend.php) |
| `Integrations` | [`includes/class-integrations.php`](../includes/class-integrations.php) |
| `Plugin` | [`includes/class-plugin.php`](../includes/class-plugin.php) |
| `Settings` | [`includes/class-settings.php`](../includes/class-settings.php) |
| `Spoke_Adapter` | [`includes/class-spoke-adapter.php`](../includes/class-spoke-adapter.php) |
| `Stock_Verifier` | [`includes/class-stock-verifier.php`](../includes/class-stock-verifier.php) |
| `Webhook_Validator` | [`includes/class-webhook-validator.php`](../includes/class-webhook-validator.php) |

---

## Entry points

**REST routes:** `/stock-update`

---

## Persistence — who writes each key and who reads it

| Key | Written by | Read by |
|---|---|---|
| `wm_ect_webhook_secret` | — | `includes/class-webhook-validator.php` |
| `wm_ethical_triggers_delete_data_on_uninstall` | `wm-ethical-conversion-triggers.php` | `uninstall.php`, `wm-ethical-conversion-triggers.php` |

**Read but never written by this plugin:** `wm_ect_webhook_secret`. The behaviour behind them is reachable only through `wp option update` or a filter — there is no screen that sets them, so an operator cannot switch it on.


---

## Gates

- `tests/mutations.php`
- `tests/run-mutations.sh`
- `tests/test-pot-freshness.php`
- `tests/test-suite.php`
- `.github/workflows/ci.yml`

What CI actually runs is in the workflow files above — a test file that no workflow invokes is not a gate.

---

## Drift detected against the documentation

Nothing measurable: versions agree and every class named in the documents exists.

