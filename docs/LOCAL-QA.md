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
