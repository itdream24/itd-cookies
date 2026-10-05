# ITD Cookies

Standalone WordPress consent preferences with Yandex Metrika and Google Analytics gated on analytics consent. GPL-2.0-or-later. WordPress 5.2+, PHP 7.4+.

Stable version: **0.1.0**. Download only a built `itd-cookies-X.Y.Z.zip` asset from this repository's Releases, never the source archive.

## Install and migrate

Upload the ZIP through Plugins → Add New → Upload Plugin. When migrating, first deactivate ModuBricks, then activate ITD Cookies. Activation copies a fixed allowlist of consent/provider settings once. It preserves the original options and existing new settings. Running both consent engines together is unsupported.

Settings → ITD Cookies provides legal links, provider IDs, consent duration/policy version and five text sizes. Theme fonts are preserved. `[itd_cookies_settings]` reopens preferences. `itd_cookies_allowed('analytics')` is a preference signal, not authorization. Existing cookie schema remains 1.

## Updates and external requests

The updater anonymously queries `https://api.github.com/repos/itdream24/itd-cookies/releases/latest` only on WordPress update checks/details requests in admin, cron or WP-CLI. Valid results cache for 6 hours; failures for 15 minutes. Native Dashboard → Updates → Check again clears the cache. Only strict stable `vX.Y.Z` tags and the matching built asset are accepted. The native WordPress upgrader downloads and installs over HTTPS. No token, custom installer, channel selector or custom signature service.

Optional providers load only after analytics consent: Yandex `mc.yandex.ru`, Google `www.googletagmanager.com`. Revoke consent to stop future loading and reload the page. This is not a legal compliance guarantee.

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

## Versions and releases

The first stable version is **0.1.0**: the consent API is young and does not yet warrant a long-term 1.0 compatibility promise. Stable tags must be strict `vX.Y.Z`; prereleases never reach ordinary users.

Align plugin header/constant, package.json/lock, readme, catalog metadata and changelog for every release. Run full CI on main before tagging. `release.yml` validates tag/version and main ancestry, reruns all CI gates, builds and inspects the ZIP, then publishes once with a SHA-256 file. Existing releases/tags are never overwritten. See `docs/ITD-COOKIES-02-GITHUB-UPDATER.md` for updater acceptance evidence.

## Attribution

Core code extracted from ITD's GPL-compatible ModuBricks project, accepted commit `485c6ad8b74ef46d9dc56b3e8bfeac8ed116adb2`. Copyright ITD contributors. Migration compatibility is retained; unrelated modules and deployment infrastructure are excluded. No third-party runtime libraries are bundled. Composer/npm libraries are development tools, with their licenses recorded in lock files and installed packages. The WordPress APIs and externally loaded provider scripts remain their respective authors' work.
