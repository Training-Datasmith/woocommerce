# WooCommerce monorepo — corpus PHPUnit plan

Planning document for the **Training-Datasmith** fork (`ai-training-20260418`). Goal: a **documented, matrix-runnable** PHPUnit story for the WooCommerce plugin (`plugins/woocommerce/`) with **0 failures, 0 errors** on PHP **8.4**, recorded in `configuration/test-baselines.tsv` when green.

Unlike greenfield ecommerce forks (e.g. osCommerce), Woo **already ships** a large upstream test tree. This plan **curates and stabilizes** that suite for the training corpus—not a rewrite of Woo’s CI matrix (Playwright, Jest, k6, multi-env e2e).

**Matrix today:** `woocommerce` Tier **C**, `runnable=no`, notes **`no composer.json`** (monorepo scanner looks at repo root; real manifest is `plugins/woocommerce/composer.json`).

---

## 1. Objectives

| Objective | Definition of done |
|-----------|-------------------|
| **Corpus PHPUnit** | Full plugin PHPUnit (`wc-phpunit-legacy` + `wc-phpunit-main`) runs via **wp-env** on **Linux cloud** (canonical) with **0F/0E** on PHP **8.4** at training-branch SHA. |
| **Host path** | Documented command path for sweeps; optional **tier-1 smoke** testsuite under **600s** for host matrix (`runnable=maybe` → `yes`). |
| **Corpus integration** | `documents/` plan + grind brief; `.cursor/install.sh` for cloud; JUnit artifact `phpunit-remote-YYYYMMDD.xml`; matrix notes cite `plugins/woocommerce` + `pnpm test:php:env`. |
| **PHP 8.4** | Training branch fixes for deprecations/fatals in plugin + tests (minimal curation diffs). |

**Non-goals (v1):** Playwright e2e (`tests/e2e-pw/`), block-library Jest, performance k6, full monorepo `pnpm test`, WooCommerce.com live APIs, publishing `ai-training-*` to origin unless the user asks.

### 1.1 Dependency management

- **Production/runtime:** unchanged (WordPress + plugin; monorepo `pnpm` build graph).
- **PHPUnit:** existing `plugins/woocommerce/composer.json` + `vendor/` via plugin `postinstall` / `composer install` inside wp-env **tests-cli** container.
- **Do not** add a fake root `composer.json` only for matrix optics—fix **`build-test-matrix.php`** / notes to point at `plugins/woocommerce/composer.json` when promoting `runnable`.
- **wp-env:** Docker-based; canonical grind on **Linux cloud** with Docker available.

---

## 2. Current state (inventory)

### 2.1 Repository shape

- **Monorepo root:** `package.json` (`pnpm@9`), packages under `packages/`, plugin under `plugins/woocommerce/`.
- **Plugin entry:** `plugins/woocommerce/woocommerce.php`; code in `includes/`, `src/`, built assets under `assets/client/`.
- **Training branch:** `ai-training-20260418` (Rector, CS-Fixer, PHPStan curation on plugin PHP).

### 2.2 Existing tests (upstream)

| Asset | Notes |
|-------|--------|
| `plugins/woocommerce/phpunit.xml` | Testsuites `wc-phpunit-legacy`, `wc-phpunit-main`; bootstrap `tests/legacy/bootstrap.php`. |
| `plugins/woocommerce/tests/php/` | Modern PHPUnit tests (PSR-4 style paths). |
| `plugins/woocommerce/tests/legacy/unit-tests/` | Legacy WP-style tests. |
| `plugins/woocommerce/.wp-env.json` | Default **PHP 8.1**; tests env on port **8086**; lifecycle `test-env-setup.sh`. |
| `pnpm test:php:env` | Runs PHPUnit inside wp-env **tests-cli** (see `package.json`). |
| `.ai/skills/.../running-tests.md` | Upstream dev docs (Docker, filters, troubleshooting). |

**Gaps for corpus:** No Training-Datasmith **`documents/`** grind story; matrix **`runnable=no`**; no **PHP 8.4** wp-env profile for baselines; unknown **0F/0E** count on training branch; no **`phpunit-remote-*.xml`** artifact or tiered **smoke** suite for 600s host cap.

---

## 3. Target architecture

One upstream PHPUnit config (extend, don’t fork blindly), optional **corpus overlay** for smoke + JUnit, one **wp-env** install path.

```text
                    ┌─────────────────────────────────────────┐
                    │  plugins/woocommerce/phpunit.xml        │
                    │  (+ optional phpunit-corpus.xml.dist)   │
                    └──────────────────┬──────────────────────┘
                                       │
          ┌────────────────────────────┼────────────────────────────┐
          ▼                            ▼                            ▼
   Monorepo pnpm install        wp-env (Docker)              Linux cloud agent
   from repo root               tests-cli + MySQL             (canonical baseline)
          │                            │
          ▼                            ▼
   pnpm --filter=@woocommerce/    vendor/bin/phpunit
   plugin-woocommerce test:php:env   -c phpunit.xml
```

### 3.1 Execution contexts

| Context | When | Command (from repo root unless noted) |
|---------|------|----------------------------------------|
| **Cloud grind (canonical)** | Full corpus baseline, PHP 8.4 | See `documents/cloud-grind-agent.md` |
| **Plugin dir (local dev)** | Iterating on failures | `cd plugins/woocommerce && pnpm test:php:env [-- filter]` |
| **Host sweep (optional)** | Smoke under 600s | `vendor/bin/phpunit --testsuite corpus-smoke` (after W2) |

### 3.2 wp-env / WordPress bootstrap

- **Install path:** `pnpm install` at monorepo root → `pnpm --filter=@woocommerce/plugin-woocommerce env:dev` (or `wp-env start --update`) from `plugins/woocommerce`.
- **PHP 8.4:** Corpus override via `.wp-env.override.json` (gitignored locally) **or** committed `.wp-env.corpus.json` + documented copy/symlink for cloud—agent chooses one approach in W1.
- **Tests bootstrap:** Existing `tests/legacy/bootstrap.php` + `WP_TESTS_DIR` inside container (unchanged unless fatals require minimal corpus fixes).

### 3.3 Tiering (corpus)

| Tier | Scope | Target runtime | Matrix |
|------|--------|----------------|--------|
| **T1 smoke** | Curated `@group corpus-smoke` or dedicated testsuite | < 600s host | `runnable=maybe` |
| **T2 full PHPUnit** | Both phpunit testsuites | Long (CI-scale) | Cloud baseline; notes in matrix |
| **T3 e2e** | Playwright | Out of v1 | Not in baselines |

---

## 4. Tooling and repository layout (proposed)

```text
woocommerce/
  documents/
    test-suite-plan.md          # this file
    cloud-grind-agent.md        # autonomous cloud brief
    cloud-grind-status.md       # agent progress (created by grind)
    coverage-exclusions.md      # if corpus adds coverage gates
  plugins/woocommerce/
    phpunit.xml                 # upstream (keep)
    phpunit-corpus.xml.dist     # optional smoke + JUnit-friendly (W2)
    .wp-env.json                # upstream
    .wp-env.corpus.json         # PHP 8.4 + corpus flags (W1, optional)
  .cursor/
    install.sh                  # cloud: node, pnpm, docker, wp-env deps (W0)
  tests/README-corpus.md        # short pointer under plugin (W6)
```

**Matrix promotion path:**

1. **`runnable=maybe`** when cloud (or host with Docker) runs **T1 smoke** **0F/0E**; notes: `plugins/woocommerce/composer.json`, command `pnpm --filter=@woocommerce/plugin-woocommerce test:php:env`.
2. **`runnable=yes`** when **T2 full PHPUnit** **0F/0E** on **Linux cloud** @ PHP 8.4; baseline row in `test-baselines.tsv`.
3. Update **`build-test-matrix.php`** heuristic for monorepos (detect `plugins/woocommerce/composer.json`)—host tooling change, separate commit in `configuration/` when user requests.

---

## 5. Phased delivery

### Phase 0 — Foundation (agent W0–W1)

- `.cursor/install.sh` + environment notes (Node 20, pnpm, Docker, PHP extensions for wp-env).
- wp-env starts on cloud; **PHP 8.4** for tests container.
- First full `pnpm test:php:env` run → inventory of failures/errors (JUnit log).

### Phase 1 — Stabilize training branch (agent W3)

- Fix **minimal** corpus diffs on `remote-test-*`: plugin code, test bootstrap, or test expectations for PHP 8.4 / HPOS / strict types introduced by curation.
- **Do not** delete tests to green the suite; **do not** skip without documented reason in `coverage-exclusions.md` or test docblock.
- Re-run until **0F/0E** full PHPUnit.

### Phase 2 — Corpus profile (agent W2, W5)

- Add **`phpunit-corpus.xml.dist`** (or testsuite block in main config) for **smoke** subset if full suite exceeds host cap.
- Emit **`phpunit-remote-YYYYMMDD.xml`** from full run for baselines.
- Optional: PCOV coverage report → `build/coverage/` (informational; 100% not required unlike osCommerce OM target).

### Phase 3 — Baseline & matrix (host, when user asks)

- Pull JUnit from remote SHA → `reports/woocommerce/`.
- Update `configuration/test-baselines.tsv` + `test-matrix.tsv` per `remote-phpunit-cloud-grind` postflight.

### Phase 4 — Maintenance

- Re-grind on training branch advances; keep smoke green on host sweeps.

---

## 6. Risks and mitigations

| Risk | Mitigation |
|------|------------|
| Monorepo install weight | Cache pnpm store on cloud; `--filter` plugin-only scripts |
| Docker unavailable on Windows host | Matrix `runnable=no` until Docker; cloud canonical |
| Full suite >> 600s | T1 smoke testsuite; full suite cloud-only |
| wp-env PHP 8.1 default | Corpus override file documented in W1 |
| Upstream test flakiness | Fix or `@group` + documented skip; no silent deletion |
| Live HTTP / payment tests | Use existing Woo test doubles; no live gateway calls |
| Matrix false `no composer.json` | Document path; optional matrix builder fix |

---

## 7. Success metrics

| Milestone | Metric |
|-----------|--------|
| M0 | wp-env + `pnpm test:php:env` completes on Linux PHP 8.4 (any result logged) |
| M1 | Full PHPUnit **0F/0E** on cloud @ training SHA |
| M2 | `phpunit-remote-*.xml` artifact from green run |
| M3 | T1 smoke **0F/0E** < 600s (optional host) |
| M4 | `test-matrix.tsv`: `runnable=yes` + baseline SHA (host updates when user asks) |

---

## 8. Decisions (owner)

| # | Topic | Decision |
|---|--------|----------|
| 8.1 | Canonical environment | **Linux cloud** + Docker wp-env |
| 8.2 | Test scope v1 | **PHPUnit only** (legacy + main testsuites) |
| 8.3 | Upstream vs new tests | **Fix/stabilize existing**; add tests only for untested curation regressions |
| 8.4 | PHP version | **8.4** for corpus baselines |
| 8.5 | Branch policy | Grind on **`remote-test-*`** pushed; **`ai-training-*`** local unless user publishes |

---

## 9. References

- osCommerce corpus pattern: `../oscommerce/documents/test-suite-plan.md`, `cloud-grind-agent.md`.
- Matrix policy: `../tools/test-framework-setup.md`.
- Cloud grinds: `~/.cursor/skills/remote-phpunit-cloud-grind/SKILL.md`.
- Woo dev: `plugins/woocommerce/.ai/skills/woocommerce-dev-cycle/running-tests.md`.
- Cursor rule: `.cursor/rules/woo-phpunit.mdc`.

---

## 10. Cloud grind — run to completion

**Owner intent:** one cloud grind implements **§7 M1–M2** (and M3 if feasible) without phase-by-phase approval prompts.

| Role | Behavior |
|------|----------|
| **Cloud agent** | Follow `documents/cloud-grind-agent.md`: queue **W0→W6**, fix loops until **0F/0E**, push to `remote-test-*`, track `documents/cloud-grind-status.md`. Stop at **Finished** or **Blocked**. |
| **Host / parent agent** | Preflight (§10.1), launch Task with `environment: cloud`, `run_in_background: true`, brief attached. No “continue?” between phases. |

### 10.1 Host preflight (before first Task)

1. Commit `documents/` on `ai-training-20260418` (plan + grind brief).
2. Create/push `remote-test-YYYY-MM-DD` from training tip (cloud clones remote).
3. Ensure latest PHP 8.4 curation fixes are on that pushed branch.

### 10.2 After Finished (host, when user wants baselines)

- Fetch JUnit from remote SHA; `reports/woocommerce/`; update matrix/baselines per skill postflight.

---

*Last updated: 2026-10-06 — corpus planning on `ai-training-20260418`.*
