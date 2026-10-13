# ITD Cookies 0.4.5 — Smart Consent Categories

Candidate: **0.4.5-dev.1**, stable baseline **0.4.4**.
Branch: `codex/itd-cookies-0.4.5-smart-categories`.
Base: fresh `origin/main` **31b64d2af525e1eaf4defe4c3ebcd00fe05e521c**.
Date: 2026-10-13. Gate: **READY_FOR_ITD_COOKIES_0_4_5_RELEASE**.

Implementation commit: **c288d793a41e15f5ce705d0d8915ec9f11d1bed1**.
[Full CI](https://github.com/itdream24/itd-cookies/actions/runs/38435692580):
**13/13 PASS**, including build; no skipped/cancelled/failed mandatory jobs.
The completion commit only updates this report; it does not change the tested ZIP.

## Behavior and consent decision

- Necessary always appears. Optional categories are rendered only when an enabled
  entry exists in `ITD_Cookies_Services`, including external descriptors and
  Script Adapters. Unused controls/status text are absent from HTML and policy.
- Zero optional services: one informative acknowledgement; reopening shows only
  Necessary. The text explicitly allows for independent external integrations.
- Accept all/custom choices normalize absent categories to false. PHP and JS
  `allowed()` additionally require an enabled category and service coverage.
- Primary consent remains schema 1, with the same four fields. A separate
  `itd_cookies_services` cookie binds a positive choice to stable service tokens.
  Both cookies have identical policy/expiry; malformed/mismatched proof fails
  closed. New provider IDs/privacy options, services and adapter revisions cannot
  inherit an earlier category grant. Unchanged categories can remain allowed.
- Old schema 1 Reject is retained byte-for-byte, without extending expiry. Old
  positive choices without a receipt require one-time confirmation. Accepted
  ModuBricks consent is inspected/marked and preserved, with no automatic grant;
  existing settings migration/marker behavior remains intact.
- Service removal/current-page subsets do not prompt. Re-enabling an identical
  previously known service is not a new identity. Integrations must revise
  `consent_revision` or the policy for vendor/purpose changes under the same ID;
  GTM container contents are not introspected.
- Receipt: up to 96 packed 128-bit tokens, encoded cookie at most 3800 bytes;
  primary at most 2048 bytes. Oversize/failed saves show an error, do not emit a
  successful choice event and do not load SDKs. Reject stays available.

See [architectural decision](ITD-COOKIES-0.4.5-CONSENT-DECISION.md) for cache,
registration, consent identity and compatibility consequences.

Shared markup/config is independent of visitor cookies. Config localization is
after group validation. Block-theme policy shortcodes can request declarations
once before head, without freezing queues; validation stays at priority 999.
Purge page/asset caches on deployment and inventory changes. No scanner, REST
polling, universal script interception or visitor-dependent cached HTML is added.
The cache test compared shared configuration before/after consent; it did not
install a full-page cache plugin or claim exhaustive cache-vendor compatibility.

Typography/CSS/footer and maps runtime are unchanged. The generated policy keeps
an explicit warning about independently operating external integrations/maps.

## Changed components

| Component | Changes |
| --- | --- |
| `includes/class-itd-cookies-services.php` | Deterministic inventory, descriptor revision |
| `includes/class-itd-cookies-consent.php` | PHP receipt and current-category coverage |
| `assets/js/consent.js` | Normalization, receipt save/validation, renewed choice, safe errors/events |
| `public/class-itd-cookies-plugin.php` | Filtered real markup, minimal UI, late config localization |
| `includes/class-itd-cookies-legal.php` | Used-category policy and independent integrations warning |
| `includes/class-itd-cookies-script-adapters.php` | Stable group identity/revision, declaration-once lifecycle |
| Main/package/lock/readme/changelog/PO/MO | Candidate version and Russian messages; dependencies unchanged |
| PHP/JS/integration tests/reference fixture | Consent, inventory, policy, lifecycle and CSP regression coverage |
| Installation/adapter/QA/decision docs | Compatibility, cache purge, receipt and cleanup contracts |

## Executed checks

| Check | Actual result |
| --- | --- |
| PHPUnit on PHP 7.4 | PASS — 59 tests, 690 assertions |
| JS tests | PASS — 48/48 |
| ESLint | PASS |
| PHPCS | PASS |
| PHPStan | PASS — no errors |
| PHPCompatibilityWP, PHP 7.4+ | PASS |
| Composer audit --locked | PASS — no vulnerability advisories |
| npm audit | PASS — 0 vulnerabilities |
| Translation build | PASS — 108 Russian messages; WP 5.2 MO test PASS |
| Gitleaks directory scan of repository source | PASS — no leaks |
| Reproducible build / package inspector / PHP 7.4 ZIP lint | PASS — 18 files, same hash twice |
| Native browser upgrade + registry/consent/UI/adapters/CSP | PASS — 39/39, final ZIP |
| Cache/legacy/minimal UI/typography/footer/PHP response follow-up | PASS — 13/13 |
| Full GitHub CI (13 jobs) | PASS — PHP 7.4/8.5, quality, node, five WordPress jobs, two legacy guards, secrets, build |

Final tested ZIP: `itd-cookies-0.4.5-dev.1.zip`.

SHA-256:
`e4168faf011407aaa292ecd3b5a98b4c102e851d24caa99260ca5574327a9706`.

Local builds and the downloaded CI artifact are byte-identical. The first
green implementation CI above and the documentation CI
[38436106198](https://github.com/itdream24/itd-cookies/actions/runs/38436106198)
(commit `df57af07e7c99802866a1c71c5abc8b56a5b2a1f`) both passed **13/13**.

Native WordPress Upload/Replace: **0.4.4 → 0.4.5-dev.1 PASS**. Active plugin folder
remained `itd-cookies`; settings, raw old consent, migration marker and managed
Cookie Policy were preserved. Old positive consent was preserved but blocked
until confirmation, as designed. All 18 installed files matched the exact ZIP.
This is candidate ZIP acceptance, not a public GitHub stable-release update.

## Browser acceptance evidence

Playwright **1.62.1**, installed Chrome/Chromium **155.0.8059.39**, independent
headless contexts. No user Chrome profile/cookies were touched.

| Scenario | Result |
| --- | --- |
| No optional / Analytics / Marketing / both / Functional adapter / all | PASS — markup, inventory, policy, normalized choice, PHP/JS parity |
| New GA4 after Yandex Accept all | PASS — no SDKs until renewed category choice |
| New Marketing category after Analytics choice | PASS — Analytics retained; Marketing false and renewed-choice UI |
| Changed Functional adapter revision | PASS — no inherited permission; explicit new choice starts once |
| Old positive / old Reject / ModuBricks accepted | PASS — preserved payloads, safe respective confirmation/retention |
| Reject/reload/custom/save/events/revoke/reopen | PASS |
| Tab/Shift+Tab/Escape/Cancel/focus return | PASS |
| Twenty Nineteen, Twenty Twenty, Twenty Twenty-Five | PASS at 320×568, 390×844, 768×1024, 1440×900 |
| Long services, 130% text, scroller, reachable actions, no horizontal overflow | PASS |
| Retained 80/90/100/115/130% scale and theme font | PASS |
| Retained left/center/right auto footer, one opener | PASS |
| Admin Settings API, legal pages, three shortcodes | PASS |
| Adapter zero/supported/errors/dependencies/ownership/deduplication | PASS |
| CSP nonce/hash | PASS — explicit reference policies, no unsafe-inline |
| Unexpected JS Console/CSP errors | PASS — none; intended local SDK 404 recorded separately |
| PHP output errors | PASS — none in sampled documents/API; server logs were not audited |

SDK endpoints were intercepted in the isolated test context with inert responses
and synthetic IDs. Actual browser request events/provider queues and adapter
executions were checked; no live vendor tracking delivery is claimed.

Private evidence outside Git/web-root: `045-smart-categories/browser/results.json`,
`followup.json`, `runtime-verified.json`, screenshots `registry-zero.png`,
`zero-informative-banner.png`, `registry-analytics.png`, `registry-marketing.png`,
`registry-both.png`, `registry-functional.png`, `registry-all.png`, and all three
themes' `320/390/768/1440-banner/panel.png`. Key screenshots visually inspected.

### Preserved first-pass history

First ZIP `08030a2e…`: 36/39 PASS. Two failures revealed a block-theme policy
timing defect: shortcodes rendered before enqueue and omitted adapter services.
Fixed by declaration-once without early queue freezing; new lifecycle PHPUnit
test and full final-ZIP browser rerun PASS. Third failure was the QA harness's
wrong admin title selector, corrected separately. No failures were hidden or
counted as PASS. Initial build lacked PHP on PATH; rerun with explicit PHP 7.4
passed. Initial local Gitleaks command referenced a nonexistent config; the
actual source scan with default rules subsequently passed.

The second local archive `997789b2…` passed 52/52 browser checks, but subsequent
comparison with the Linux CI artifact found 15 CRLF line endings in the source
`.po` file only. PHP, JS, CSS and compiled MO were identical. Normalizing that
working file to Git's LF did not change committed content or runtime code.
Both local rebuilds then matched the CI SHA above. The complete native upgrade,
39/39 browser matrix and 13/13 follow-up were repeated on this exact final ZIP;
all 18 installed file hashes matched. Final Restore was repeated afterwards.

## Cleanup / immutable baseline

Before destructive QA, preserved local checkpoint and actually restored immutable
`baseline-v0.4.4`. After QA, restored it again. All temporary fixtures, reference
plugin, pages, options/counters and uploaded package artifacts disappeared with
the restored wp-content/database. Both browser runs and follow-up cleared their
isolated cookies (including receipt) and closed contexts. User Chrome unchanged.

- **504/504 file hashes match**; settings/theme/active plugin match.
- **12/12 schemas and every dumped data row match**; no maintenance file.
- Official **0.4.4 ACTIVE**, no candidate or temporary plugin left installed.
- Baseline SQL SHA:
  `3a2c17fa9741f0c620b52a0a30a24f9d29b412fd4fd74598aab775e41430839b`.
- Baseline manifest SHA:
  `a977cf4e2697f7cfb575cde064563faad9aee77d22d06e7e779542538c74ebc8`.
- Canonical restored DB SHA:
  `0e7bb14a0c3ba77ff0fd501bb6bc3f36bc6344ae276bbdd49285771aa7be8084`.

Baseline was not overwritten. Production/testwp/Timeweb QA requests and mutations:
**0**. Upstream main, existing tags/assets/Releases unchanged. Stable publication
requires a separate release gate and owner authorization.

## Stable release gate 0.4.5 — 2026-10-13

Owner authorized stable publication and local updater acceptance in this task.
Release branch `release/itd-cookies-0.4.5` starts from accepted candidate
`fdb92850ffd08b819917322a27c9f2de2a93c147`; fresh main is its ancestor
`31b64d2af525e1eaf4defe4c3ebcd00fe05e521c`. Existing tags/assets remain immutable.
Version metadata is synchronized to 0.4.5, dependencies/runtime behavior unchanged.
Stable local ZIP (18 files, PHP 7.4 inspector, same SHA twice):
`2428d0d64bf4c6e95481621b4a59499c2bead44164d36966310a79bbcd8e4a9d`.

Stable-specific CI, native upgrade matrix, receipt attributes/corruption, actual
shared HTML cache/purge, main CI, publication, public artifact verification,
real GitHub updater and new immutable baseline are pending at this preparation
commit. Candidate history above is retained and is not substituted for these gates.
