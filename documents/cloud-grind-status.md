# Cloud grind status — WooCommerce corpus PHPUnit

| Field | Value |
|-------|--------|
| Branch | `remote-test-2026-10-06` |
| Spec | `documents/test-suite-plan.md` |
| Brief | `documents/cloud-grind-agent.md` |
| Latest SHA | `197f4d3c98` |

## Work queue

- [x] W0 — `.cursor/install.sh` / environment (2026-10-06 UTC, Docker + pnpm + iptables FORWARD fix)
- [x] W1 — wp-env PHP **8.4.26** + first full run attempt (bootstrap + env green; initial PHPUnit inventory started)
- [ ] W2 — corpus-smoke testsuite (deferred until W3 stabilizes full suite)
- [ ] W3 — full suite **0F/0E** (in progress — stop-on-failure grind on PHP 8.4 curation regressions)
- [ ] W4 — per-testsuite verification
- [ ] W5 — JUnit artifact from green run
- [ ] W6 — README-corpus + command docs

## Log

- **2026-10-07 ~02:55 UTC** — W3: SOF past **~2920** / 9753 (`16171ec4a1`). Fixes: onboarding hook guards, admin reports/coupons/orders-stats dates, fulfillments datastore + table bootstrap, `StoredUrl` casts, `WC_Emails`/`WC_Admin_Reports` array hooks, `WC_Checkout` null customer, `WC_Helper` REQUEST_URI casts, install schema test isolation, Action Scheduler test helper cap/purge (unblocks SOF ~1952 stall), `WC_Data_Store::read_multiple` callable check.
- **2026-10-06 ~22:38 UTC** — W0/W1: Added `.cursor/install.sh`, `start.sh`, `environment.json`; `plugins/woocommerce/.wp-env.corpus.json` (PHP 8.4); `bin/corpus-wp-env-start.sh` (iptables FORWARD for DinD). wp-env starts on PHP 8.4.26. PHPUnit lists **9752** tests; bootstrap green after Container/test-stub fixes. W3: fixing runtime TypeError/signature failures (e.g. `wc_format_decimal`, `LookupDataStore`, `ObjectCache`, order items).
- **2026-10-06 ~22:40 UTC** — W3: Stop-on-failure past test ~13 (`wc_hex_darker`/`dechex` int cast). Full suite run in progress on branch tip `e8d351df08`.
- **2026-10-06 ~23:05 UTC** — W3 resume: stop-on-failure past ~400 tests (`b2d25bfca8`). Fixes include checkout shipping methods, coupon/order item types, analytics product sync `round()`, mobile messaging blog id, `wc_let_to_num`, CSV export encoding.
- **2026-10-06 ~23:08 UTC** — W3: stop-on-failure past **~514** tests (`5e48ca8b8b`). Additional fixes: log handler `wp_mail`, `wc_make_numeric_postcode`, PayPal API URL `strstr`, order item `calculate_taxes` signatures, legacy `WC_Order_Item_Meta` item type.
- **2026-10-06 ~23:20 UTC** — W3 resume: stop-on-failure past **~900** tests (`f8a4c7e093`). Notable fixes: `WC_Comments` filter removable callback, order coupon/privacy assert fixes, `ObjectCache::is_cached`, variable product version invalidation, wc-admin `legacy-settings` REST args.
- **2026-10-07 ~00:35 UTC** — W3: SOF past **~2320** tests (`915a9ac56c`). wc-admin WP_Query filter array callables; onboarding profile `update_option` hooks tolerate non-array; Experimental_Abtest + payments volume mock fixes.
- **2026-10-07 ~00:15 UTC** — W3: SOF past **~1946** tests (`9b8ffdbc6c`). Admin reports: `TimeInterval`/`Segmenter`/`stdClass` types, export `microtime` cast, customers `DataStore` array hooks, performance indicators `WC_DateTime`. **Flake note:** `Product_Variations_API_V2::test_product_variations_batch` may fail ~test 1000 in full SOF (external image) but passes isolated.
- **2026-10-07 ~00:05 UTC** — W3: SOF past **~1833** tests (`647da929b3`). REST terms `attribute_id` `str_replace` string cast; continued hook singleton / onboarding fixes from prior commits on branch.
- **2026-10-06 ~23:50 UTC** — W3 resume from `f40924c92c`: SOF past **~1798** tests (`8767fc67c7`). Fixes: v3 order coupon `number_format`, settings/query/deprecated hook array callables, `wc_get_logger` singleton, onboarding homepage image fallbacks.
- **2026-10-06 ~23:38 UTC** — W3: stop-on-failure past **~1127** tests (`b140e93e7a`). v3 customer REST date assertions aligned with `WC_DateTime`.
- **2026-10-06 ~23:35 UTC** — W3: stop-on-failure past **~1076** tests (`479e066f93`). Fixes: REST `str_replace` int route params (variations + CRUD pagination), restore v2 product/variation `delete_item` success path, `WC_Brands` REST query `WP_REST_Request` type, shipping zone setting text cast for `stripslashes`.
