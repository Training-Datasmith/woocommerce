# Cloud agent brief — WooCommerce corpus PHPUnit (autonomous)

Paste this entire file into a **cloud** Task (`environment: cloud`, `cloud_base_branch: remote-test-*`). The agent must **not** stop after inventory or a partial green slice to ask whether to continue.

---

## Authority

- **Spec:** `documents/test-suite-plan.md` (decisions in §8 are fixed).
- **Repo:** `Training-Datasmith/woocommerce` monorepo; plugin under `plugins/woocommerce/`.
- **Branch:** `remote-test-YYYY-MM-DD` (already pushed). Push all fixes to **this branch only**; no force-push; preserve LF; corpus curation only (no deleting tests to pass).

## Execution policy (mandatory)

1. **Run until finished** per [Finished](#finished) below — not until “Phase 0 done” or “first JUnit with fewer failures.”
2. **Never ask the user** if you should continue, start the next phase, or resume after a milestone. Assume **yes** for every row in [Work queue](#work-queue).
3. **If a session or turn limit approaches:** commit + push progress, append checklist to `documents/cloud-grind-status.md`, then **keep working** in the same run if possible; only end when the platform stops you — next resume reads status and continues **without asking**.
4. **Fix loop:** minimal diffs → re-run same scope until **0 failures, 0 errors** (skips OK if documented). JUnit with failures/errors is not completion.
5. **Do not** return a summary that only lists “recommended next steps” while work remains in the queue.

## Environment

- Monorepo root is the git root (`woocommerce/`).
- Create/use `.cursor/install.sh` in **W0**: Node **20+**, **pnpm@9**, **Docker**, enable Docker daemon, `pnpm install` at repo root.
- Plugin tests: `cd plugins/woocommerce` or `pnpm --filter=@woocommerce/plugin-woocommerce` scripts.
- **wp-env:** `pnpm env:dev` or `wp-env start --update`; target **PHP 8.4** in tests container (override `.wp-env.json` via `.wp-env.corpus.json`, `.wp-env.override.json`, or inline change documented in status — prefer committed `.wp-env.corpus.json` + env var if upstream supports it).
- PHPUnit: `pnpm test:php:env` (runs in wp-env **tests-cli**). Raw equivalent: `sh ./client/blocks/bin/copy-blocks-json.sh && wp-env run --env-cwd='wp-content/plugins/woocommerce' tests-cli vendor/bin/phpunit -c phpunit.xml --verbose`.

## Work queue (in order — complete all unless [Blocked](#blocked))

| ID | Work | Done when |
|----|------|-----------|
| W0 | `.cursor/install.sh` (+ optional `.cursor/environment.json`): Node, pnpm, Docker, `pnpm install` | Docker runs; `pnpm --filter=@woocommerce/plugin-woocommerce exec wp-env --version` works |
| W1 | wp-env **tests** env on **PHP 8.4**; first full `pnpm test:php:env` | JUnit/log captured; failure count documented in `cloud-grind-status.md` |
| W2 | Optional `phpunit-corpus.xml.dist` with **corpus-smoke** testsuite (<600s) if full suite is too heavy for host | Smoke command documented; smoke **0F/0E** OR documented deferral with reason |
| W3 | Fix all PHPUnit **failures and errors** on training branch (plugin + tests) | `pnpm test:php:env` → **0F/0E** full `phpunit.xml` suites |
| W4 | Re-run legacy + main testsuites separately if needed to isolate regressions | Both suites **0F/0E** |
| W5 | Artifacts: `plugins/woocommerce/phpunit-remote-YYYYMMDD.xml` (or repo root) from green full run | File committed or SHA noted for host `git show` |
| W6 | `plugins/woocommerce/tests/README-corpus.md` + update `documents/test-suite-plan.md` §4 commands | Host can copy exact cloud commands |

Update `documents/cloud-grind-status.md` after each W-row (checkbox + UTC date + commit SHA).

## Finished

Stop **only** when **all** are true:

- W0–W6 complete.
- **M1** from test-suite-plan §7: full PHPUnit **0F/0E** on PHP **8.4** Linux.
- **M2:** JUnit artifact from green run.
- Latest commit **pushed** to `remote-test-*`.

Report: test counts, skips, PHP version in container, wp-env image versions, branch SHA, total runtime.

## Blocked

Stop early **only** if a hard external blocker remains after reasonable effort (document in `cloud-grind-status.md`):

- Docker unavailable on cloud VM and no alternative.
- wp-env cannot start tests DB after documented troubleshooting.
- Upstream test requires unavailable binary extension with no apt package.
- Failure requires rewriting upstream architecture (out of corpus scope).

**Not** blocked: large test count, long runtime, many commits, needing monorepo build before tests.

## Parent agent (host) — do not micro-manage

- Launch **one** cloud Task with this brief + `run_in_background: true`.
- Do **not** send follow-up “continue?” messages; use **Task resume** only if the platform ends the run before [Finished](#finished).
- Record baselines from remote JUnit after Finished when the user asks (`reports/woocommerce/`, `test-baselines.tsv`).

## Useful filters (during W3)

```bash
cd plugins/woocommerce
pnpm test:php:env -- --testsuite wc-phpunit-main --stop-on-failure
pnpm test:php:env -- --testsuite wc-phpunit-legacy --stop-on-failure
pnpm test:php:env -- --filter 'SomeTestClass' --verbose
```

Preserve LF line endings on edited PHP/MD/JSON/YML files.
