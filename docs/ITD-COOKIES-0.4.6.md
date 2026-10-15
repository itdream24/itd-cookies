# ITD Cookies 0.4.6 — stable release and automatic footer colors

## Final result — 2026-10-15

**`ITD_COOKIES_V0.4.6_RELEASED_AND_LOCAL_STABLE_UPDATE_VERIFIED`**

- Release: https://github.com/itdream24/itd-cookies/releases/tag/v0.4.6
- Immutable annotated tag object: `c8a9c06e0aad80d54e91916bf34cea4343de692b`.
- Tag/stable commit: `c9d4e4f16eac0629edecfb31ca38322022229004`.
- Minimal CI fix: `c88a03745bb88fd0371fd650d53ca76e1dec2499`; no runtime/dependency changes during recovery.
- [Recovery CI](https://github.com/itdream24/itd-cookies/actions/runs/38685649191):13/13 PASS.
- [Release HEAD CI](https://github.com/itdream24/itd-cookies/actions/runs/38685852535):13/13 PASS.
- [Independent pre-tag main CI](https://github.com/itdream24/itd-cookies/actions/runs/38686015557):13/13 PASS.
- [Stable Release workflow](https://github.com/itdream24/itd-cookies/actions/runs/38687367191):15/15 completed SUCCESS, including publish. No mandatory skipped/cancelled job.
- Official asset: `itd-cookies-0.4.6.zip`, SHA-256 `4c61310f81eaae2dccf9527d9a868ca0188597045061ef1fa7bfd61209770994`.
- Local reproducible build, Linux recovery/main CI artifacts, anonymous published download and official checksum all match. Anonymous API confirms latest=v0.4.6, draft=false, prerelease=false. Published package inspector/PHP7.4 syntax:20 files PASS.

### Genuine WordPress updater —7/7 PASS

After publication, immutable official baseline-v0.4.5 was restored. A local-only observer recorded HTTP events and state; it did not intercept/substitute HTTP, metadata, package or cache. Synthetic settings/IDs, Cookie Policy/legal pages and two real0.4.5 visitor choices (Reject/positive) were created.

The actual WordPress Updates → Check again requested the public GitHub latest API exactly once (HTTP200), received0.4.6 and the real public package URL. The normal WordPress update button installed0.4.6 in `itd-cookies`, active. All20 file hashes match the anonymous published ZIP. All original settings, primary consent and service receipt for both visitors, migration marker and policy ID/content/title/status survived exactly. No unnecessary re-consent, no SDK for Reject; repeated save did not duplicate SDK; revoke/reload worked. Published Color Picker/manual HEX/save and dark footer were checked at390px without changing visitor receipts. Guest/admin contexts cleared and closed; user Chrome untouched. No unexpected observer PHP or browser JS errors. SDK delivery itself used inert browser responses with synthetic IDs; server-side GitHub API/package were genuine throughout.

Private evidence: `046-stable-release/real-update/results.json`, public-offer/native-update screenshots and `official-dark-footer-390.png`. This is genuine updater acceptance, not synthetic metadata or assisted Upload/Replace recovery.

### Cleanup and new immutable baseline

The observer, all QA pages/options/counters/settings were removed by full baseline-v0.4.5 Restore, independently verified against504 files and all12 tables. Then a second **helper-free native WordPress updater** upgraded the clean original0.4.5 to official0.4.6, preserving original settings/marker/policy/theme. There is exactly one official active plugin; all20 published file hashes match. Test admin session explicitly logged out; QA cookies cleared, browser context closed, no maintenance file.

New separate `baseline-v0.4.6` was captured outside Git/webroot and **actually restored**. Verification: **506/506 file hashes**, every schema and row in **12/12 tables**, settings, locale, theme, marker and active plugin PASS. The two additional files relative to0.4.5 are the new footer editor JS/CSS assets.

- New baseline SQL SHA-256: `4b6ad4ee948af6e4cae6b4df872b5f8cca054e16174b5ed26c56b3a33ba36af9`.
- New manifest SHA-256: `350e33f33e4223d6672bff30db0ac36df23375812759e90d98bc1780a283466b`.
- All12 canonical table hash: `415cc8cc7f793e59b0897eeecb93f114ca6f534bd051f5187c228c929e42e24e`.
- Old baseline-v0.4.5 SQL remains `30b3d7a37a1c123c5800a93f14f29a8e5812159bdd187e20bab5e285094ad2f7`, manifest `f8c0b1ae9be5ee8cf500b6472e47ba946b76ec7b773b5f8ea6b152f928ff0ae0`.

Final stand: official **0.4.6 ACTIVE**, WordPress7.1.2 / PHP8.1.5 / ru_RU / Twenty Twenty-Five1.5. No temporary plugins, synthetic metadata, QA pages/options/counters or test cookies remain. Existing baselines/tags/Release assets are not overwritten. No business-site/production/testwp/Timeweb access or mutation.

Strict CSP limits are unchanged: empty color defaults add no palette inline CSS; chosen colors require site-authorized nonce/hash for the native WordPress inline stylesheet. Without authorization the browser correctly blocks the palette, with expected CSP violations; the document stays usable. No unsafe-inline, policy disabling or universal CSP compatibility is claimed. Forced-colors QA is emulation. Full stable three-theme/mobile/CSP suite44/44 is recorded below; runtime is byte-identical to that accepted stable ZIP.

Final documentation commit receives its own CI; final HEAD/CI URL are included in the handoff.

---

## Stable gate — 2026-10-14

Stable source is prepared from accepted feature commit `2417b3bbc288d8e771a56d4763e04196b332cd95`. Release branch: `release/itd-cookies-0.4.6`. Fresh main was `ec9f662c64880b21a2a9f67c3fde2ff5797bcaef`, an ancestor of the accepted commit; no unexpected divergence. Version metadata alone changes to 0.4.6; runtime and dependencies are identical to the accepted implementation.

Stable ZIP: `itd-cookies-0.4.6.zip`, 20 files, PHP 7.4 lint/package inspector PASS. Two consecutive builds have SHA-256 `4c61310f81eaae2dccf9527d9a868ca0188597045061ef1fa7bfd61209770994`. PHPUnit 62/734, JS 50, ESLint, Composer/npm audits PASS.

Separate stable Browser E2E **44/44 PASS**, Chromium 155.0.8059.40: native 0.4.5 → 0.4.6 ZIP upgrade, exact settings/primary+receipt/marker/policy/active preservation; three themes × 320/390/768/1440; picker/save/reopen/reset; light/dark palettes; #181C1E/white contrast17.161:1; alignments and manual shortcode/banner isolation; auto-footer OFF/ON; keyboard; consent/providers and Script Adapters; emulated forced colors. All20 installed file hashes match the stable ZIP.

CSP stable tests: no additional palette stylesheet at empty defaults; palette applies with authorized nonce/hash and no style attributes; strict CSP without authorization blocks custom inline palette **as expected**, while the document stays usable. Expected denial violations are counted separately. No unsafe-inline or CSP disabling is added by the plugin. CSP integrations must authorize WordPress inline styles (exact hash includes WordPress whitespace/sourceURL); inherited themes/admin native picker also require an appropriate site policy. No universal CSP compatibility is claimed.

Map regression uses a synthetic local constructor/iframe integration: one map instance and preserved dimensions for Fresh/Reject/Accept all, independent of Analytics; primary SDK remains gated. External Yandex availability/tracking delivery is **not** claimed by this mock. Runtime map handling remains unchanged. The first stable harness looked for Reject with zero optional services; corrected fixture enables synthetic Analytics, and the entire44-scenario suite was rerun. A transient WordPress.org TLS warning during CLI theme installation is classified as setup environment output; themes installed from cache, browser suite has no unexpected errors.

Pre-release cleanup restores immutable baseline-v0.4.5: 504 files, every schema and row in12 tables, original settings/marker/theme/plugin activity; QA cookies cleared in isolated contexts, user Chrome untouched. Old snapshot SHA values remain as recorded below. Publication waits for release and independent main CI.

### Initial release CI blocker (history retained)

Stable preparation commit: `295d5311830c4136305c216171748b71599830d8`.
[Original release CI](https://github.com/itdream24/itd-cookies/actions/runs/38605368991), attempts **1 and 2**:
**Both FAILURE** — each attempt: 11 jobs SUCCESS, `wordpress (5.3.26, 7.4)` FAILURE,
downstream `build` SKIPPED.

The WordPress 5.3.26 job reaches integration setup but its first `wp core download`
command fails: `wp: command not found`, exit 127. Plugin integration steps never run
in that job. This is a WP-CLI/CI infrastructure setup blocker, not evidence of a
plugin runtime defect. Other WordPress matrix jobs and PHP/JS/quality/secrets pass.
Workflow/runtime/dependencies have not been modified to hide the failure.

Per the owner's stop-on-failed-gate requirement, no main merge, tag, Release,
publication attempt or genuine updater acceptance is performed. Main remains
`ec9f662c64880b21a2a9f67c3fde2ff5797bcaef`; latest remains v0.4.5.
Stable Browser E2E44/44 and baseline Restore504/12 remain PASS. Official0.4.5 ACTIVE
is left locally, no QA artefacts; baseline-v0.4.6 is NOT_CREATED.

Continuation requires a fully completed release CI including WP5.3.26 and build,
then separate main CI before tagging. Final genuine updater/new baseline remain
NOT_RUN. This blocker note is retained as a local documentation diff, without a
push that would automatically start another CI attempt.

Historical verdict before recovery: **`BLOCKED_BY_CI_WP_CLI_SETUP`**. No publication occurred during that blocked stage.

## WP-CLI infrastructure recovery — 2026-10-15

Original HEAD `295d5311830c4136305c216171748b71599830d8`; attempt1 job115994750505 and attempt2 job115997251748 both show setup-php `Could not setup wp-cli`, followed by `wp: command not found` / exit127. WordPress integration did not start in that job; WordPress5.3.26 is not identified as the cause. Setup-php does not report an underlying HTTP status, so a specific network cause is unproven.

Minimal infrastructure commit: `c88a03745bb88fd0371fd650d53ca76e1dec2499` (workflow + CI helper only). Native setup-php remains responsible for PHP/extensions and pins `wp-cli:2.12.0`. `scripts/ci/ensure-wp-cli.sh` checks the command, falls back only when missing (or explicitly forced by its regression row), downloads the official pinned PHAR over verified HTTPS with3 bounded retries, verifies a fixed SHA-256 before installing to RUNNER_TEMP, then requires `command -v wp`, `wp --info` and exact2.12.0. No curl|bash, TLS disabling, integration skipping, matrix reduction or swallowed installation error.

Source: [official WP-CLI2.12.0 release](https://github.com/wp-cli/wp-cli/releases/tag/v2.12.0). Pinned PHAR SHA-256 `ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c` independently matches both the official checksum asset and GitHub release asset digest. The same verifier protects both WordPress and legacy-guard matrices. WP5.3.26 deliberately forces the real fallback on every CI, then runs the complete existing integration suite with that PHAR.

Local tests on PHP7.4.33/Git Bash: actual absent-command fallback download/install/info PASS; already-present command PASS; corrupt download rejected before installation PASS; download failure rejected PASS. No global WP-CLI/OpenServer setting was changed. The original uncommitted blocker report was backed up privately as a patch (SHA-256 `0f4bcdd2dc3cac244e54a82da724b00cda61b59ccd0a5616fd2429316e3330c8`) and its history remains above.

Two stable rebuilds / package inspector PASS with unchanged SHA-256 `4c61310f81eaae2dccf9527d9a868ca0188597045061ef1fa7bfd61209770994`. No plugin PHP/CSS/JS, runtime dependencies, consent/receipts/providers/adapters or version metadata changed in recovery.

[Recovery CI](https://github.com/itdream24/itd-cookies/actions/runs/38685649191) on c88a037: **13/13 completed SUCCESS**. WP5.3.26 executes the verified fallback and its entire integration smoke; every other matrix row and downstream build PASS, no skipped/cancelled mandatory job. Downloaded Linux CI artifact matches the same stable ZIP SHA-256. Current stage: release CI recovered; independent main CI and publication remain pending.

## Pre-publication local startup pause (resolved) — 2026-10-15

[Actual release HEAD CI](https://github.com/itdream24/itd-cookies/actions/runs/38685852535), commit `c9d4e4f16eac0629edecfb31ca38322022229004`: **13/13 completed SUCCESS**, including forced fallback WP5.3.26 and build. Main was clean and fast-forwarded to that exact commit after this gate.

[Separate main CI](https://github.com/itdream24/itd-cookies/actions/runs/38686015557): **13/13 completed SUCCESS**. Independently downloaded recovery/main CI ZIPs and local ZIP all have SHA-256 `4c61310f81eaae2dccf9527d9a868ca0188597045061ef1fa7bfd61209770994`. Stable Browser44/44 is retained; no runtime change requires reacceptance.

Pre-publication environment blocker: local OpenServer services are stopped/unavailable. MariaDB127.0.0.1:3306 returns10061; HTTP port80 is unavailable. The installed OpenServer5.4.2 binary confirms its `/start` switch; a normal start was requested without changing global settings. The log reaches `Обновление Hosts файла`, but services are not ready. The owner has been asked to finish the normal startup / report any displayed dialog. No host/IP proxy workaround, manual global config rewrite or direct database-daemon repair was attempted.

A private wp-content checkpoint was copied; its database export failed while MariaDB was down and is explicitly INVALID (empty), never used for Restore. Immutable baseline-v0.4.5 SQL/manifest hashes remain unchanged. There has been no local WordPress mutation in this recovery task.

Historical continuation status during the pause: **`BLOCKED_BY_LOCAL_OPENSERVER_STARTUP`**. WP-CLI CI blocker is resolved. Tag/Release/publication/genuine updater/new baseline remain NOT_RUN; latest is still v0.4.5. Resume at local readiness verification and pre-tag checks on c9d4e4f, not by retrying the old failed CI. The owner subsequently completed normal OpenServer startup; HTTP/DB readiness and a valid SQL checkpoint were verified. Baseline-v0.4.5 Restore504/12 was repeated before tagging. This pause did not cause any tag or publication rewrite.

## Feature acceptance history (retained)

Date: 2026-10-14. Candidate: `0.4.6-dev.1`.
Branch: `codex/itd-cookies-0.4.6-footer-colors`, based on fresh
`origin/main` `ec9f662c64880b21a2a9f67c3fde2ff5797bcaef` (official stable 0.4.5).

## Implementation

- Settings API: `footer_background_color`, `footer_text_color`, both default `''`.
- Only `#RRGGBB` is accepted, trimmed and normalized to lowercase. Short HEX,
  alpha, named colors, expressions, URLs, scripts and non-string values are rejected.
  Invalid saved data normalizes quietly to empty. Invalid submissions preserve the
  previous valid color and display a localized error through Settings API.
- Native `wp-color-picker` loads only on the authorized ITD Cookies settings page.
  Each field has an independent reset, manual HEX and live preview. A warning appears
  below 4.5:1; invalid input has a separate message and `aria-invalid`.
- Preview uses a neutral white background and dark text when fields are empty;
  the help explicitly explains that inherited frontend colors depend on the theme.
  Colors are not silently changed to improve contrast: administrators choose the palette.
- Frontend CSS goes through `wp_add_inline_style`, is revalidated at the CSS boundary
  and contains only literal HEX and fixed selectors under
  `.itd-cookies-footer.itd-cookies-footer--custom-colors`. No style attributes or
  theme files are written. Empty colors add no stylesheet and no custom class.
- Scoped important declarations protect selected colors from theme button/link styles.
  The settings button stays transparent; hover uses an underline, keyboard focus has
  black/white rings. Forced colors uses system colors instead of the authored palette.
- Left/center/right, spacing, theme fonts, legal URLs and manual shortcode markup remain
  intact. Banner/modal, providers, consent engine, services inventory/receipt, schema,
  consent version and migration marker are unchanged. Dependencies are unchanged;
  package/lock changes only synchronize the candidate version.

### CSP and page cache

Strict `style-src` must authorize the WordPress-generated inline stylesheet with a
nonce or its exact hash (including WordPress whitespace/sourceURL). The plugin does
not disable CSP, add `unsafe-inline`, generate a fake nonce or bypass a site policy.
Site CSP integrations are responsible for the authorization; changing colors requires
an updated hash where hashes are pinned. Empty defaults require no new inline style.
Browser tests enforce both nonce and hash, with `style-src-attr 'none'`, using a
temporary local fixture that authorizes the native WordPress stylesheet. This is
not a claim that every site's existing CSP configuration automatically supports it.
The native admin picker uses WordPress's own dynamic styles; it needs a compatible
WordPress admin CSP. Clear page cache/CDN after saving colors.

## Local acceptance — actual results

Environment: OpenServer, `http://itd-cookies.local`, WordPress 7.1.2 / PHP 8.1.5,
ru_RU. Playwright 1.62.1, installed Chromium/Chrome **155.0.8059.40**, isolated headless
contexts (no user Chrome profile).

| Check | Result |
| --- | --- |
| Native WordPress Upload/Replace 0.4.5 → 0.4.6-dev.1 | PASS |
| Settings, active plugin, marker, Cookie Policy and valid consent/receipt survive upgrade | PASS, byte-exact cookie comparison |
| Empty color defaults | PASS, computed styles identical before/after upgrade |
| Native picker palette click, manual HEX, live preview, warning, validation error | PASS |
| Save/reopen and independent resets including native picker swatch | PASS |
| Twenty Nineteen 3.4, Twenty Twenty 3.2, Twenty Twenty-Five 1.5 | PASS |
| Widths 320×568, 390×844, 768×1024, 1440×900 across all three themes | PASS, no horizontal overflow |
| `#181C1E` background / white text, hover, active, keyboard focus | PASS, text contrast **17.161:1** in all 12 theme/viewport combinations |
| Significant focus indication | PASS, black outline / white outer ring; white-to-background contrast 17.161:1 |
| White/black and black/white, arbitrary `#345678` / white | PASS |
| Left/center/right at 320/390/1440 | PASS |
| Manual legal shortcode, banner and banner button style isolation | PASS on all three themes |
| Forced colors | PASS **Playwright emulation**, not physical Windows High Contrast |
| Nonce/hash style CSP | PASS, actually enforced by browser |
| Fresh, Reject/reload, Analytics/save, repeated save, revoke/reload | PASS, no optional SDK before consent or after rejection; one load per permitted document |
| Footer opener, Escape/focus return, Cookie Policy and three shortcodes | PASS |
| Script Adapters zero/supported/404/ownership/nonce/hash, dependencies and deduplication | PASS |
| JavaScript/page/CSP errors | PASS, none unexpected; fixture 404 intentional, local favicon 404 explicitly classified |

Final Browser E2E: **39/39 PASS**. All **20 installed package files** match the tested
ZIP by SHA-256. Synthetic counter IDs and no-op SDK responses were used: actual
browser SDK request events and gating are verified, live vendor delivery is not tested.

Screenshots were captured and visually inspected, including Twenty Twenty at 320 px
and the native picker/preview. Private evidence is outside Git/webroot in
`046-footer-colors/browser` of the existing private QA workspace: `results.json`,
`native-upgrade.html`, `admin-preview-dark.png`, `admin-low-contrast.png`,
`THEME-WIDTH-dark-footer.png`, `palette-*.png`, `forced-colors-emulation.png`.

### Test-run history

Earlier preliminary harness runs are retained privately. The first tried filling
the native picker field while WordPress had hidden it; the harness now opens the
picker with a real click. Another captured the installation before complete HTTP
load and encountered an incomplete local file set; the site was restored, and native
upgrade was repeated with full load/network completion and package hash verification.
The initial broad Console check also caught the intentionally aborted fixture CDN
dependency and a test counter outside the adapter fixture CSP allowlist. Fixtures were
isolated and the final full run has no unexpected errors. None of those preliminary
runs is counted as the final PASS. Reset was refined to dispatch the native change
event so both value and swatch clear; final ZIP was rebuilt and fully retested.

## Quality and package

- PHPUnit PHP 7.4: **62 tests / 734 assertions PASS**.
- JavaScript: **50 tests PASS**; ESLint PASS.
- PHPCS: PASS, zero warnings/errors. PHPStan: PASS.
- PHPCompatibilityWP 7.4+: PASS. Package inspector includes PHP 7.4 syntax checks.
- Composer audit: no advisories; npm audit: **0 vulnerabilities**.
- Russian translation build: **116 messages PASS**.
- Gitleaks: PASS, no leaks in repository history; final commit is checked separately.
- Production-format package: **20 files**, root `itd-cookies/`; no tests/docs/fixtures,
  observers, dependencies or development files. Two consecutive builds identical.

ZIP: `itd-cookies-0.4.6-dev.1.zip`.
SHA-256: `f8f07f0bb1e5d797dbbf9025b541e5f260dfd5d2db548a200445a68fd851e3b5`.

## Cleanup and CI

Baseline-v0.4.5 was verified by actual Restore before QA and again after final E2E.
Final Restore: **504/504 file hashes**, every schema/row in **12/12 tables**, settings,
marker, theme and activity PASS. Temporary themes, reference/CSP plugins, QA pages,
options/counters are absent. Official **0.4.5 ACTIVE** remains installed.
Immutable SQL SHA-256: `30b3d7a37a1c123c5800a93f14f29a8e5812159bdd187e20bab5e285094ad2f7`;
manifest SHA-256: `f8c0b1ae9be5ee8cf500b6472e47ba946b76ec7b773b5f8ea6b152f928ff0ae0`.
All 12 canonical database tables hash:
`99b78bd5dca0d38958681dc3f913aa4516f4929565ea807e4bc59c59d207b29a`.
GitHub CI is recorded below after completion. No baseline is overwritten.
All QA runs use separate contexts; final run clears guest/admin cookies and closes
them. User Chrome is untouched. No production, testwp, Timeweb or reference-site
visit, upstream main merge, tag or Release is authorized or performed.

## GitHub CI and verdict

Implementation/acceptance commit: `a7e327bc2222240e7cfe0840a50415a93ed71b71`.
[Feature CI run](https://github.com/itdream24/itd-cookies/actions/runs/38565155830):
**13/13 completed SUCCESS**, including build; no required skipped/cancelled jobs.

- PHP 7.4 and 8.5: PASS.
- Node (audit, ESLint, translation reproducibility, JS tests): PASS.
- Quality (Composer audit, PHPCS, PHPStan): PASS.
- WordPress 5.2 / 5.2.24 / 5.3.26 with PHP 7.4, latest with PHP 7.4 / 8.5: PASS.
- Legacy guards PHP 7.3 / 7.4: PASS.
- Gitleaks: PASS.
- Build, package inspector and reproducibility: PASS.

Final documentation commit receives its own unchanged-runtime CI; its URL and HEAD
are provided in the final handoff. Stable main/releases remain untouched.

Verdict: **`ITD_COOKIES_0_4_6_FOOTER_COLORS_READY`**.
