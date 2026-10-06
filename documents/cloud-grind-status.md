# Cloud grind status — WooCommerce corpus PHPUnit

| Field | Value |
|-------|--------|
| Branch | `remote-test-2026-10-06` |
| Spec | `documents/test-suite-plan.md` |
| Brief | `documents/cloud-grind-agent.md` |
| Latest SHA | `479e066f93` |

## Work queue

- [x] W0 — `.cursor/install.sh` / environment (2026-10-06 UTC, Docker + pnpm + iptables FORWARD fix)
- [x] W1 — wp-env PHP **8.4.26** + first full run attempt (bootstrap + env green; initial PHPUnit inventory started)
- [ ] W2 — corpus-smoke testsuite (deferred until W3 stabilizes full suite)
- [ ] W3 — full suite **0F/0E** (in progress — stop-on-failure grind on PHP 8.4 curation regressions)
- [ ] W4 — per-testsuite verification
- [ ] W5 — JUnit artifact from green run
- [ ] W6 — README-corpus + command docs

## Log

- **2026-10-06 ~22:38 UTC** — W0/W1: Added `.cursor/install.sh`, `start.sh`, `environment.json`; `plugins/woocommerce/.wp-env.corpus.json` (PHP 8.4); `bin/corpus-wp-env-start.sh` (iptables FORWARD for DinD). wp-env starts on PHP 8.4.26. PHPUnit lists **9752** tests; bootstrap green after Container/test-stub fixes. W3: fixing runtime TypeError/signature failures (e.g. `wc_format_decimal`, `LookupDataStore`, `ObjectCache`, order items).
- **2026-10-06 ~22:40 UTC** — W3: Stop-on-failure past test ~13 (`wc_hex_darker`/`dechex` int cast). Full suite run in progress on branch tip `e8d351df08`.
- **2026-10-06 ~23:05 UTC** — W3 resume: stop-on-failure past ~400 tests (`b2d25bfca8`). Fixes include checkout shipping methods, coupon/order item types, analytics product sync `round()`, mobile messaging blog id, `wc_let_to_num`, CSV export encoding.
- **2026-10-06 ~23:08 UTC** — W3: stop-on-failure past **~514** tests (`5e48ca8b8b`). Additional fixes: log handler `wp_mail`, `wc_make_numeric_postcode`, PayPal API URL `strstr`, order item `calculate_taxes` signatures, legacy `WC_Order_Item_Meta` item type.
- **2026-10-06 ~23:20 UTC** — W3 resume: stop-on-failure past **~900** tests (`f8a4c7e093`). Notable fixes: `WC_Comments` filter removable callback, order coupon/privacy assert fixes, `ObjectCache::is_cached`, variable product version invalidation, wc-admin `legacy-settings` REST args.
- **2026-10-06 ~23:35 UTC** — W3: stop-on-failure past **~1076** tests (`479e066f93`). Fixes: REST `str_replace` int route params (variations + CRUD pagination), restore v2 product/variation `delete_item` success path, `WC_Brands` REST query `WP_REST_Request` type, shipping zone setting text cast for `stripslashes`.
