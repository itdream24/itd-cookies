# ITD Cookies

Standalone WordPress consent preferences with Yandex Metrika and Google Analytics gated on analytics consent. GPL-2.0-or-later. WordPress 5.2+, PHP 7.4+.

Stable version: **0.4.2**. Candidate: **0.4.3-dev.1**. Download only a built `itd-cookies-X.Y.Z.zip` asset from this repository's Releases, never the source archive.

## Install and migrate

Upload the ZIP through Plugins → Add New → Upload Plugin. When migrating, first deactivate ModuBricks, then activate ITD Cookies. Activation copies a fixed allowlist of consent/provider settings once. It preserves the original options and existing new settings. Running both consent engines together is unsupported.

Settings → ITD Cookies provides legal links, provider IDs, consent duration/policy version and five text sizes. Theme fonts are preserved. `[itd_cookies_settings]` reopens preferences. `itd_cookies_allowed('analytics')` is a preference signal, not authorization. Existing cookie schema remains 1.

## Updates and external requests

The updater anonymously queries `https://api.github.com/repos/itdream24/itd-cookies/releases/latest` only on WordPress update checks/details requests in admin, cron or WP-CLI. Valid results cache for 6 hours; failures for 15 minutes. Native Dashboard → Updates → Check again clears the cache. Only strict stable `vX.Y.Z` tags and the matching built asset are accepted. The native WordPress upgrader downloads and installs over HTTPS. No token, custom installer, channel selector or custom signature service.

Built-in providers load only after their category is allowed: Analytics gates Yandex `mc.yandex.ru`, Google GA4/GTM `www.googletagmanager.com` and Clarity `www.clarity.ms`; Marketing gates Meta `connect.facebook.net`. Revoke consent to stop future loading and reload the page. This is not a legal compliance guarantee.

## Development

Node.js 22 (22.13+), Composer 2, PHP 7.4+ with mbstring:

```sh
composer install
npm ci
npm run build:i18n
composer test
composer phpcs
composer phpstan
composer compat
composer audit --locked
npm audit
npm run check
npm run build
npm run inspect
```

The deterministic ZIP uses an explicit runtime allowlist, fixed timestamps and one `itd-cookies/` root. Its inspector checks paths, main/header/constant versions and PHP syntax. Set `PHP_BINARY` to the PHP executable if it is not on PATH. Dev dependencies never ship.

## Local QA

The primary QA environment is OpenServer at `C:\openserver\domains\itd-cookies.local`, URL `http://itd-cookies.local`. See [Local QA procedure](docs/LOCAL-QA.md) and [project rules](AGENTS.md). Remote WordPress QA requires the explicit authorization phrase `REMOTE FINAL SMOKE AUTHORIZED`. GitHub updater checks run locally.

## Versions and releases

The first stable version is **0.1.0**: the consent API is young and does not yet warrant a long-term 1.0 compatibility promise. Stable tags must be strict `vX.Y.Z`; prereleases never reach ordinary users.

Align plugin header/constant, package.json/lock, readme, catalog metadata and changelog for every release. Run full CI on main before tagging. `release.yml` validates tag/version and main ancestry, reruns all CI gates, builds and inspects the ZIP, then publishes once with a SHA-256 file. Existing releases/tags are never overwritten. See `docs/ITD-COOKIES-02-GITHUB-UPDATER.md` for updater acceptance evidence.

## Attribution

Core code extracted from ITD's GPL-compatible ModuBricks project, accepted commit `485c6ad8b74ef46d9dc56b3e8bfeac8ed116adb2`. Copyright ITD contributors. Migration compatibility is retained; unrelated modules and deployment infrastructure are excluded. No third-party runtime libraries are bundled. Composer/npm libraries are development tools, with their licenses recorded in lock files and installed packages. The WordPress APIs and externally loaded provider scripts remain their respective authors' work.

## 0.2.0

Version: 0.2.0. Adds service descriptions, Cookie Policy, four legal links and an optional isolated footer. See docs/ITD-COOKIES-0.2.0.md for the acceptance gate. Stable v0.1.0 remains unchanged.

Shortcodes: [itd_cookies_settings], [itd_cookies_policy], [itd_cookies_legal_links]. Automatic footer output is off by default. Cookie Policy page creation is an explicit admin action and never overwrites existing page content.

Registry filter: itd_cookies_services receives an array of id, name, category, enabled and description entries plus sanitized settings. Extensions must implement their own consent-aware loading through the existing APIs; registry entries only describe services.

## 0.3.0

Release version: **0.3.0**; previous stable v0.2.0 remains immutable. See [acceptance report](docs/ITD-COOKIES-0.3.0.md).

Native Analytics integrations: Yandex Metrika, GA4, Google Tag Manager and Microsoft Clarity. Native Marketing: Meta Pixel. New integrations are off by default, including on legacy import. VK Ads is deferred until its official installation method can be verified.

The service registry adds provider_type; older descriptive extensions default to external. A closed adapter map loads built-in scripts only after their category is allowed and attempts each integration once per document. Revoking an optional category saves the choice and reloads; already executed third-party code cannot be unloaded reliably. Cookie schema stays 1, and upgrades preserve the existing consent version.

External SDK hosts also include www.clarity.ms and connect.facebook.net. ITD Cookies controls loading of the GTM container; tags inside depend on the container configuration and can require Marketing consent. Google Consent Mode is not configured automatically. Configure the container accordingly and avoid configuring the same service both natively and inside GTM. Clarity receives consentv2 with Analytics granted and ad storage following Marketing consent.

Built-in consent gating does not block arbitrary scripts inserted by themes/plugins. No universal firewall, arbitrary script input, vendor backend delivery guarantee or legal compliance guarantee is provided.
