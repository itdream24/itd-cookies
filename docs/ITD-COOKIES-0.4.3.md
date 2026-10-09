# ITD Cookies 0.4.3 — Production Hardening & Compatibility

Date: 2026-10-09. Candidate: **0.4.3-dev.1**.
Branch: `fix/itd-cookies-0.4.3-production-hardening`.
Base after fresh fetch: `97f7930a4caeaee8bd2989ed2346a2d3293162f5` (`origin/main`).
Research source: `4bc5fa007a1c2bd836bf10db009a080126c1c47f`; only four rollout
documents and the guard scenario were brought forward. The research branch was
not merged. Published stable remains **v0.4.2**; no tag, Release or main changes.

## Current verdict

**NOT_READY_FOR_ITD_COOKIES_0_4_3_RELEASE** — browser acceptance and full CI are
pending. This is an implementation checkpoint, not a claim of release readiness.

Browser candidate installation has not occurred. A computer-use action-time
confirmation is pending for the exact ZIP, temporary reference fixture/observer,
synthetic settings, theme matrix and complete baseline restoration.

## Three P2 fixes

1. **Bootstrap:** `itd_cookies_allowed()` is declared conditionally after runtime
   classes load. On refused bootstrap `function_exists` and `is_callable` are
   false. Callers must check availability; missing API must never imply consent.
   Existing PHP/Core notices and supported consent behavior are preserved.
2. **Updater:** only this basename is removed/replaced on update-record writes.
   A late read filter also rejects persisted foreign records without HTTP.
   Strict stable version, repository ID, slug, basename and canonical GitHub
   built-asset URL must agree. Negative cache/no usable release cannot leave a
   WordPress.org/foreign fallback. Details failure returns `WP_Error` so Core
   cannot fall back to a same-slug directory package. Other plugin records remain
   unchanged. Valid metadata TTL remains 21600 seconds, failure TTL 900 seconds;
   existing manual two-cache invalidation and request reuse are retained.
   This does not protect against arbitrary third-party code bypassing Core hooks.
3. **CSS:** scoped action-button selectors and state colors outrank theme button
   rules. Toggles are scoped too. No new `!important`; no global theme styles or
   font-family override. Focus uses the same accent with a 3px outline. Forced
   colors use Canvas/ButtonFace/ButtonText/Highlight and native checkbox rendering.
   Numeric contrast and actual hover/focus/active/forced-colors browser evidence
   remain pending; CSS inspection alone is not visual acceptance.

Consent, five provider integrations, Script Adapters API/engine and UI behavior
were not refactored. The updater adds one translated failure message (97 compiled
Russian messages). Candidate version is synchronized in header/constant,
package.json/lock, readme and translation metadata. Dependencies were not changed.

## Compatibility decision

The new official Core/PHP line starts with **WP 5.3 / PHP 7.4**. Core 5.0–5.2 is
outside the official supported-combinations policy. PHP 7.4 is an EOL compatibility
minimum, not a recommendation for new production. Prefer current Core and a PHP
version with security support; the local primary baseline itself uses legacy
PHP 8.1 and is QA evidence, not a hosting recommendation.

**Header/readme/updater metadata and runtime guard remain WP 5.2 / PHP 7.4.**
Raising the guard immediately would stop an already functioning consent engine
on WP 5.2. Local WP 5.2.24/PHP 7.4 regression demonstrated activation and runtime
operation; it does not make that Core/PHP combination officially supported.
Owner recommendation: inventory/migrate existing WP 5.2 sites first, then approve
a separate floor change with an explicit warning and migration window.

References: [official Core/PHP matrix](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/),
[PHP EOL](https://www.php.net/eol.php),
[Update URI since WP 5.8](https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/),
[Upload/Replace since WP 5.5](https://make.wordpress.org/core/2020/07/29/miscellaneous-developer-focused-changes-in-wordpress-5-5/).

## Executed local gates

| Check | Actual result |
| --- | --- |
| PHPUnit / PHP 7.4.33 | PASS — 53 tests, 591 assertions |
| JavaScript tests / Node 22.22.2 | PASS — 42 tests |
| ESLint | PASS |
| PHPCS | PASS — 22 files, no errors/warnings |
| PHPStan | PASS |
| PHPCompatibilityWP (7.4+) | PASS |
| Translation build | PASS — 97 messages |
| npm audit | PASS — 0 vulnerabilities |
| Composer audit --locked | PASS — no advisories |
| Gitleaks 8.30.1 git | PASS — 50 commits, no leaks |
| Gitleaks project files | PASS — tracked + non-ignored new files, no leaks |
| Gitleaks entire development folder | 5 findings in ignored vendor/phpstan/phpstan/phpstan.phar; dependency artifact, not project/package source. Report redacted; values not exposed |
| Build twice / package inspector / PHP syntax | PASS — identical SHA, 18 production files |

Initial PHPUnit run failed because the new global WP_Error double collided with
existing Mockery Script Adapters tests. The double now loads only inside an
isolated updater test process; the complete suite was rerun PASS. Integration
manual-refresh fixtures were updated to use a valid canonical stale package and
isolate both read/write hooks when simulating an older installed version. Native
real-WordPress regressions were rerun after those test fixes.

### Actual isolated WordPress CLI tests

All six pre-existing isolated sites were verified unchanged before testing;
their original databases and pristine wp-content were restored after testing.

- WP **5.0** and **5.0.30**, PHP **7.3.33 / 7.4.29**: guard-only PASS, expected
  notice, no runtime classes/shortcode/options mutation, consent API absent.
- WP **5.2.24 / 7.4.29** (regression-only) and **5.3.26 / 7.4.29**: native ZIP
  install/activation, migration, defaults/UI/Russian translation, reactivation
  persistence PASS.
- Both functional sites: synthetic same-basename collision success, timeout,
  HTTP 500, malformed JSON, invalid metadata and no usable release PASS. Other
  records preserved; one controlled GitHub call per case; cached reads zero HTTP;
  details failure is WP_Error. Persisted foreign cache is rejected on read.
- Both: controlled native manual-refresh ordering/two-cache invalidation/one
  GitHub request PASS. Ordinary valid cache preserved. These are mocked API tests.
- Both: real native Plugin_Upgrader using a synthetic newer metadata version and
  the unchanged candidate ZIP PASS; invalid ZIP/network/timeout leave installed
  files/settings intact. This is not discovery of a public candidate release.
- Both: Script Adapters zero/supported/missing handle/dependency/cycle/ownership/
  ancillary/unprinted/late and benchmark PASS.
- Old Core emits its own deprecated `get_magic_quotes_gpc`, curly-brace offset
  and legacy theme notices. No Core shims or PHP 7.3 support were introduced.

### Public API versus browser evidence

Anonymous public REST read returned **v0.4.2**, draft=false, prerelease=false.
This verifies the reference release only. Real WordPress browser Check again,
real request count/TTL, controlled browser failure, consent/five providers,
legal/policy/settings, keyboard and three-theme contrast matrix: **NOT_RUN**.
No real WordPress.org slug collision is claimed.

## Package

`itd-cookies-0.4.3-dev.1.zip`

SHA-256: `793c5b98c38f86ecd84599d494a2e4d77f5f229aa875da371130f05df9ec587b`

Root `itd-cookies/`; main file/updater/local assets/Russian PO+MO included.
No docs/tests/private fixtures/observers/vendor/node_modules/dev manifests.
Two builds with fixed archive timestamp/ordering/permissions are identical.

## CI configuration (result pending)

Mandatory total **13 jobs**, not 9: PHP 7.4/8.5 (2), quality, node,
WordPress 5.2/5.2.24/5.3.26 with PHP 7.4 and latest with PHP 7.4/8.5 (5),
WP 5.0 guard-only with PHP 7.3/7.4 (2), Gitleaks, build.
Build waits for all groups; compares two ZIP hashes and runs inspector.
All existing checks preserved; source-collision test runs on each functional WP
matrix job. Gitleaks pins 8.30.1 and verifies official archive checksum before use.

## Baseline / cleanup

Before QA, **baseline-v0.4.2** was actually restored. Subsequent pure DB export
compared schemas/rows of all **12 tables**; live and backup wp-content manifests
matched all **504 files**. Immutable SQL SHA remained
`09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4`.

After CLI QA all six isolated databases (12 tables each) and content matched
their original snapshots. Primary site remains official **0.4.2 ACTIVE**;
its 504 files/all 12 tables/settings/activity matched baseline. No candidate,
fixture, observer, page, counter or synthetic metadata was installed there.
Browser QA cookies/cleanup verification remain pending with Browser E2E.

Timeweb/testwp/production QA requests or mutations: **0**. No merge, tag or Release.
Published v0.4.2 and earlier assets remain unchanged.
