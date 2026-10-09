# ITD Cookies 0.4.2 — native GitHub updater refresh

Date: 2026-10-09. Branch: fix/itd-cookies-0.4.2-updater-refresh.
Fresh origin/main base: af3ccfd2a06696f909a0c297f77eac1ce410a7ca.
Candidate: 0.4.2-dev.1. Published v0.4.1 remains immutable.

Verdict: **READY_FOR_ITD_COOKIES_0_4_2_RELEASE** — exact final artifact Browser E2E and cleanup PASS; implementation CI 9/9 PASS. This is implementation readiness, not permission to merge, tag or publish. Final report-commit CI is checked after push.

## Cause and native execution order

Inspected official WordPress 5.2 source and installed official WordPress 7.1.2.
Both register wp_update_plugins on load-update-core.php at the default priority
10. wp-admin/admin.php runs admin_init before the screen's load-* action.
wp-admin/update-core.php then handles force-check for wp_version_check, which
checks Core. It does not guarantee deletion of the plugin update transient or
our independent GitHub metadata cache.

Previously includes/class-itd-cookies-updater.php invalidated its GitHub cache
only through delete_site_transient_update_plugins. The native manual screen
path did not fire that deletion. A valid old GitHub result remained cached for
six hours; WordPress could also short-circuit wp_update_plugins for one minute
on the Updates screen, or rebuild no_update from stale GitHub data. The old unit
test explicitly called clear_cache and therefore did not exercise native hooks.

The fixed manual path is:

1. Core authenticated admin bootstrap/admin_init. If expired metadata is checked
   here, remember its fresh HTTP result for this request.
2. load-update-core.php priority 1: validate native hook, admin context, exact
   scalar force-check=1, update_plugins capability; exclude AJAX/REST. Invalidate
   GitHub metadata and WordPress update_plugins through native transient APIs.
3. If admin_init already fetched in this request, restore only that fresh result
   after invalidation. Do not reuse an older persistent-cache result.
4. Core wp_update_plugins at priority 10: the absent WordPress update record
   bypasses its recent-check short circuit. Its first transient write can have
   no checked versions; the adapter correctly skips this provisional record.
5. Core plugin update HTTP response produces the final response array. Modern Core also provides checked; old WP 5.2/5.2.24 can omit it after an uncached check. Only on this authenticated manual path, recover our installed version in checked when the entire checked list is absent. The provisional last_checked-only record is still skipped;
   pre_set_site_transient_update_plugins applies our existing adapter. A newer
   GitHub release moves the plugin from no_update to response.

manual_refreshed prevents repeated invalidation in one instance/request; fresh
request_release avoids a second API request following an admin_init check.
Ordinary cache TTLs remain 21600 seconds for valid releases and 900 seconds for
errors. No explicit new HTTP call was added to the early hook. The existing Core
manual link has no nonce; its authenticated screen and update_plugins permission
are used, without introducing an endpoint, changing Core or removing checks.

## Scope

Production change is restricted to includes/class-itd-cookies-updater.php and
0.4.2-dev.1 version/readme/changelog/translation metadata. package.json changed
only its version; package-lock changed only the root and root-package versions.
No dependency updates. Consent, providers, Script Adapters, UI, legal integration
and migration runtime have no diff from main. Packaging scripts are unchanged.
CI adds one controlled native-hook integration invocation within the existing
four WordPress jobs; the job count and matrix stay unchanged.

## Automated checks actually executed locally

| Check | Result |
| --- | --- |
| PHP 7.4 PHPUnit | PASS — 50 tests, 494 assertions |
| PHP 7.4 compatibility, PHPCS, PHPStan | PASS |
| JS suite, ESLint | PASS — 42 JS tests |
| npm ci and npm audit | PASS — zero vulnerabilities |
| Composer strict validation and locked audit | PASS — no advisories |
| Russian translation compilation | PASS — 96 messages |
| Production-format build / package inspector | PASS — 18 files, PHP syntax valid |
| Two builds SHA-256 equality | PASS |
| Gitleaks committed history | PASS — 45 commits scanned before final acceptance; final report commit rescan follows |

Unit regression models installed 0.4.0, cached GitHub 0.4.0 and stale no_update;
controlled latest 0.4.1 becomes response and removes no_update. Negative cases:
missing/invalid/array flag, no capability, ordinary visit, frontend, unrelated
hook, AJAX, REST, GitHub failure/negative TTL and repeated calls. Both successful
and failed HTTP checks before the screen hook are reused once with the proper TTL.

Controlled integration uses real Core do_action/load hook, wp_update_plugins,
transient storage and final filtering, with HTTP mocks and isolated old-version
plugin-header cache. It asserts both caches absent at priority 9, update notice
0.4.1 on modeled 0.4.0, no stale no_update, exact hook/HTTP order and one GitHub
request. A local-host guarded private copy ran successfully on installed WP
7.1.2 after the public Browser E2E; this is not a public stable upgrade. CI will
run the committed disposable-only test on WP 5.2, 5.2.24 and latest PHP 7.4/8.5.

## Earlier f42 candidate Browser E2E — PASS (historical evidence)

Only itd-cookies.local was used. baseline-v0.4.0 was actually restored and its
immutable hashes verified before testing. Synthetic settings and consent were
created through the real admin/banner. Native WordPress Upload/Replace updated
official 0.4.0 to 0.4.2-dev.1. All 18 installed hashes equal the ZIP, canonical
folder remains itd-cookies, plugin ACTIVE. All raw settings/legal links/provider
fields, marker and prior activity are equal before/after; saved rejection remains
effective after reload. No consent/provider/Script Adapter changes were made.

Seeded controlled stale metadata for the known public v0.4.0 without any HTTP
mock. A temporary local-only observer counted actual http_api_debug responses
and recorded early/final native hooks; it did not filter HTTP or replace metadata.

- Ordinary Updates visit: both cached records retained at priority 2; GitHub
  requests=0; cached version stays 0.4.0.
- Real «Проверить снова» click: both cached records absent at priority 2; native
  provisional write has checked=false; exactly one actual GitHub request returns
  HTTP 200; final checked record and metadata contain published v0.4.1.
- response has no ITD Cookies entry; no_update has version 0.4.1. This is correct
  because installed 0.4.2-dev.1 is newer. No downgrade is offered.
- Console warnings/errors: none on the manual-check page.

This proves the candidate refreshes real public metadata, NOT a future real
stable-to-stable upgrade. Update notice on an older modeled version is proven
separately by the controlled integration test.

Private screenshots and observer/cache evidence: QA_STATE/042-updater, including
native-zip-upgrade.jpg, ordinary-admin-cached.jpg, public-api-manual-refresh.jpg
and its JSON record. Credentials, dumps, raw cookies and QA tools stay outside
Git/package. Candidate ZIP: itd-cookies-0.4.2-dev.1.zip; SHA-256:

f42c1788ff087b48e5e9c25184661be1be1cba88ad96646a7def2763e8008654

## Cleanup — PASS

Cleared local consent/legacy marker QA cookies and verified an empty cleanup
result before removing instrumentation. Actually restored baseline-v0.4.0.
Only official v0.4.0 ACTIVE remains; all 18 official runtime hashes match its ZIP.
All 504 wp-content paths/hashes equal the unchanged snapshot. All 12 database
schemas and every INSERT row match baseline. Original settings/marker/activity,
absent policy option, default banner and theme state restored. Temporary MU
observer, upload/test records, options/caches and synthetic settings are absent.
No WordPress request was made after the final exact database comparison.

Immutable SQL SHA-256:
c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93

Canonical all-table comparison SHA-256:
5ef5e08a66edc59ce7f0cb546ce2e5fb286b70a78c93ae1682ce4241e496a234

## Future release limitation

Installed official 0.4.0 and 0.4.1 still contain the old manual-refresh defect.
Publishing 0.4.2 will not repair code before it is installed. Immediate discovery
through Check again on an old cached installation is not guaranteed. A future
real stable E2E must wait for natural cache expiry or explicitly document assisted
recovery; manual transient deletion is never evidence that the old release has
fixed native refresh behavior. No such stable-upgrade claim is made here.

No merge/main push, tag or Release in this task. v0.4.1 and older published assets
remain unchanged. No Timeweb, testwp or production access; LOCAL-FIRST maintained.

## CI execution history

Initial run https://github.com/itdream24/itd-cookies/actions/runs/37902096673: PHP, quality and node PASS; four WordPress jobs FAIL and build skipped. The new test included an ordinary-view WordPress.org HTTP event in the expected manual-only trace. Its checks for one GitHub request and refreshed update metadata passed before the trace assertion. Resetting only test trace between the ordinary and manual phases fixes that unrelated-event assumption. Production source and accepted ZIP SHA-256 are unchanged. Full CI on the corrected test remains required.

Run https://github.com/itdream24/itd-cookies/actions/runs/37902472907: six jobs PASS (including both latest WordPress jobs); WP 5.2/5.2.24 FAIL and build skipped. Official old Core omits checked in its final result after an uncached check. A bounded compatibility path now adds only this active plugin’s installed version to that final manual-check record, identified by its response array, never to the provisional record or ordinary requests. New unit regression covers these distinctions. Updated ZIP SHA-256: 6c20b96becb1f1d00374bb0d291d45efd24a07692bb4a6c8946b8318c5b050ed. Earlier f42 ZIP browser evidence does not cover this additional path; repeat acceptance and full CI are required.

Run https://github.com/itdream24/itd-cookies/actions/runs/37903587848 passed metadata refresh on old Core but failed the synthetic repeated-hook trace: old Core legitimately contacted WordPress.org again. The test now asserts first-call hook order separately from repeated-call GitHub deduplication, without suppressing Core HTTP or reducing the one-GitHub-call gate. No additional production diff or ZIP change.

Current implementation commit: 27c8e1e85b31259c4e72993953cb5ee08b5acbee. Full CI https://github.com/itdream24/itd-cookies/actions/runs/37903786635 completed SUCCESS: php (7.4), php (8.5), quality, node, wordpress (5.2, 7.4), wordpress (5.2.24, 7.4), wordpress (latest, 7.4), wordpress (latest, 8.5), build — all nine PASS. Final production-format ZIP SHA-256: 6c20b96becb1f1d00374bb0d291d45efd24a07692bb4a6c8946b8318c5b050ed; inspector 18 files PASS and two builds equal. Repeat local Browser E2E on exactly this artifact remains pending; previous browser evidence cannot be used to claim final-candidate acceptance.

## Final 6c20 artifact acceptance — PASS (2026-10-09)

This section supersedes earlier pending status without removing earlier test
results or failure history. The pre-E2E uncommitted report was copied to private
QA_STATE/042-updater/final-acceptance/report-before-e2e.md before any changes.
Runtime source stayed exactly at accepted commit
27c8e1e85b31259c4e72993953cb5ee08b5acbee. Candidate ZIP remains:

itd-cookies-0.4.2-dev.1.zip

SHA-256: 6c20b96becb1f1d00374bb0d291d45efd24a07692bb4a6c8946b8318c5b050ed

### Baseline, native upgrade and settings

Actually restored immutable baseline-v0.4.0 before testing; verified 504 files
and all 12 schemas/INSERT rows. Synthetic settings were saved through Settings
API on official 0.4.0: custom title/description, large 105% text, HTTPS privacy
link, relative local link, synthetic Metrika/GA4 IDs with both providers OFF.
Created temporary Cookie Policy page ID 7 through the real plugin admin button.
Saved Reject and reloaded before installation.

The real WordPress Upload/Replace flow upgraded official 0.4.0 to the exact final
0.4.2-dev.1 ZIP. Canonical folder itd-cookies, ACTIVE, all 18 runtime file hashes
equal ZIP. All raw settings, migration marker fresh-v1, policy ID and active
plugins equal before/after. Consent schema/version/expiry/categories equal
before/after; rejected banner stays hidden. Candidate Settings API save round
trip was tested through real clicks; restored synthetic values equal pre-upgrade.

### Public metadata refresh and capability — PASS

Temporary local observer instrumented the entire request, including admin_init,
via http_api_debug and native hooks. No pre_http_request, response mock, synthetic
release feed or plugin runtime alteration. Controlled stale cache was populated
with metadata for the known published v0.4.0; only the starting cache is synthetic.

| Real browser scenario | Observed result |
| --- | --- |
| Ordinary Updates visit with stale metadata | GitHub calls 0; caches present at priority 2; metadata 0.4.0 and original timeout retained |
| Native Check again click | Both caches absent at priority 2; provisional checked=false; exactly one GitHub HTTP 200 response across full request; final metadata and no_update 0.4.1 |
| Ordinary view after refresh | GitHub calls 0; metadata 0.4.1; timeout unchanged, roughly six hours remaining |
| Subscriber force-check request | Native access-denied page; calls 0; metadata, timeout and no_update remain 0.4.0; no early invalidation |

Subscriber test temporarily reduced only local synthetic QA user rights, then
restored administrator role. Final baseline DB comparison includes user roles.
No downgrade is offered: candidate 0.4.2-dev.1 is newer than published v0.4.1.
The actual HTTP response supplied published download URL/changelog; this proves
public metadata retrieval, not future stable-to-stable upgrade. The separate
controlled Core CI regression proves the older installed-version update notice.

### Regression smoke — PASS

- Fresh, saved Reject/reload, Customize, Analytics-only, Accept all, reopen,
  Back/Cancel, Save and revoke with native page reload exercised by real clicks.
- Escape closes panel and returns focus to Settings shortcode button; Tab from
  Save moves to Accept all. Necessary stays enabled.
- Existing public-API reference fixture: no owned SDK calls before consent;
  Analytics-only library/dependent each called once, marketing absent. Trace:
  before/localized data, library, after, dependent, dependent-init.
- Repeated consent event keeps SDK counters/trace unchanged. Revoke preserves
  false categories and reloads without owned SDK execution (trace empty).
- Accept all activates analytics and marketing. Cumulative counters reflect
  the earlier analytics scenario; each current-page initializer occurs once.
- Deliberate local SDK 404: LOAD_ERROR, SKIPPED_DEPENDENCY, FAILED for analytics;
  dependent not requested; independent marketing activates. Expected injected
  failure is not an unexpected plugin error.
- Zero registered groups: empty diagnostics/counters; native application,
  neighbor, jQuery and theme still run once. No native providers enabled.
- All three shortcodes rendered. Real Cookie Policy page and legal links work;
  HTTPS/relative/generated policy URLs retained. No external client IDs used.
- Screenshots inspected at desktop 1440x900 and mobile 390x844. Mobile document
  scrollWidth equals clientWidth 375 (390 viewport minus scrollbar); panel width
  351, scrollWidth=clientWidth=349. No horizontal overflow, all action buttons
  visible, category area scrolls. Functional label remains whole.
- Observed PHP warnings/errors empty, shutdown last_error null in capability
  probe; browser Console warnings/errors empty in checked states, fixture CSP
  violations empty. This was a targeted regression smoke, not a full new audit
  of every third-party provider or all CSP policies.

Private evidence: final-acceptance/*.json and native-upgrade.jpg,
public-manual-refresh.jpg, desktop-fresh.jpg, desktop-panel.jpg,
mobile-fresh.jpg, mobile-panel.jpg, insufficient-capability.jpg,
restored-official-040.jpg. Screenshots/QA data/tools are excluded from Git/ZIP.

### Final cleanup — PASS

Real fixture reset cleared consent/legacy QA cookies; DOM evidence consent=null.
Actually restored immutable baseline-v0.4.0. Default fresh banner verified in
browser after restore, then final exact DB restore/export comparison executed.
No subsequent WordPress request after that final comparison.

Official 0.4.0 ACTIVE, canonical plugin directory, all 18 official file hashes
match published 0.4.0 ZIP. All 504 wp-content paths/hashes equal snapshot. All 12
table schemas and every INSERT row equal baseline. Settings/marker/activity/user
roles restored; temporary reference plugin, MU observer, Cookie Policy page 7,
uploads, private QA options/counters and seeded metadata removed.

Immutable SQL hash remains c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93.
Canonical all-table hash remains 5ef5e08a66edc59ce7f0cb546ce2e5fb286b70a78c93ae1682ce4241e496a234.
No main merge/push, tag/Release, stable asset changes or remote WordPress access.

Implementation CI: https://github.com/itdream24/itd-cookies/actions/runs/37903786635
— all nine jobs PASS. Final documentation commit is pushed only to the current
fix branch; its separate full CI result is verified in the completion report.
