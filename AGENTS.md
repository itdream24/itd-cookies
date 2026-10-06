# ITD Cookies project rules

## LOCAL-FIRST QA POLICY

The primary development, integration and browser E2E environment is
`C:\openserver\domains\itd-cookies.local`, URL `http://itd-cookies.local`
(or its automatically configured OpenServer HTTPS equivalent).

Use a dedicated local database. Never use another project's database or copy
production credentials. Keep the Git checkout separate from the installed
`wp-content/plugins/itd-cookies` runtime copy. Use OpenServer services and WP-CLI
where available. Do not change global OpenServer configuration without need.

Before destructive QA, retain a restorable baseline: WordPress/PHP versions,
theme/plugins, database dump, wp-content file manifest/hashes, plugin files and
original ITD Cookies settings. Direct file synchronization is allowed for local
development; acceptance upgrades must use a production-format ZIP and the
native WordPress Plugin Upgrader.

Real GitHub updater and future stable-to-stable E2E run locally. Access to
api.github.com/github.com by this local WordPress updater is allowed.
Temporary local fixtures/observers use synthetic data, a private option
namespace, stay outside Git/production ZIP and are removed after acceptance.
Local QA operations within this environment are pre-authorized by the owner;
applicable tool-level confirmation rules still apply.

Remote QA is forbidden unless the current task contains the explicit phrase
`REMOTE FINAL SMOKE AUTHORIZED`. Do not contact testwp.itdream.su, Timeweb,
production or other owner WordPress installations for QA without that phrase.
This includes browser visits/reloads, SSH/FTP, ZIP deployment, plugin activity,
pages, helpers, database changes, updater and failure testing. Do not resume
remote QA automatically when a remote host recovers.

For a local-only acceptance PASS, remote QA requests to testwp.itdream.su = 0,
SSH/FTP operations against Timeweb = 0, production mutations = 0. Keep previous
remote test history separate from current local acceptance evidence.

Production is never a development or destructive QA environment. Deployment
requires explicit owner authorization for an already accepted stable release.
Do not create a second compatibility environment without a concrete need.
