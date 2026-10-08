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
