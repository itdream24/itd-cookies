# ITD Cookies 0.4.4 — Typography, Footer UX and Maps Policy

Published stable remains 0.4.3. Candidate: 0.4.4-dev.1 on a separate feature branch.
Main and published tags/Release assets remain unchanged.

## Accepted addition: universal embedded maps policy

Owner decision 2026-10-13: Yandex Maps iframe/constructor embeds load automatically
on project sites, independently of Analytics and after Reject. Preserve the
integration's geometry and settings. Native Metrika stays consent-controlled.

See [technical decision, site responsibilities and admin copy](MAPS-INTEGRATION-POLICY.md).
Maps frontend runtime changes: **none required**. No fictitious map switch or global
interception. A site that registered/gated its map must correct that integration;
the generic Script Adapter API is not silently changed.

## Combined implementation scope

- Include this policy in installation and production rollout documentation.
- The translated explanatory admin paragraph is implemented with the same 0.4.4
  typography/footer settings work. It is information, not a stored map setting.
- Typography/footer acceptance is documented separately below. Do not infer
  complete real-vendor maps availability from the UX checks.
- Preserve consent API, provider categories, Script Adapters and saved settings.
- No separate tag/Release for documentation; normal full 0.4.4 CI and release gate later.

## Historical Maps Policy browser verification (official 0.4.3)

Verification uses official 0.4.3 on `http://itd-cookies.local`, temporary local-only
fixture, real Yandex map resources and synthetic native provider/adapter scripts.
Chrome/Chromium155.0.8059.39, Playwright1.62.1; independent contexts, no user profile.

| Case | Result |
|---|---|
| Direct iframe, zero groups, 320/390/768/1440px | PASS4/4 composite lifecycles |
| Constructor, zero groups, 320/390/1440px | PASS3/3 composite lifecycles |
| Constructor, zero groups, 768px | FAIL: external constructor request HTTP504 on reload; real rerun also HTTP504 |
| Iframe + unrelated adapter groups, 390/1440px | PASS2/2: closed dependency order, categories and dedup |
| Constructor + unrelated adapter groups, 390/1440px | PASS2/2 |
| Constructor + failed adapter, 390px | PASS: deliberate local404, dependent skipped, marketing independent, map unaffected |
| Separate 768px constructor contract | PASS with explicitly mocked constructor response and a real map iframe; not a real-constructor availability PASS |
| Existing JS regression suite | PASS42/42; no failed/skipped tests |

Each successful lifecycle checks Fresh, Reject/reload, Analytics-only, same-choice
Save, Accept all and Revoke/new document; one iframe and one constructor request
where applicable, preserved 100%/400px fixture geometry, real map zoom, native
Metrika load/init0 before consent and1 after, keyboard/reopen. Map vendor SDK/
tracking is attributed separately from the synthetic site's SDK.

**Actual vendor matrix:12/13 PASS,1 FAIL**, with an additional failed real retry.
Do not report full real Browser E2E PASS or diagnose an ITD defect from the
external504. The separate constructor-response mock confirms the plugin contract
at768px and is recorded independently. Real vendor availability at that case
requires a later bounded recheck when the external request succeeds.

Native Metrika's SDK response and adapter scripts are synthetic local QA;
account-side tracking delivery is NOT_RUN. Maps iframe/SDK/tiles/telemetry are
real except the explicitly identified constructor response in the separate test.

Before QA, immutable baseline0.4.3 archive/hash integrity was checked and a fresh
task checkpoint was actually restored. Initial WP-CLI import with Windows
backslash paths failed before import; forward-slash paths fixed the QA command,
then all12 table schemas/rows matched. No runtime fix was made for this harness issue.

Cleanup removed the one temporary MU fixture and its newly created empty folder.
No persistent QA pages/options/counters were created. Independent contexts cleared
their cookies and closed. Final504 wp-content hashes and all12 database tables
match the pre-task checkpoint; original settings/marker/activity preserved,
official0.4.3 ACTIVE. Baseline0.4.3 and older snapshots remain unchanged.
Screenshots/raw Network/SQL stay outside Git and webroot; no production requests.

**Maps policy/specification: accepted. Real vendor Browser gate: partially blocked
by external HTTP504.** The paragraph was only planned at commit 73b15ba;
the combined candidate now implements it. Maps frontend behavior is unchanged.

## Implementation and acceptance — 2026-10-13

### Source and scope

- Fresh origin/main: `fd3dcad15ae1fe6298eed96ec052152e7801dd22`; original checkout clean.
- Feature: `codex/itd-cookies-0.4.4-typography-footer`, created from that main.
- Only the four agreed documents from local Maps Policy commit
  `73b15baea1aeb2953ca040a5868a03edb10fdbaf` were cherry-picked as `b472691`.
- Candidate version synchronized in header, PHP constant, package/lock, readme,
  PO/MO and changelog. No dependency versions changed.
- Consent/provider JS, Script Adapters, updater and public API are unchanged.
  No theme DOM interception, output buffering, maps loader or consent schema change.

### Typography decision and measurements

The old rem sizes multiplied the theme's root font size. Measured at 390px:

| Theme | Root | Old Standard title | Old description/button | New Standard title | New description/button |
|---|---:|---:|---:|---:|---:|
| Twenty Nineteen 3.4 | 22px | 27.5px | 20.625px | 20px | 15px |
| Twenty Twenty 3.2 | 10px | 12.5px | 9.375px | 20px | 15px |
| Twenty Twenty-Five 1.5 | 16px | 20px | 15px | 20px | 15px |

Theme versions above are checked against the installed local themes at acceptance.
The local CSS text unit is `calc(16px * var(--itd-cookies-scale))`.
All window text sizes use that unit with their existing hierarchy; status labels
inherit the scaled description. No font family is added. Computed description/
button family equals the theme body: Helvetica Neue stack for Nineteen/Twenty,
Manrope for Twenty-Five. Browser zoom remains available; sizes do not follow a
theme rem reset. Existing preset keys remain valid, but their appearance changes
intentionally: Standard stays 15px on a 16px root and normalizes other themes.

| Preset | Scale | Title | Description/button/services | Legal link | Category name |
|---|---:|---:|---:|---:|---:|
| Very small | 80% | 16px | 12px | 11.2px | 12.8px |
| Small | 90% | 18px | 13.5px | 12.6px | 14.4px |
| Standard | 100% | 20px | 15px | 14px | 16px |
| Large | 115% | 23px | 17.25px | 16.1px | 18.4px |
| Very large | 130% | 26px | 19.5px | 18.2px | 20.8px |

The larger 10–15 percentage point steps replace the previous 5 point steps.
Missing/invalid text preset still falls back to Standard.

### Automatic footer

`auto_footer` remains OFF by default. When enabled, the automatic wrapper is
centered by default, with `footer_alignment` restricted to left/center/right.
Missing, invalid or non-scalar values normalize to center. Reads do not write
old options; stored consent/version/marker are preserved. Settings API owns
nonce/capability checks. The wrapper class is escaped.

Scoped automatic-only CSS uses a 1200px maximum content width, safe-area-aware
16px outer padding, wrapping links, a 4px/24px gap and minimum 44px targets.
Its theme font is retained at 16px. Manual shortcode markup/layout is unchanged;
choose manual integration with automatic output off to avoid duplication.
No theme element is moved or restyled by JavaScript.

### Actual local Browser E2E

OpenServer primary site only, WP 7.1.2/PHP 8.1, ru_RU.
Playwright 1.62.1, installed Chrome/Chromium 155.0.8059.39, isolated contexts.
Native browser Upload/Replace 0.4.3 → 0.4.4-dev.1; all 18 installed package
files match the tested ZIP. No candidate file replacement was used.

| Gate | Actual result |
|---|---|
| Native upgrade: raw settings, consent, migration marker, policy, active state | PASS |
| 3 themes × 4 widths × 5 presets, computed sizes/families | PASS 60/60 |
| 3 themes × 4 widths × 3 footer alignments, links/reopen/focus | PASS 36/36 |
| 320×568, 390×844, 768×1024, 1440×900; long category list and accessible actions | PASS |
| Long banner/links, Russian Functional kept whole, keyboard/colors on 3 themes × 4 widths | PASS 12/12 |
| Admin save of all alignments/text preset; Russian Maps information | PASS |
| Policy/legal documents/three shortcodes; automatic alignment isolated from manual markup | PASS |
| Fresh, Reject, Functional, Analytics, Marketing, All; repeated event/reload/revoke | PASS |
| Script Adapters zero/supported/errors/dependencies/dedup/CSP | PASS; no runtime changes |
| Text and button border contrast, normal/hover/focus | PASS, measured minimum 6.386:1 |
| Forced colors | PASS 3 theme emulations; physical Windows High Contrast NOT_RUN |
| CSP nonce/hash | PASS; zero observed violations |
| Unexpected JS errors and observed local PHP warnings | PASS; none |

**119/119 main scenarios + 19/19 follow-up scenarios PASS.**
SDK browser request events are observed using synthetic IDs and no-op SDK
responses. Fresh/Reject/Functional request 0 optional SDKs; Analytics requests
4, Marketing 1, All 5, Revoke requests 0 in the new document. Queue init counts
and dependency traces/dedup are checked. Real account delivery/vendor SDK
availability is NOT_RUN here; this is not a real tracking account test.

Earlier follow-up runs exposed two harness errors: initial Escape was expected
to focus Customize instead of the existing Accept-all contract, and Necessary
was queried with a data attribute it never had. Corrected only the harness and
reran; runtime ZIP/SHA unchanged. First results remain in private evidence.
Initial sandbox PHP tools also failed on readable/temp paths; rerunning with
the permitted tool environment succeeded. Initial PHPCS formatting findings
were corrected before the tested ZIP was built.

84 main screenshots plus upgrade/admin/forced-colors images are saved privately,
outside Git/webroot. Before/after screenshots of Twenty Twenty at 390px show
the former 9.375px and new 15px standard description. Narrow large-size panel
and centered footer images were visually reviewed. Raw QA data stays private.

### Quality and package checks

- PHPUnit: PHP 7.4 and local PHP 8.1, 55 tests/620 assertions PASS.
- JS: 42/42 PASS; ESLint PASS; 103-message translation build PASS.
- PHPCS, PHPStan and PHPCompatibilityWP 7.4+ PASS.
- Composer validate/audit PASS, npm audit 0 vulnerabilities.
- Gitleaks full history and source tree PASS, no leaks.
- Reproducible build PASS (two builds identical); package inspector/PHP 7.4
  package syntax PASS. One `itd-cookies/` root, 18 files, updater and PO/MO included;
  dev/tests/docs/private QA excluded.
- ZIP: `itd-cookies-0.4.4-dev.1.zip`.
- SHA-256: `67a13f1ee2a00c542940b68d2ccac525df06c383720b0defcd6f846993d10870`.
- Full implementation GitHub CI: **13/13 PASS**, commit
  `ebdcd6c873ffd7b56c105d95fe3a5be0b0b42674`,
  [run 38396856762](https://github.com/itdream24/itd-cookies/actions/runs/38396856762).
  Current workflow has 13 jobs, not the older 9: PHP ×2, quality, node,
  WordPress ×5, legacy guard ×2, secrets, build. No workflow jobs were removed.
- Downloaded Linux CI artifact matches the local tested/rebuilt ZIP SHA exactly;
  package inspector with PHP 7.4 passes for that artifact too.

### Cleanup and Restore

Immutable baseline-v0.4.3 was actually restored before testing and after it.
Final verification: all 504 wp-content file hashes and schemas/rows of all 12
tables match. Settings, migration marker, policy and activity match baseline;
official 0.4.3 ACTIVE, Twenty Twenty-Five restored. Temporary themes, reference
plugin, MU helper, synthetic pages/options/counters and QA cookies are gone.
QA contexts were cleared and closed; user Chrome profile was not touched.
No `.maintenance` or temporary MU directory remains. Baseline and older
snapshots were not overwritten.

- Baseline SQL SHA: `e04288464b2ac2c70aa3343c64230656faa28159cb4f9ae4bf4f6434f3aa4708`.
- Manifest SHA: `241de2f533c82434ce9a284ac8dd3e64bd4c4098a2dc900f1fa3e4a47d8460ee`.
- Canonical 12-table data SHA: `64bf751d605579d40456b390dd576aed2f4e0c2689e4cac24c3776a7a686f287`.

No production/testwp/Timeweb access, main merge, tag or GitHub Release.
Historical real maps result remains **12/13**, with external constructor 504
and a separate mocked-contract PASS; it is not upgraded to 13/13 by these UX tests.

**Implementation verdict: `READY_FOR_ITD_COOKIES_0_4_4_RELEASE`.**
The documentation-only completion commit must also finish its full feature CI
before the final handoff. This is readiness for a separate stable release gate,
not authorization to merge/tag/publish. Historical vendor maps availability
remains explicitly 12/13 real PASS plus the separate mocked-constructor PASS.


## Stable release gate — preparation

Release branch `release/itd-cookies-0.4.4` starts strictly from accepted `282a4a3813174025d87117dd0a909f641c5d2266` (CI 38397237965, 13/13 PASS). Fresh origin/main was fd3dcad. Stable version 0.4.4 synchronized with structured lockfile updates: only root/package version changed; every resolved dependency and range/integrity is unchanged. Consent schema/provider loader/updater/Script Adapters/maps/migration runtime unchanged. Pre-release stable acceptance, release/main CI, publication and real update remain pending. Historical real Maps result remains 12/13 with external 504, separate mocked constructor PASS; no maps regression evidence and no map integration change.
