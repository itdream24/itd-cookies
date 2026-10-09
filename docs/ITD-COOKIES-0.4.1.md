# ITD Cookies 0.4.1 — UX fixes and acceptance

Date: 2026-10-09. Candidate: **0.4.1-dev.1**.

Branch: `fix/itd-cookies-0.4.1-ux`, created after a fresh fetch from
`origin/main` at `32e79276f8651d892b8ac5bb437d5f8d084d8e23` (clean checkout).
Local acceptance: **PASS**. Full candidate CI: **PASS — 9/9 jobs**.
Verdict: **READY_FOR_ITD_COOKIES_0_4_1_RELEASE**.
This accepts the development candidate; it does not authorize a merge/tag/Release.

## Actual behavior changes

- The registered Settings API callback is now `sanitize_submission()`.
  `sanitize()` / `get()` remain quiet normalization functions.
- Valid HTTPS and root-relative paths save. A URL at the exact current HTTP
  site origin (case-insensitive host, matching effective port, HTTP home URL)
  becomes a root-relative path, retaining query and fragment. Port 80 is
  equivalent to omitted port. A different host, subdomain, port or HTTP URL on
  an HTTPS site does not qualify.
- Nonempty rejected URLs retain that field's previously valid normalized URL
  and add a translated Settings API **error** naming the link number. Other
  submitted fields still save. WordPress does not display its generic success
  notice when these errors exist. Empty input deliberately clears a link.
- External HTTP, protocol-relative URLs, backslash/encoded leading authority
  tricks, malformed hosts/ports, credentials in URLs and executable schemes
  are rejected. The resulting same-origin HTTP path is checked again, so
  `http://same-site//external.example` cannot become a protocol-relative URL.
- WordPress still owns options.php capability checks, settings nonce and save
  redirect; no custom save endpoint or bypass was introduced. Legal URL helper
  text and errors use `itd-cookies`; Russian PO/MO were rebuilt.
- Category headings can wrap the fixed-width toggle onto another row before
  splitting a normal word. Unbreakable synthetic labels still have a bounded
  emergency wrap. No global font-size reduction; the theme font is inherited.
- Only the category list scrolls in normal settings viewports. The existing
  Save / Accept all / Back controls remain outside it, with a divider and no
  overlays or duplicate handlers. On mobile Save gets one row and the other
  two controls share the next row. The footer is 128 px at 320×568 / 110%,
  versus a 235 px category viewport; at 390×844 it is 109 px / 555 px.
- Viewport height and safe-area insets bound the panel. At heights ≤450 px
  (e.g. keyboard-reduced layouts), the complete panel scrolls instead of
  pinning a large block of buttons. Reopening resets category scroll.

## Browser E2E — OpenServer only

WordPress 7.1.2, PHP 8.1.5, ru_RU, Twenty Twenty-Five; actual browser clicks,
checkboxes, keyboard keys, DOM geometry, screenshots, resource inventory and
the existing local reference fixture. Only synthetic provider IDs/test data.

| Check | Result / observed evidence |
| --- | --- |
| Original 0.4.0 defects | Reproduced: HTTP input blanks the old URL with success notice; `Функциональные` splits at 320 px / 110%; long-panel Save is below the viewport. |
| Settings API URL saves | PASS: HTTPS, relative, own HTTP :80 with query/fragment, intentional blank. |
| URL rejection | PASS: external HTTP, malformed text, `//external`, own HTTP `//external`; each keeps old HTTPS and shows Russian error with **zero** success notices. |
| Native ZIP upgrade | PASS: WordPress Upload/Replace 0.4.0 → 0.4.1-dev.1; existing `itd-cookies` folder, ACTIVE, all 18 installed files equal candidate ZIP. Settings SHA, consent including expiry, schema 1 and fresh-v1 migration marker preserved. |
| Six responsive widths | PASS: 320×568, 360×640, 390×844, 425×800, 768×1024, 1440×900. No dialog horizontal overflow; Save visible. `Функциональные` is one line at 320/110%, toggle below and inside its card. |
| Short / long content | PASS: disabled native services, five enabled native services, 35 descriptive synthetic services, long localized names and unbroken label. Categories scroll; controls remain visible; no overlap. |
| Text sizes / theme font | PASS: 90/95/100/105/110%; h2 18/19/20/21/22 px, category 14.4/15.2/16/16.8/17.6 px, button 13.5/14.25/15/15.75/16.5 px; Manrope inherited. All presets checked at 320×568. |
| Low viewport | PASS: 320×350 uses full scrolling; Tab brings focused action inside viewport, no horizontal overflow. |
| Consent | PASS: Fresh, Reject, Analytics, Marketing, Accept all, Customize, Save, Back/Cancel, reload, revoke; cancelled pending category changes do not persist. |
| Keyboard | PASS: initial Escape returns to summary without consent; saved-choice Escape/Back returns focus to opener; Shift+Tab from first enabled switch reaches Back, Tab wraps to switch; footer Tab order retained. Reopening resets inner scroll. |
| Native provider network | PASS: no provider resources fresh/rejected/after revoke; four analytics SDKs after Analytics, Meta after Marketing. Repeat event retains one GA4 config and one loader. Invalid synthetic IDs do not establish working third-party accounts. |
| Admin / legal | PASS: title, long description, text size, 30-day lifetime, legal links and auto footer save. Cookie Policy created with official button (temporary ID 8); dynamic content and footer navigation work. All three shortcodes render, no raw shortcode text; reopening works. |
| Script Adapters regression | PASS: zero groups inert; public reference registration, analytics dependency order (`before → library → after → dependent → dependent-init`), independent marketing, one execution per document after repeat, category revoke/reload. 404 and timeout skip dependent/after while marketing proceeds. |
| CSP / Console | PASS: nonce/hash fixtures activate with zero observed CSP violations; captured warning/error console list empty. Deliberate 404/timeout appear in adapter diagnostics. Timeout may still finish its external SDK late, the documented 0.4.0 limitation; it never runs after/dependent or retries. |

Physical phone safe-area/IME behavior was not tested on hardware. Insets are
handled in CSS; the reduced-height browser test and 110% preset are explicit
emulations, not claims of a physical keyboard/device test. No universal
third-party firewall or business analytics-delivery claim is made.

Screenshots compared directly: before/after panel at 320, 390, 768, 1440;
additional 360/425, full visible functional category at 320, short/long content,
low-height keyboard layout, URL error, policy and cleanup. Original screenshots
and private synthetic evidence are attached to this task, not shipped in ZIP.

## Automated/local checks actually executed

| Check | Result |
| --- | --- |
| PHPUnit PHP 7.4.33 / 8.4 | PASS: 44 tests, 424 assertions each. Four new URL tests cover allowed/rejected values, bypasses, exact HTTP origin, four retained links, clearing and quiet reads. |
| JS tests | PASS: 42 tests, including reopening/scroll reset without consent loss. jsdom's existing navigation-not-implemented messages are test harness limitations, not failures. |
| ESLint | PASS. |
| PHPCS / PHPStan | PASS, no remaining warnings/errors. Initial style violations were corrected before acceptance. |
| PHPCompatibilityWP | PASS: testVersion 7.4- on production PHP. |
| Real WordPress URL integration | PASS on local WordPress; same assertions added to existing CI WordPress activation/reactivation smoke. |
| Composer audit / npm audit | PASS: no advisories / 0 vulnerabilities. Initial sandbox network/temp limitation was resolved with a permitted official-registry retry. |
| Gitleaks | PASS: repository history; final candidate commit scanned before push. |
| Translation build | PASS: 96 Russian messages; committed MO. |
| Build / inspector / PHP syntax | PASS: deterministic production-format ZIP, 18 files, `itd-cookies/` root, correct header/constant/readme version, updater and translations included; dev/tests/docs/fixtures excluded. |

`package.json` and the two root version fields in `package-lock.json` change
only from 0.4.0 to 0.4.1-dev.1; no dependencies were updated. Script Adapters,
services/providers, consent schema/migration and updater runtime files have no
changes. consent.js changes only the inner-scroll reset; existing handlers are
retained. PHP header/constant, readme, changelog and PO metadata are synchronized.

ZIP: `itd-cookies-0.4.1-dev.1.zip`

SHA-256: `06afb5c9172281a133eef9eb4c5d290cea149f3eab59e35c825ced8103e5733e`

## Cleanup and immutable baseline

PASS: baseline-v0.4.0 was actually restored and verified both before and after
testing. All 504 wp-content paths/hashes and the 18 official plugin files match;
official 0.4.0 remains the only installed plugin and ACTIVE. The 12-table schema
and **all** INSERT rows match the original dump after final import/export;
settings, fresh-v1 marker, absent policy option and plugin activity match.
Fixture, QA counters/options, uploaded candidate files and page ID 8 are absent.
QA consent/provider cookies were cleared and the cleanup endpoint subsequently
reported zero names. Final browser is signed out and shows the original fresh
banner. No WordPress request was made after the final database comparison.

Immutable baseline SQL SHA-256:
`c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93`.
Canonical schemas/all-row SHA-256:
`5ef5e08a66edc59ce7f0cb546ce2e5fb286b70a78c93ae1682ce4241e496a234`.

Remote QA requests = 0; Timeweb/FTP operations = 0; production mutations = 0.
main, published v0.4.0 tag/Release/assets and old baselines were not changed.

## Full CI

PASS on code/acceptance commit `695611e7679eb390d4718261a5cca05c1c44862f`:
[CI 37895915175](https://github.com/itdream24/itd-cookies/actions/runs/37895915175).
All nine jobs completed successfully: PHP 7.4 and 8.5, quality, node,
WordPress 5.2/PHP7.4, 5.2.24/PHP7.4, latest/PHP7.4, latest/PHP8.5 and build.
The separate report-only follow-up runs the same full CI before final handoff;
its final run URL/status is retained in the task acceptance evidence.
No merge, stable version/tag or Release was produced.

## Stable 0.4.1 release gate

Accepted implementation commit: `016e169c3d14978d81b554fe29e59dc278502571`.
Release branch: `release/itd-cookies-0.4.1`, created directly from that commit.
Only version/changelog/readme/translation metadata changed for stable 0.4.1;
production runtime logic is identical to the accepted 0.4.1-dev.1.
Stable ZIP, release/main CI, local pre-release acceptance, publication, real
GitHub update and the new immutable baseline are pending. No stable verdict yet.
