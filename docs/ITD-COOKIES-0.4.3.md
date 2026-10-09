# ITD Cookies 0.4.3 — Production Hardening & Compatibility

Historical implementation record (superseded by the stable release section below).

Date: 2026-10-09. Candidate: **0.4.3-dev.1**.
Branch: `fix/itd-cookies-0.4.3-production-hardening`.
Base after fresh fetch: `97f7930a4caeaee8bd2989ed2346a2d3293162f5` (`origin/main`).
Research source: `4bc5fa007a1c2bd836bf10db009a080126c1c47f`; only four rollout
documents and the guard scenario were brought forward. The research branch was
not merged. Published stable remains **v0.4.2**; no tag, Release or main changes.

## Current verdict

**ITD_COOKIES_V0.4.3_RELEASED_AND_LOCAL_STABLE_UPDATE_VERIFIED** — stable packaging, release/main CI, publication, real native updater, browser acceptance and exact immutable baseline Restore PASS. Final documentation commit CI is verified separately after push; its URL is included in the final handoff.

The owner explicitly authorized isolated local Playwright, the exact candidate ZIP,
fixtures and cleanup. Earlier pending/interrupted checkpoints below are retained
as history and superseded by the final Browser E2E section.

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

### Public API versus browser evidence — earlier checkpoint

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

## CI — 13/13 PASS

Implementation commit: 9f4d90757195bc81a63c31f438a043959ea2b7d8.
[CI run 37939916826](https://github.com/itdream24/itd-cookies/actions/runs/37939916826): completed, success, **13/13 jobs PASS**.
Latest resolved to WP **7.1.3**, PHP **7.4.33 / 8.5.11**. WP 5.3.26 used PHP 7.4.33. Both WP 5.0 guard jobs passed; all five functional WP jobs passed source collision, manual refresh, native upgrader and adapter regressions.
Linux build inspector reported 18 files and the same SHA-256 as Windows; second build verification passed.

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

## Final Browser E2E attempt — interrupted before candidate installation

Verdict: `NOT_READY_FOR_ITD_COOKIES_0_4_3_RELEASE`.

The accepted commit and approved candidate ZIP SHA were checked unchanged.
Immutable baseline-v0.4.2 was actually restored before this attempt. On the
local official 0.4.2, synthetic settings were saved through the real Settings
API, including the 110% text size, five synthetic provider IDs, four legal
links, consent version and auto footer. A temporary policy page (7), acceptance
page (8), local observer/reference fixture and official test themes were used.
The real Reject button saved a necessary-only consent before attempted upgrade.

Before screenshots were captured for Twenty Twenty-Five and Twenty Twenty at
390x844. The observed normal primary/secondary text colors were white/#0b57d0
and #0b57d0/white: calculated contrast 6.386:1. The old focus indicator was
#7aabff on white: 2.309:1. This attempt did not reproduce the earlier 1.22:1
button state and makes no claim about the candidate's state contrast.
Twenty Twenty visibly restyled the old toggles. A browser ViewTransition
InvalidStateError was recorded on the official baseline; its origin has not
been attributed to ITD Cookies.

Chrome rejected automatic file selection because the ChatGPT extension has no
file-URL access. No install/replace action completed; the approved candidate was
NOT installed. A native Computer Use fallback then stopped this turn because it
could not establish the current browser URL confidently enough for its policy.
All further browser input was stopped. This is an infrastructure/browser-control
block, not a failed candidate runtime test. Runtime code was not changed.

| Final browser scenario | Actual status |
| --- | --- |
| Candidate native ZIP upgrade and preservation | NOT_RUN |
| Candidate three-theme colors/states/contrast | NOT_RUN |
| forced-colors: active | NOT_RUN |
| Candidate four-width responsive/keyboard matrix | NOT_RUN |
| Candidate consent/providers/adapters regression | NOT_RUN |
| Browser real GitHub refresh and controlled collision | NOT_RUN |
| Exact server Restore and artifact cleanup | PASS |
| Browser QA consent-cookie removal and final reload | NOT_RUN |

Cleanup used the existing Restore helper, then a pure database import/export
and schema/row comparison. All 504 live wp-content file hashes and all data in
12 tables matched baseline. Official 0.4.2 ACTIVE, settings, marker, theme and
plugin activity were verified by Restore. Temporary plugins, observer, pages,
settings, counters and metadata were removed by restoration. All six isolated
sites also matched their existing pristine content and original 12-table dumps;
they were not used for this browser attempt. The immutable baseline SQL SHA
remains 09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4.

A test consent cookie was written in Chrome; it cannot be claimed removed after
Computer Use stopped. Clear only itd_cookies_consent, itd_modubricks_consent and
itd_cookies_legacy_migrated for itd-cookies.local before resuming browser QA.
No optional provider SDK was started by the baseline Fresh/Reject scenarios.

Private evidence directory: 043-hardening in the existing QA artifact root.
Before images: before-042-tt25-390-banner.jpg,
before-042-tt25-390-settings.jpg, before-042-tt20-390-banner.jpg,
before-042-tt20-390-settings.jpg, before-042-tt20-focus.jpg.
JSON: pre-upgrade.json, consent-before-upgrade.json, before-042-tt20-focus.json.
No candidate after image or A/B PASS is claimed.

Report saved locally; no commit/push was made because final acceptance is still
pending. The previously green 13/13 CI belongs to accepted commit 8210c6f, not
to a new browser-acceptance report commit. No main merge, tag, GitHub Release,
remote QA request or production operation occurred.


## Final local Playwright Browser E2E — PASS

Date: 2026-10-09. Tested accepted commit
`8210c6fff0e2e320e20700718e504876969eb4b3`.

The owner explicitly requested standalone local Playwright. This uses the
browser-tool exception for explicitly requested alternate technologies and the
repository LOCAL-FIRST policy. No Computer Use barrier, hostname/IP, proxy or
access policy was changed. Installed **Google Chrome 154.0.8037.98** (Chromium),
**Playwright 1.62.1**, headless rendering, dedicated non-persistent contexts.
No connection to the user's Chrome profile or its authenticated session.
WordPress 7.1.2 / PHP 8.1.5 / ru_RU; three official themes:
Twenty Nineteen 3.4, Twenty Twenty 3.2, Twenty Twenty-Five 1.5.

### Exact candidate / native browser upgrade

- Filename: `itd-cookies-0.4.3-dev.1.zip`.
- SHA-256: `793c5b98c38f86ecd84599d494a2e4d77f5f229aa875da371130f05df9ec587b`.
- Browser Upload/Replace installed this ZIP over official 0.4.2; the native
  WordPress success screen was captured. No direct runtime-file replacement.
- All **18** installed production file hashes matched the approved ZIP.
- Active state, complete synthetic settings (including 110%, five provider IDs,
  four legal links and consent version), necessary-only consent cookie, migration
  marker `fresh-v1` and policy page ID 7 were preserved.
- Runtime code, candidate ZIP and dependencies were not modified during QA.

### Executed scenarios

| Scenario | Result / actual evidence |
| --- | --- |
| Official 0.4.2 → candidate Upload/Replace | PASS — settings/consent/marker/policy/activity preserved |
| Three themes × 320/390/768/1440 | PASS — 12 combinations, 110% text, no horizontal overflow |
| Primary/secondary normal, hover, focus-visible, active | PASS — 288 measured candidate states; hover/focus/active flags verified |
| Boundaries, toggle knob and keyboard focus | PASS — enabled controls exceed 3:1; focus visible |
| Long list of 24 synthetic functional services | PASS — categories scroll; one action set stays visible without overlap |
| Fresh / Reject / Customize / Cancel / Escape / Tab / Shift+Tab | PASS — initial choice stays pending, modal Tab wraps, reopened Escape returns focus |
| Analytics / Marketing / Accept all / Save / reopen / reload / revoke | PASS — persistent choice, revoke reload without optional SDK |
| Five actual provider SDK requests | PASS — Yandex/GA4/GTM/Clarity after analytics; Meta after marketing; none before consent or after Reject |
| Provider and adapter deduplication | PASS — repeated consent event adds no SDK tags or replay executions |
| Admin save, rejected HTTP validation, four legal destinations | PASS — readable error, prior valid URL retained; actual documents open |
| Cookie Policy / three shortcodes / automatic footer | PASS — settings control, legal links and current policy rendered |
| Script Adapters zero / supported / missing handle/dependency / cycle | PASS — correct diagnostics, independent group unaffected |
| Adapter 404 / timeout / ancillary / unprinted / late | PASS — failure codes, dependency skipped, no duplicate execution |
| Adapter ownership | PASS — GA4 conflict reports FAILED_OWNERSHIP_CONFLICT; conflicting SDK absent |
| CSP nonce/hash | PASS — dependency order retained, no CSP violations |
| JS exceptions | PASS — 0 pageerror events in main and follow-up runs |
| Unexpected PHP fatal | PASS — rendered test routes/observer evidence, errors=[] and last_error=null |
| Real GitHub metadata via native Check again | PASS — 1 real HTTP 200; current public v0.4.2; both stale caches invalidated |
| Ordinary metadata cache / no downgrade | PASS — 0 additional GitHub calls, unchanged timeout, six-hour TTL; no update to older v0.4.2 offered |
| Synthetic collision failure/cache + unrelated records | PASS — browser controlled timeout plus native isolated success/timeout/500/malformed/invalid/no-release/cache regressions |
| Exact Restore / local artifact cleanup | PASS — 504 files and all data/schemas of 12 tables |

Viewport heights: 320×568, 390×844, 768×1024, 1440×900. Disabled action
buttons are not present (N/A). Always-on necessary and unused functional toggles
are disabled; disabled controls are not counted as interactive contrast gates.
Enabled off-toggle #68758a/white was measured separately; both boundary and knob
contrast exceed 3:1. Checked controls use #0b57d0/white.

### Computed contrast table

All four widths and all four action-button states have the following colors.
Full per-element/state values, font sizes and pseudo-state flags are in the
private `playwright/contrast.csv` (288 candidate rows plus the old reference).

| Theme | Primary text / background | Secondary text / background | Minimum text ratio | Button border / white | Focus outline / white | Result |
| --- | --- | --- | --- | --- | --- | --- |
| Twenty Nineteen | #ffffff / #0b57d0 | #0b57d0 / #ffffff | 6.386:1 | 6.386:1 | 6.386:1 | PASS |
| Twenty Twenty | #ffffff / #0b57d0 | #0b57d0 / #ffffff | 6.386:1 | 6.386:1 | 6.386:1 | PASS |
| Twenty Twenty-Five | #ffffff / #0b57d0 | #0b57d0 / #ffffff | 6.386:1 | 6.386:1 | 6.386:1 | PASS |

Twenty Twenty A/B used official 0.4.2 and the unchanged approved candidate,
390×844, identical plugin settings and the short five-provider content. Old
button text was 6.386:1 in the measured states; the earlier reported 1.22:1
was not reproduced. Old focus #7aabff/white was **2.309:1**; candidate focus is
**6.386:1**. Old theme-restyled toggles were flattened/clipped; candidate toggles
are clearly visible. Other Twenty Twenty toggle buttons retained computed black
text/transparent backgrounds. Automatic theme translation changed their labels
from English to Russian during QA; the color comparison excludes label text.

### Forced colors — emulation, not physical Windows High Contrast

**PASS** for all three themes using Playwright
`page.emulateMedia({forcedColors: 'active'})`; matchMedia was actually true.
Buttons, switch/native checkbox fallback, text and keyboard focus were visible
in screenshots. Extra interaction checked analytics and traversed action focus.
Computed system button colors were black/white (**21:1**), with visible system
Highlight outline and native appearance. Physical Windows High Contrast:
**NOT_RUN**, no physical-OS verification is claimed.

### Console/network limitations and failed QA harness attempts

No unexpected plugin JS exception or CSP violation was observed. Console includes
expected synthetic-ID Yandex CORS/404 responses, the intentional adapter 404, and
the clean site's missing favicon. These are retained in private evidence, not
reported as zero console messages. Provider SDK loading/gating was verified;
successful analytics delivery to real accounts was not tested. No real IDs used.
A timed-out external script can still finish executing after timeout; its
dependent script remained skipped, matching the existing bounded adapter design.

The initial harness used a mismatching Russian native Upload/Replace label; no
candidate installation completed in that attempt. Its QA profile was cleaned.
The corrected native class locator then completed the real browser upgrade.
Initial updater clicks omitted the final period in “Проверить снова.”; follow-up
used the actual native link. Follow-up assertions were corrected to compare TTL
as numeric values (WP option reads can serialize it as string), compare theme
colors independently of translated labels, and check ownership at prepare-time
using the actual FAILED_OWNERSHIP_CONFLICT diagnostic. Original failed harness
results remain preserved. These were QA-script corrections; no runtime was fixed
silently and no candidate defect was found within the requested gates.

### Screenshots and visual observations

48 saved screenshots; private `043-hardening/playwright` evidence contains
`results.json`, `followup.json`, `summary.json`, `contrast.csv`,
`screenshots-manifest.json`, native upgrade success and real/synthetic updater
screens. All three themes have banner, panel and scrolled screenshots at every
requested width. Forced-colors images are named explicitly as emulation.

A/B:
- `before-042-tt20-390-panel.png`
- `after-043-tt20-390-matching-panel.png`

Non-blocking visual follow-up: theme root rem sizing still makes Twenty Twenty
text smaller and Twenty Nineteen text larger; the latter leaves less category
space at 320×568. These are existing theme-dependent typography, not a new
0.4.3 regression. A later UX task may evaluate bounded local text metrics while
keeping the theme font family, and isolate theme decorative heading pseudo-elements.
No typography/layout/runtime changes were made in this acceptance task.

### Final cleanup and profile scope

All dedicated Playwright contexts were cleared with clearCookies(); subsequent
cookies() returned **[]**, then contexts and Chromium were closed. The user's
main Chrome profile was **not accessed or cleaned**. Its earlier consent cookie
from the interrupted Computer Use test may remain; this task explicitly forbids
touching that profile without separate permission.

Actual Restore of immutable baseline-v0.4.2 verified settings, marker, theme and
plugin activity. A subsequent pure DB import/export comparison verified every
schema/row of all **12 tables**; live and backup manifests matched **504 files**.
Temporary fixtures/observer/long-list filter/themes/pages 7–12/options/counters/
controlled metadata were removed by restoration. Official **0.4.2 ACTIVE** is
left installed. Baseline SQL SHA remains
`09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4`.
Six previously isolated environments also matched their prior snapshots; no
browser tests or mutations were performed on them during this acceptance.

Only this report is committed. QA scripts, credentials, cookies, raw network
URLs, screenshots and observers stay outside the repository/package. Final
report-commit CI must be green before returning the readiness verdict.
Timeweb/testwp/production operations: **0**. No main merge, tag or GitHub Release.

### Final click-coverage completion

Four additional real browser scenarios PASS on the exact unchanged ZIP:
Fresh Customize → Back; panel Accept all; reopened Back preserves saved consent
and returns focus; subsequent reload retains consent. This used another isolated
Playwright context and another native WordPress Upload/Replace, followed by full
Restore. `final-clicks.json` records four PASS, zero JS exceptions and empty QA
cookies. `final-panel-actions-success.png` is additional screenshot evidence.
The final post-click Restore again matched all 504 files and all 12 tables;
official 0.4.2 ACTIVE. The earlier 48-screen matrix evidence remains preserved.


## Stable release gate — preparation

Accepted browser commit: 37a1fd55fd91b60cbda96edc4836d7c4f1d23673, CI 37976010185 13/13 PASS. Release branch is created strictly from it. Version 0.4.3 synchronized; WP 5.2/PHP 7.4 activation floor retained. Recommended lower compatibility combination is WP 5.3/PHP 7.4; production should use current security-supported Core/PHP. WP 5.0 support is not declared.

Lockfile correction: the previous broad candidate-version replacement accidentally changed ESLint’s @humanwhocodes/retry range from ^0.4.2 to ^0.4.3-dev.1. Restored ^0.4.2 from the ESLint manifest; all resolved dependency versions/integrities unchanged. No consent schema, adapters, provider list or runtime logic changes. Stable publication and real updater acceptance remain pending.

Stable production ZIP: `itd-cookies-0.4.3.zip`.
Two builds/package inspection with PHP 7.4: PASS, 18 production files.
Stable SHA-256: `09d9ddb4a6b03f13d83875cff1c1cf657f5842a5d9d97683ef09dc8dc654c06d`.
This differs from the accepted dev ZIP hash. Translation build: 97 messages.


## Stable publication and real updater acceptance — 2026-10-09

- Release: [v0.4.3](https://github.com/itdream24/itd-cookies/releases/tag/v0.4.3); latest=v0.4.3, draft=false, prerelease=false.
- Annotated tag commit: `5cca459ddf5626858c2f4266671929661f6ff148`. Accepted implementation: `37a1fd55fd91b60cbda96edc4836d7c4f1d23673`.
- Stable asset: `itd-cookies-0.4.3.zip`, SHA-256 `09d9ddb4a6b03f13d83875cff1c1cf657f5842a5d9d97683ef09dc8dc654c06d`.
- Two local production builds: identical SHA; 18 files, one itd-cookies/ root, updater/ru_RU included, development directories excluded. PHP 7.4 syntax/package inspector PASS.
- Anonymous ZIP/checksum download: PASS; identical to the reproducible local ZIP. Published assets/tags were not rewritten.

### CI and quality

| Gate | Actual result |
| --- | --- |
| [Release branch CI](https://github.com/itdream24/itd-cookies/actions/runs/37978082773) | 13/13 PASS |
| [Separate main CI](https://github.com/itdream24/itd-cookies/actions/runs/37978938196) | 13/13 PASS |
| [Release workflow](https://github.com/itdream24/itd-cookies/actions/runs/37979302048) | 15/15 PASS, including publication |
| Local PHPUnit / JS | 53 tests, 591 assertions / 42 tests PASS |
| PHPCS / PHPStan / PHPCompatibilityWP / ESLint | PASS |
| Composer audit / npm audit / Gitleaks | zero advisories / zero vulnerabilities / zero leaks |
| Translation build | 97 messages, PASS |

CI covers PHP 7.4/8.5, all five WordPress matrix jobs (5.2, 5.2.24, 5.3.26 and latest with PHP 7.4/8.5), both legacy guard jobs, source-collision regressions, quality, node, secrets and reproducible build. Initial local Composer test invocation could not find PHP in PATH; with the PHP 7.4 PATH corrected, the actual tests ran and passed. No runtime change was made for this tooling issue.

### Stable production-format pre-release Browser E2E

Chrome 154.0.8037.98, Playwright 1.62.1, isolated non-persistent contexts; WordPress 7.1.2/PHP 8.1.5/ru_RU on itd-cookies.local only. Native Upload/Replace 0.4.2 → stable 0.4.3 preserved full synthetic settings, consent, fresh-v1 migration marker, legal links, Cookie Policy and ACTIVE state. All 18 installed file hashes matched the stable ZIP.

Three themes (Twenty Nineteen 3.4, Twenty Twenty 3.2, Twenty Twenty-Five 1.5) × 320×568, 390×844, 768×1024, 1440×900: PASS. Measured 288 action states (normal/hover/focus-visible/active). Actual white/#0b57d0 colors gave minimum text/border/focus contrast 6.386:1; toggle boundaries and indicators exceed 3:1. Long 24-service panel scrolls; action area visible and non-overlapping, no horizontal overflow.

Fresh/Reject/Customize/Back/Save/Accept all/reopen/reload/revoke, Tab/Shift+Tab/Escape/focus return, URL rejection and settings save, four legal destinations, policy, all three shortcodes/footer: PASS. Five synthetic provider IDs produced real SDK requests only after their category was allowed; repeated consent events added no SDK tags or adapter executions. Account delivery is not validated with synthetic IDs.

Script Adapters: zero, supported, missing handle/dependency, cycle, 404, timeout, ancillary/unprinted/late, ownership and CSP nonce/hash: PASS with expected diagnostics, dependency order and dedup. Refused-bootstrap API absence and foreign updater/source protection passed the CI guard/collision scenarios. Separate local synthetic success/timeout/500/malformed/invalid/no-release/cache cases preserved other plugin records; they are not represented as real attacks or release discovery.

Forced colors: PASS by Playwright emulation on all three themes (system black/white buttons 21:1, visible focus/native checkbox). Physical Windows High Contrast NOT_RUN. Unexpected JS exceptions/CSP violations: zero. Expected intentional adapter 404/timeouts, favicon and synthetic vendor failures are recorded separately.

One test harness initially used reload() on the force-check URL and incorrectly expected no GitHub request. The corrected ordinary Updates URL test passed with zero additional requests and unchanged six-hour timeout; no plugin fix was required. Original results remain preserved. Additional five follow-up and four actual action-click tests all passed. 50 screenshots plus the final action screenshot are retained privately outside Git/web-root. Selected screenshots were visually inspected. Known non-blocking theme-root typography (small Twenty Twenty / large Twenty Nineteen at 320px, inherited heading decoration) remains a future UX improvement, not a 0.4.3 runtime change.

Pre-release cleanup: actual Restore matched all 504 files and every schema/row of 12 tables; old immutable SQL/hash unchanged.

### Real native stable update — distinct cache scenarios

**Cached old genuine public metadata:** before publication, official 0.4.2 and the capture-only observer obtained v0.4.2 via one real HTTP 200 using the native Check again button. The same WordPress instance, DB cache and browser context were retained through publication; no cache seeding, metadata replacement or HTTP interception. An ordinary page visit correctly reused that still-valid old cache (zero API requests, no offer). Native Check again then invalidated GitHub/update_plugins, made exactly one real HTTP 200, obtained v0.4.3 and offered only the canonical repository ZIP. The native Updates checkbox/button installed official 0.4.3, preserving all settings, consent, marker, policy, legal links and activity.

**Ordinary discovery:** independently restored immutable baseline-v0.4.2 had its original expired real public cache (original expiry timestamp 1791561663). Ordinary admin/Updates discovery obtained real v0.4.3 without force-check or manual cache clearing. The native Updates button installed it again. This verifies cache-expiry/cache-miss ordinary discovery separately from cached manual refresh; no artificial waiting or synthetic metadata is claimed.

Published runtime file comparison: all 18 entries match the anonymous official asset. Published consent UI/reload, adapter public API/dependency order/dedup and 390/1440 screenshots PASS. Installed settings/consent hashes and before/after observer traces are private evidence. No Upload/Replace or assisted recovery was used for these real release updates.

### Final cleanup / immutable baseline-v0.4.3

The second native update used restored clean default settings; temporary reference plugins/pages/provider IDs/counters from the first scenario were removed by the intervening full Restore. The passive observer/evidence option and QA cookies were then removed. No fixture/helper/synthetic metadata remains. Clean official **0.4.3 ACTIVE**, default Twenty Twenty-Five/ru_RU. All user Chrome profiles were untouched; only independent QA contexts were cleared (cookies()=[]). An older user-profile consent cookie from an earlier task may still exist and was not accessed.

New immutable snapshot created outside Git/web-root and actually restored: **504 files, 12 tables**, all file hashes and all schemas/INSERT rows identical; settings, marker, active plugins, theme and locale equal.

- SQL SHA-256: `e04288464b2ac2c70aa3343c64230656faa28159cb4f9ae4bf4f6434f3aa4708`
- File manifest SHA-256: `241de2f533c82434ce9a284ac8dd3e64bd4c4098a2dc900f1fa3e4a47d8460ee`
- Canonical database SHA-256: `64bf751d605579d40456b390dd576aed2f4e0c2689e4cac24c3776a7a686f287`
- Old baseline-v0.4.2 SQL remains `09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4`; old manifest `b33e88a1e3b2330316563be0366001f1ba02078a57f72b3ed1571e5ae344a002`. Older snapshots were not overwritten.

### Production readiness and limits

Metadata/activation floor remains WP 5.2 / PHP 7.4 to preserve existing installs. Recommended lower technical combination: WP 5.3 / PHP 7.4; new production should use security-supported Core/PHP. WP 5.0 support is not declared. No current stable browser acceptance on legacy WP 5.3 is claimed; that CI/previous research result is distinct from the main local browser matrix.

See installation, rollout, rollback and legacy guides: verified GitHub assets, backup/Restore, 0.4.0/0.4.1 assisted recovery, one consent engine, one owner per SDK, GTM review and bounded Script Adapter limitations. The plugin is not a universal tracking firewall or a legal-compliance guarantee. No real production install occurred. Timeweb/testwp/production operations: **0**. No prior stable tag/Release/asset was changed. Final documentation is committed separately on main and its complete CI is required before the final handoff.
