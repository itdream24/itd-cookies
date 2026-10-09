# ITD Cookies 0.4.2 — native GitHub updater refresh

Date: 2026-10-09. Branch: fix/itd-cookies-0.4.2-updater-refresh.
Fresh origin/main base: af3ccfd2a06696f909a0c297f77eac1ce410a7ca.
Candidate: 0.4.2-dev.1. Published v0.4.1 remains immutable.

Verdict: **PENDING_FULL_CI** (local acceptance PASS; no release authorization).

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
5. Core plugin update HTTP response produces the final checked record;
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
| PHP 7.4 PHPUnit | PASS — 49 tests, 480 assertions |
| PHP 7.4 compatibility, PHPCS, PHPStan | PASS |
| JS suite, ESLint | PASS — 42 JS tests |
| npm ci and npm audit | PASS — zero vulnerabilities |
| Composer strict validation and locked audit | PASS — no advisories |
| Russian translation compilation | PASS — 96 messages |
| Production-format build / package inspector | PASS — 18 files, PHP syntax valid |
| Two builds SHA-256 equality | PASS |
| Gitleaks committed history | PASS before implementation commit; rescan at commit |

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

## Real local Browser E2E — PASS

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
