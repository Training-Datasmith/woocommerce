# Cloud grind status — WooCommerce corpus PHPUnit

| Field | Value |
|-------|--------|
| Branch | `remote-test-2026-10-06` |
| Spec | `documents/test-suite-plan.md` |
| Brief | `documents/cloud-grind-agent.md` |
| Latest SHA | `cd0f4945f8` |

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
