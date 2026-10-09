# Local QA procedure

## Scope

Runtime root: `C:\openserver\domains\itd-cookies.local`.
URL: `http://itd-cookies.local`. Dedicated database: `itd_cookies_local`.
Use the owner's existing OpenServer services. Keep the source checkout separate
from the installed plugin folder; no Git metadata belongs in WordPress runtime.
`AGENTS.md` contains the permanent remote-access policy.

## Setup

1. Inspect the site folder, service versions, database and domain registration.
   Do not overwrite an existing installation. Register only this local domain
   using OpenServer's normal mechanism; do not change unrelated projects.
2. Download current stable core from WordPress.org, verify core checksums,
   install with a dedicated local database/user, select ru_RU and a standard
   theme (prefer Twenty Twenty-Five). Do not copy production secrets.
3. Keep every additional DB/admin credential and tool outside both the document
   root and Git. Only the normal wp-config.php contains runtime DB credentials;
   never place a credential JSON/SQL file in the web root. Delete private setup
   credential files after the verified configuration is complete.
4. Define a local WP-CLI wrapper that invokes the selected OpenServer PHP and
   verified official wp-cli.phar with this exact --path. Windows WP-CLI has
   limited platform support; check exit status and files after every command.

## Baseline and reset

Store the baseline outside the document root and Git (QA_STATE): core/PHP
versions, theme/plugin list, JSON settings/marker/policy-option state, database
dump, a copy of wp-content and its sorted SHA-256 manifest. Use the existing
database credentials from wp-config.php rather than command-line passwords.

Suggested WP-CLI commands (the wrapper must restrict --path to this QA site):

```text
wp core version
wp theme list --format=json
wp plugin list --format=json
wp option get itd_cookies_settings --format=json
wp db export QA_STATE/baseline.sql
```

An absent plugin settings option is a valid baseline; record it as absent.
For reset, verify the absolute target is exactly the QA root and ensure the
snapshot exists before removing anything. Restore wp-content from the saved
copy, import only the dedicated QA database, then compare the original manifest,
settings/marker, active plugins/theme and managed page option. Clear only local
test consent cookies. Reload the local homepage and ensure no helper remains.
Keep credentials/dumps/HAR/raw cookies out of Git and the production ZIP.

## Acceptance

Install the immutable previous release ZIP, seed synthetic settings and consent,
then install/update the candidate with WordPress's native ZIP upgrader. Direct
file copy is a separate development smoke and never upgrade evidence.
Check settings, consent, migration marker, legal links, managed page, footer,
existing Yandex/GA4 fields and new provider defaults. Exercise consent choices,
reopen/save/reload/dedup/revoke, dynamic policy and actual SDK request counts.
Use 1440x900 and 390x844; add 768/425/320 if changing panel layout.

Failure tests (invalid ZIP, unavailable package URL, mocked GitHub timeout,
invalid IDs/consent/settings) run only after the baseline is recoverable. Do not
modify production updater code to enable a fixture. Temporary instrumentation
must be local-host restricted, synthetic, untracked, independently namespaced
and removed after testing. Verify cleanup, not only the attempted deletion.

For future published releases, use real anonymous GitHub metadata locally:
previous official stable ZIP -> settings/consent -> Check again -> native Update
to the next official release. No synthetic metadata counts as real updater E2E.
Do not publish a stable release merely to test an unpublished dev candidate.

## Current execution evidence

Actual versions, baseline location, results and final state belong in
[the 0.4.0 release report](ITD-COOKIES-0.4.0.md). A pending installation or
unexecuted browser/failure/reset check is not a PASS.

## Versioned immutable baselines (2026-10-08)

Current development baseline: **baseline-v0.4.0**. Official ITD Cookies 0.4.0
ACTIVE, only installed plugin. WordPress 7.1.2 / PHP 8.1.5 / MariaDB 10.6,
ru_RU, Twenty Twenty-Five 1.5. Dedicated itd_cookies_local DB and restricted
itd_cookies_qa localhost user; no production data. Providers OFF, default raw
settings restored, fresh-v1 marker, no managed policy page or QA helper.

Private QA_STATE/030-local-qa contains separate immutable snapshots:

| Snapshot | Use | wp-content files | SQL SHA-256 |
| --- | --- | --- | --- |
| baseline-v0.2.0 | Previous stable for 0.2.0 -> 0.3.0 upgrade | 491 | 30e83507104013fc3b82cb90673d9c4356c059478e2c11c030e562b4e8b49d5e |
| baseline-v0.3.0 | Previous stable for 0.3.0 -> 0.4.0 upgrade | 501 | 61eeb52f5597c8360d31a44edfc64c819d6b56e9bbe22aac5e0f816439873ab9 |
| baseline-v0.4.0 | Current stable for development/reset | 504 | c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93 |

Each has a dedicated SQL dump, wp-content copy/manifest/hashes and JSON
versions/theme/plugins/settings/marker/policy state. Original unversioned 0.2.0
baseline was retained; versioned 0.2.0 is an identical verified copy. It was not
replaced with 0.3.0. The new snapshot includes native WordPress theme-language
updates performed during the plugin upgrader flow; core remains 7.1.2.

Run the private baseline.ps1 from its saved artifact directory (PowerShell):

```powershell
# Previous stable, only when preparing an upgrade acceptance test:
.\baseline.ps1 -Mode Restore -Version 0.3.0
# Current stable for normal local development/reset:
.\baseline.ps1 -Mode Restore -Version 0.4.0
# Inspect the live isolated site without restoration:
.\baseline.ps1 -Mode Inspect -Version 0.4.0
# Capture only a NEW accepted version after cleanup; never overwrite:
.\baseline.ps1 -Mode Capture -Version 0.4.0
```

The last Capture command has already been run: repeating it refuses to overwrite
the existing snapshot. Default Version is 0.4.0, but use explicit versions in
acceptance scripts. Capture checks installed version against the requested
version. Restore verifies local URL/DB, immutable dump/copy hashes and the exact
absolute wp-content removal target before importing only this site's database.
Then it compares restored files/settings/marker/activity/plugins/theme.

Capture and actual Restore of baseline-v0.3.0 both PASS; all 491 files and SQL of
baseline-v0.2.0 were separately reverified unchanged. Use OpenServer PHP 8.1 and
verified WP-CLI 2.12.0. SQL paths need forward slashes on Windows; prepend local
MariaDB and Git usr/bin to the process PATH only. No password in CLI arguments.

Clear browser test consent before removing observer instrumentation; reset does
not clear browser cookies. Check homepage separately after restoration. Keep
private credentials/dumps/tools/evidence outside Git/package/web-root. No
installed observer/helper, private test option/cache/metadata or test page may
remain in a clean snapshot. A real upgrade gate must finish on the newly accepted
stable version and create a NEW versioned snapshot, retaining its predecessor.

Real browser WordPress 0.2.0 -> published 0.3.0, provider category matrix,
1440x900/390x844 UI, cleanup and current-baseline restore PASS. Exact commits,
CI/Release links and hashes are in [the release report](ITD-COOKIES-0.3.0.md).
Historical remote state is outside this procedure and must not be resumed.

## Historical 0.4.0 pre-merge regression (2026-10-08)

Native local ZIP 0.3.0 -> 0.4.0 and shipped Script Adapter browser/API/CSP/UI
regression PASS. The immutable 0.3.0 snapshot was actually restored both before
and after testing. Final 501 file hashes and all 12 database tables match; QA
consent/analytics cookies and temporary plugins/pages/options/counters are gone.
Current baseline remains **v0.3.0**, official plugin ACTIVE. No v0.4.0 baseline
exists yet; capture/actual Restore of a new clean official snapshot waits for
publication and real public updater acceptance. Previous snapshots remain intact.
See [the 0.4.0 stable gate report](ITD-COOKIES-0.4.0.md).

## Published 0.4.0 acceptance and current reset

Real GitHub Check again -> native WordPress 0.3.0 -> published 0.4.0 PASS,
including complete settings/consent preservation, shipped public adapter
category/error/ownership/CSP/UI/performance gates. Cleanup removed all temporary
plugins/pages/options/counters and QA cookies. Official 0.4.0 is the only
installed/active plugin. Capture and actual Restore of NEW baseline-v0.4.0 PASS:
504 file hashes and all 12 DB tables/schema/INSERT rows match. The prior 0.3.0
snapshot (501 files/dump) was reverified unchanged. No baseline was overwritten.
Use explicit `-Version 0.4.0` for current QA resets. Historical pre-merge reset
to 0.3.0 above is superseded by this published acceptance. Exact release/CI/tag
links, ZIP and database hashes are in [the 0.4.0 report](ITD-COOKIES-0.4.0.md).

## 0.4.1 publication / blocked real updater gate (2026-10-09)

Official v0.4.1 was published after accepted pre-release native ZIP/browser QA,
release-branch 9/9 CI and separate main 9/9 CI. Publication workflow 11/11 PASS;
anonymous ZIP/checksum match the reproducible build. However, native «Check
again» on restored official 0.4.0 did not invalidate cached v0.4.0 GitHub metadata,
so the real stable updater acceptance did not complete. See
[the 0.4.1 gate report](ITD-COOKIES-0.4.1.md) for reproduction and exact evidence.

Per the post-publication stop rule, no published tag/asset/runtime fix was made.
The local site was fully restored to immutable **baseline-v0.4.0**, official
0.4.0 ACTIVE. All 504 file hashes and 12 database schemas/all INSERT rows match;
QA cookies and temporary fixture/pages/options/counters are removed. Snapshot
SQL remains c247f4c0c96f71159a0dd806e08302f5b39def68415cd73a0c6f08b38bf47d93.
No baseline-v0.4.1 was created. Continue to use explicit Restore -Version 0.4.0
until a separate 0.4.2 task resolves and verifies the real updater gate.


## Published 0.4.2 / current immutable reset (2026-10-09)

Current baseline is **baseline-v0.4.2**, clean official **0.4.2 ACTIVE**.
Use baseline.ps1 -Mode Restore -Version 0.4.2 (explicit version; tool default
remains 0.4.0 for backward compatibility). New Capture and actual Restore PASS:
all 504 wp-content paths/hashes, raw settings/marker/activity/theme/locale and
all 12 table schemas/every INSERT row equal. Old baseline-v0.4.0 remains immutable.
New SQL hash 09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4;
canonical DB b3e80e63d2c2ba256bd9153c081927785c405ce950fde176f04d66d529b13f76.
No temporary observer/reference, policy page, counters/options/synthetic metadata
or QA consent cookies in the clean snapshot. Only normal public updater cache.

Publication workflow 11/11 PASS; downloaded official ZIP/checksum match local
reproducible SHA. Original old updater with genuine six-hour pre-publication
cache used assisted Upload/Replace, with settings/consent/policy preserved;
natural TTL expiry was NOT tested. New 0.4.2 manual refresh PASS (both caches,
one real GitHub HTTP request, correct public 0.4.2, six-hour ordinary reuse).
Separately, native Updates 0.4.0 -> 0.4.2 also PASS on the clean restored snapshot
which originally had no GitHub cache. This cache-miss test must not be presented
as natural expiry or automatic migration of the cached old instance.
Overall conservative verdict: ITD_COOKIES_V0.4.2_RELEASED_WITH_ASSISTED_UPDATE_ONLY.
See [the 0.4.2 release report](ITD-COOKIES-0.4.2.md) for independent A1/A2/B,
CI/Release links, hashes, screenshots and limitations. LOCAL-FIRST remains in
force. Next WP5.0/PHP7.4 compatibility task starts separately; minimum WP5.2
has not been changed. Never overwrite snapshots or published release assets.


## Current official 0.4.3 baseline — 2026-10-09

Current reset: **baseline-v0.4.3**, clean official **0.4.3 ACTIVE**. Use baseline.ps1 -Mode Restore -Version 0.4.3. Never overwrite older snapshots or call Capture on an existing baseline. Native real GitHub 0.4.2 → 0.4.3 update PASS in both ordinary expired-cache and genuine cached-manual-refresh scenarios; no synthetic HTTP metadata/assisted update.

Actual Capture then Restore: 504 files, 12 table schemas/all rows, settings/marker/activity/theme/locale identical. SQL SHA e04288464b2ac2c70aa3343c64230656faa28159cb4f9ae4bf4f6434f3aa4708; manifest SHA 241de2f533c82434ce9a284ac8dd3e64bd4c4098a2dc900f1fa3e4a47d8460ee; canonical DB 64bf751d605579d40456b390dd576aed2f4e0c2689e4cac24c3776a7a686f287. Old baseline-v0.4.2 hash 09c7599b3f8570444a95b27b5ce9380f1b6b3fdd096b49cc85341a2c00dc8aa4 unchanged. All QA plugins, pages, options, counters, synthetic IDs and isolated browser cookies removed. User Chrome not accessed. Detailed CI, snapshots, screenshots and caveats: [0.4.3 report](ITD-COOKIES-0.4.3.md). LOCAL-FIRST; remote QA/production is not authorized by this report.
