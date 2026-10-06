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
[the 0.3.0 acceptance report](ITD-COOKIES-0.3.0.md). A pending installation or
unexecuted browser/failure/reset check is not a PASS.

## Verified permanent reference baseline (2026-10-06)

WordPress 7.1.2 / PHP 8.1.5 / MariaDB 10.6, ru_RU, Twenty Twenty-Five 1.5;
only official ITD Cookies 0.2.0 installed/active. Dedicated itd_cookies_local DB,
itd_cookies_qa localhost user with grants restricted to it. No production data.

Private task artifacts contain 030-local-qa/baseline: 12-table SQL dump,
491-file wp-content SHA-256 manifest/copy and settings/theme/plugin state.
The sibling baseline.ps1 supports Capture (refuses overwrite), Inspect and
Restore. Capture and repeated Restore were actually verified. Use the existing
OpenServer PHP 8.1 and verified WP-CLI 2.12.0. SQL export/import paths must use
forward slashes on Windows; prepend local MariaDB and Git usr/bin to this
process PATH only. No credentials are needed in command arguments.

Run the private baseline.ps1 -Mode Restore from its saved artifact directory.
It validates the configured local URL/DB, immutable dump/snapshot hashes and
exact absolute wp-content target before removal/import, then compares restored
files/settings/marker/plugins/theme. The QA root never changes. Clear local
consent test cookies before removing observer instrumentation and reload the
homepage afterward; browser verification is separate from the reset script.
Temporary installed helpers/pages/options/cache must be absent. Keep the
snapshot and private QA admin credentials outside Git/web-root for future work.

The 0.3.0 gate completed direct deployment, native ZIP upgrade, real public
GitHub 0.1.0 -> 0.2.0 update, actual local ModuBricks migration, browser category
matrix, responsive and safe failure checks. Final state is this restored
baseline, not the candidate. See the acceptance report for exact evidence.
