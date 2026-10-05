=== ITD Cookies ===
Contributors: itdream24
Tags: cookies, consent, analytics, privacy
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0-dev.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie choices with consent-aware Yandex Metrika and Google Analytics.

== Description ==

Necessary, functional, analytics and marketing preferences. Analytics providers load only after analytics consent. Visitors can reopen settings using [itd_cookies_settings]. Five text sizes retain the theme font. Includes Russian translation.

Distributed through GitHub Releases, not the WordPress.org directory. Automatic updates accept stable releases only. Update checks contact api.github.com from admin/cron, with cached results. Packages download from GitHub over HTTPS through the native WordPress upgrader. No GitHub token is required.

When enabled and consented to, Yandex Metrika loads mc.yandex.ru and Google Analytics loads www.googletagmanager.com. Configure provider IDs in Settings > ITD Cookies. No providers load before analytics consent. Configure legal documents according to your site.

== Installation ==

Upload the built itd-cookies ZIP in Plugins > Add New > Upload Plugin. If migrating from ModuBricks, deactivate ModuBricks before activating ITD Cookies. The migration copies only consent/provider options once and preserves the original options.

== Frequently Asked Questions ==

= Where are updates published? =
https://github.com/itdream24/itd-cookies/releases . Only stable vX.Y.Z releases with a built itd-cookies-X.Y.Z.zip asset are accepted.

= Is this a legal compliance guarantee? =
No. Site owners must choose their policies and provider configuration.

== Changelog ==

= 0.1.0 =
* Cookie consent banner with accept all, reject and custom preferences.
* Necessary, functional, analytics and marketing consent categories.
* Yandex Metrika and Google Analytics 4 load after analytics consent.
* Persistent consent with configurable duration and policy version.
* `[itd_cookies_settings]` shortcode to reopen preferences.
* Up to three legal document links and five theme-font text sizes.
* One-time allowlisted migration of legacy ModuBricks consent/provider settings.
* Anonymous stable GitHub Releases updater with cached failure handling and native WordPress installation.
* Russian translation; WordPress 5.2+ and PHP 7.4+ compatibility.
