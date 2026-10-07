# ITD Cookies 0.4.0 — Limited Script Adapters acceptance

Candidate: **0.4.0-dev.1**. Scope: **WP_HANDLES_ONLY**.

Branch: `feature/itd-cookies-0.4.0-limited-adapters`, created from fresh
`origin/main` at `3614cf7ff867e234feaf1c7ef7aeeac36d9e9b71`.
Research source: accepted R2 `51af5a42140ab324ac0f55a6b27965185fa0afb4`,
verdict `READY_FOR_LIMITED_SCRIPT_ADAPTERS_IMPLEMENTATION`.
The historical [firewall audit](ITD-COOKIES-0.4.0-FIREWALL-AUDIT.md) retains its
exact R2 Git blob `67cca0bf8bdd8dbe9c6fb62cdd9a67c31d86dbdd`.
Production was developed on main's architecture, independently of research POCs.

## Gate status

Local implementation, automated checks, browser acceptance and exact cleanup:
**PASS**. Feature CI: **PASS — all nine jobs**, including build and all four
WordPress combinations, on accepted runtime commit
`eb5639f82b99476841096af097cd687b2effbb8c`.
Verdict: `READY_FOR_ITD_COOKIES_0_4_0_RELEASE`, for **WP_HANDLES_ONLY** only.
No stable release, tag, merge into main or release workflow change is authorized
by this task or performed here.

## Production changes

Added runtime:

- `includes/functions-script-adapters.php`: public WordPress PHP functions.
- `includes/class-itd-cookies-script-adapters.php`: bounded registration,
  explicit ownership, closed DAG validation, native owned inline capture,
  inert templates, manifest and safe diagnostics.
- `assets/js/script-adapters.js`: category gating, whole-group validation,
  independent per-group asynchronous DAG replay and once-per-document attempts.

Changed runtime/metadata: `itd-cookies.php` (requires, hooks, candidate version),
`readme.txt`, Russian PO/MO. The existing consent engine, provider loader,
settings, frontend markup/CSS, updater and uninstall implementation are unchanged.
Consent schema remains **1**; no default adapter groups are registered.
`package.json` and the two root version fields in `package-lock.json` changed
only to the candidate version; dependencies are unchanged.

Build/check changes: translation source inventory, required ZIP inventory,
PHPStan test version, new PHP/JS tests, real WordPress smoke and benchmark,
local public-API reference fixture and CI integration of the latter checks.
`.github/workflows/release.yml` is unchanged. Experiments are not imported,
required, or packaged. Only the historical research report is carried over.

## Public contract

See [SCRIPT-ADAPTERS.md](SCRIPT-ADAPTERS.md) for the complete example and limits.

```php
itd_cookies_register_script_group( $group_id, array $args ); // true | WP_Error
itd_cookies_get_script_group_diagnostics();                 // safe snapshot
```

Register ordinary WP scripts/attachments on `wp_enqueue_scripts`, then groups
on `itd_cookies_register_script_groups` (priority 999 of that action).
Preparation starts after that dedicated hook returns, before
core dependency traversal and WP 5.2 datepicker localization at priority 1000.
It freezes owned handles and attachment bodies; print hooks validate that queue.
Native tags render at print time, preserving nonce filters attached later.

Required declaration: strict group ID, existing category, nonempty explicit
handle/provider-type lists, `resources = array()`. Dependencies come from the
actual WP queue. Limits: 32 groups, 32 handles/group, 256 handles total,
8 provider types/group. The API accepts no code or execution URL.

Supported: classic registered external handles, closed group dependencies,
head/footer in one group, native data/localization → before → SDK → after,
native tag attributes/nonce and exact native inline bodies, consent gating,
independent groups, safe PHP action/browser event/debug snapshot.
Native enabled provider types win; ownership is explicit, not hostname-based.

Unsupported: shared/outside dependency edges, source-less aliases, script
translations, conditional/module/nomodule scripts, concatenation, tracking
preload/pixel/noscript/iframe/fallback resources, late or post-freeze mutation,
arbitrary HTML/code discovery, global DOM/network interception, full-page
buffering, URL inference, universal firewall behavior, strict-dynamic,
Trusted Types and arbitrary CSP policies.

Late registration returns `LATE_REGISTRATION`, acquires no ownership and cannot
undo native output already printed. Integrators must handle registration errors
and prevent their own fallbacks. Denied supported groups stay inert; unknown
code stays native. Executed JS cannot be unloaded: revoke saves and reloads.

## Automated checks actually run locally

| Check | Actual result |
| --- | --- |
| PHPUnit, PHP 8.1.5 | PASS — 40 tests, 374 assertions |
| PHPUnit, PHP 7.4 | PASS — 40 tests, 374 assertions |
| Full JS suite | PASS — 41 tests, including 10 adapter cases |
| ESLint | PASS |
| PHPCS | PASS — 19 files |
| PHPStan level 5 | PASS |
| PHPCompatibilityWP, PHP 7.4– | PASS |
| Composer validate --strict | PASS |
| Composer audit --locked (Composer 2 / PHP 8.4) | PASS — no advisories |
| npm audit | PASS — 0 vulnerabilities |
| Translation compilation | PASS — 94 Russian messages |
| Gitleaks source export | PASS — 0 leaks in tracked/nonignored source export |
| Production build / inspector | PASS — 18 runtime files |
| Native WP 7.1.2 smoke, PHP 8.1 | PASS — zero, supported, missing-handle, missing-dependency, cycle, ownership, ancillary, unprinted, late |

The PHP unit suite covers strict public API inputs, all four categories and safe
diagnostics. JS tests execute the shipped controller with intercepted fixture
resources in jsdom, testing ordering, gating, nonce/body preservation,
independence, timeout/404, no duplicates, next-document denial and unsupported
whole-group tags. They do not substitute for the browser matrix below.

Real WordPress smoke runs the public registration API against native WP_Scripts,
checks exact version-specific data/before/after tags and nonce filters, dependency
failures before core recursion, unknown native output and zero-group equivalence.
CI runs it and the benchmark on all four existing WordPress matrix combinations:
5.2/PHP 7.4, 5.2.24/PHP 7.4, latest/PHP 7.4, latest/PHP 8.5.
All four combinations passed, including every adapter scenario and benchmark.
The latest WordPress used by CI was **7.1.3**, PHP **7.4.33 / 8.5.11**.

## Local browser acceptance

Only `http://itd-cookies.local`; WordPress 7.1.2, PHP 8.1.5, MariaDB 10.6,
ru_RU, Twenty Twenty-Five 1.5. Disposable reference plugin uses public ITD/WP
APIs, with local synthetic SDK endpoints and atomic per-case request counters.
Browser evidence combines server requests, executed trace, safe diagnostics and
fixture-rendered state. Consent is saved using the actual UI.

| Scenario on the final candidate | Result |
| --- | --- |
| Fresh | PASS — optional SDK requests 0; owned inline executions 0 |
| Reject and reload | PASS — 0 / 0 |
| Analytics only | PASS — library/dependent once, data→before→library→after→dependent→init; marketing 0 |
| Marketing only | PASS — SDK/init once; analytics 0 |
| Accept all | PASS — both groups once, correct per-group order |
| Save same choice + repeated consent event | PASS — no extra requests/init |
| Revoke + actual reload | PASS — request delta 0 and owned trace empty in denied next document |
| Native GA4 ownership + repeated save/event | PASS — native config 1, native SDK tag 1, conflicting adapter SDKs 0, marketing once |
| Missing handle/dependency, cycle | PASS — failed analytics 0, independent marketing once |
| Ancillary / unprinted | PASS — unsupported analytics 0, marketing once |
| 404 | PASS — failed request 1, after/dependent/init withheld; marketing once |
| Timeout | PASS — LOAD_TIMEOUT, SKIPPED_DEPENDENCY, FAILED; dependent/after withheld; marketing completed independently |
| Late registration | PASS — PHP LATE_REGISTRATION; no adapter runtime; already native scripts untouched |
| Nonce CSP | PASS — known owned inline/SDK order, both groups once, 0 CSP violations |
| Hash CSP | PASS — exact native owned bodies execute, both groups once, 0 CSP violations |
| Unknown code | PASS — local application + neighboring inline once; jQuery, theme, unknown CDN functional |
| Shortcodes / Russian UI | PASS — settings reopen, generated policy, four legal links and adapter service labels |

Timeout observations on the accepted runtime: marketing completed at **213.5 ms**,
analytics timed out at **5010 ms**. The deliberately 7-second SDK still executed later despite
removal of its script element. No dependent/after code ran. A timeout prevents
subsequent controlled steps, not a guaranteed browser network cancellation;
this limitation is part of the developer contract, not hidden by the verdict.

The initial hash-policy fixture omitted native WordPress importmap/emoji hashes
and blob worker permission. Those test-policy omissions were corrected in the
fixture, without modifying native code. Final nonce/hash profiles allow the
known WordPress bootstrap and worker, local controllers, SDKs and unknown CDN.
Hash values are calculated from the known native bodies before attachments are
captured. The nonce integration intentionally registers its attribute filter
after graph preparation to prove print-time preservation. Final CSP recapture,
all failure cases, the complete consent matrix and ownership/dedup were rerun on
the accepted runtime and final ZIP. Neither profile claims arbitrary CSP support.

Desktop and **390×844**: long repeated description, four legal links and Large
text size tested. On mobile, document width **375 ≤ 390**, banner/panel left 12,
right 363, client/scroll width both 334. No horizontal overflow; the theme font
remains **Manrope, sans-serif**. Vertical internal scrolling is needed for this
deliberately long content; actual Reject and Save clicks completed successfully.
Category cards and both adapter labels remain readable.

## Native upgrade: official 0.3.0 → candidate

Official 0.3.0 ZIP SHA-256:
`aa45c5a4ba2c4bc01e4dde683093504ac127db30556227f20a26666bc8c6f9f4`.

Installed official stable locally, seeded all five native provider configurations,
legal links, Large text, footer integration, managed policy page and existing
schema-1 consent. Upgraded through WordPress `Plugin_Upgrader` (WP-CLI install
with force/activate); no manual file substitution was used for acceptance.

PASS: version 0.4.0-dev.1, folder `itd-cookies`, active plugin, identical settings
SHA-256 `15b251bb78226b0d854bfbae95ab8e5f49641160334838d616adbb1989c1eda4`,
same `fresh-v1` migration marker, same managed page ID and full consent including
expiry. With zero registered groups there is no adapter asset/manifest/template,
no optional load after rejected consent, and unchanged native unknown scripts,
UI, shortcodes and legal output. No baseline data was used from a remote site.

## Bounded performance

30 iterations, medians, local PHP 8.1.5 / WP 7.1.2:

| Groups / handles | Registration | Graph preparation | Capture + manifest |
| --- | ---: | ---: | ---: |
| 0 / 0 | 0 ms | 0.0010 ms | 0.0019 ms |
| 3 / 6 | 0.0620 ms | 0.3750 ms | 0.2148 ms |

CI medians for 3 groups / 6 handles:

| WordPress / PHP | Registration | Preparation | Capture + manifest |
| --- | ---: | ---: | ---: |
| 5.2 / 7.4.33 | 0.0930 ms | 0.5279 ms | 0.0758 ms |
| 5.2.24 / 7.4.33 | 0.0522 ms | 0.3021 ms | 0.0470 ms |
| 7.1.3 / 7.4.33 | 0.0629 ms | 0.3700 ms | 0.2520 ms |
| 7.1.3 / 8.5.11 | 0.0691 ms | 0.3872 ms | 0.2630 ms |

The benchmark uses registration, a real WP queue, capture and manifest, not HTML
tokenization. Replay measures include actual SDK latency (above), so are not
presented as CPU overhead. This is measured evidence, not a cross-host SLA.

## Cleanup: immutable baseline-v0.3.0

**PASS**: baseline restored without recapture or overwrite; exact **501
wp-content files**, settings/markers, theme and plugin activity verified. ITD
Cookies 0.3.0 is active. Reference fixture directory, created policy page ID 7,
counter/cache/options and all other temporary database changes are absent.
Only QA consent/legacy/migration cookies were cleared; the fresh baseline banner
and working homepage were checked in the browser.

Final before-bootstrap database import/export comparison proves all **12 tables**,
CREATE schemas and every INSERT row identical to the immutable baseline.
Canonical table SHA-256:
`5e4de59abce9821678a8b76d5641ae6a1af8fb28811fecd22c3e4206b0759c1f`.
Baseline SQL SHA-256:
`61eeb52f5597c8360d31a44edfc64c819d6b56e9bbe22aac5e0f816439873ab9`.
The browser homepage check precedes this exact final DB comparison so routine
WordPress cache writes do not alter the comparison.

Current remote QA requests to testwp = **0**; Timeweb SSH/FTP = **0**;
production mutations = **0**. Main and stable tags/releases are untouched.
Private dumps, credentials, browser evidence and screenshots are outside Git.

## Candidate ZIP

`itd-cookies-0.4.0-dev.1.zip`: one root `itd-cookies/`, **18 runtime files**,
new runtime files and translations included, existing updater retained,
PHP syntax/metadata inspector PASS. Tests, fixture, docs, reports, experiments,
dependencies, credentials and private evidence excluded by the builder's
runtime allowlist. Build timestamps/order/permissions are fixed.

SHA-256: `d7d6f56a44fd3ba1b897036d0292e722d03b6fa35330e11f69369d9ac7f05af2`.
Repeated local builds produce this same hash. The CI build inspector reports
the identical ZIP hash and 18-file inventory. This is the archive installed by
the final native upgrade acceptance. The outer Actions artifact container has
its own hash, which is not the plugin ZIP hash.

## Feature CI

Accepted runtime [CI 37596606001](https://github.com/itdream24/itd-cookies/actions/runs/37596606001)
for `eb5639f82b99476841096af097cd687b2effbb8c`: **SUCCESS**.

| Job | Result |
| --- | --- |
| PHP 7.4 | PASS |
| PHP 8.5 | PASS |
| Quality: PHPCS, PHPStan, Composer audit | PASS |
| Node: audit, ESLint, translations, JS tests | PASS |
| WordPress 5.2 / PHP 7.4 | PASS |
| WordPress 5.2.24 / PHP 7.4 | PASS |
| WordPress latest (7.1.3) / PHP 7.4 | PASS |
| WordPress latest (7.1.3) / PHP 8.5 | PASS |
| Build / inspector / artifact | PASS |

Initial [37594945763](https://github.com/itdream24/itd-cookies/actions/runs/37594945763)
failed: both WP 5.2 jobs stopped in the cycle scenario and ended with runner
shutdown / exit 143. The native datepicker queue query at priority 1000 could
recurse through owned cycles before the original print-time preparation.
Registration/preparation now runs at 999, with an explicit regression assertion
before the native query. The replacement
[37595897343](https://github.com/itdream24/itd-cookies/actions/runs/37595897343)
passed all nine jobs.

Local CSP recapture then found that early tag rendering preceded late nonce
filters. Attachments now freeze early and native tags render at print time;
the real WordPress smoke checks a filter registered at priority 1000. The
accepted runtime CI and final browser nonce/hash profiles both pass.
This is a corrected lifecycle issue, not a waived infrastructure failure.

The final report/reference-fixture closure changes no production ZIP bytes.
Its branch-head CI is also required to pass all nine jobs before task completion;
that link and final HEAD are supplied in the task's final report. No release job
or stable tag is part of this gate.
