=== ITD Cookies ===
Contributors: itdream24
Tags: cookies, consent, analytics, privacy
Requires at least: 5.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie choices with consent-aware Yandex Metrika and Google Analytics.

== Description ==

Necessary, functional, analytics and marketing preferences. Analytics providers load only after analytics consent. Visitors can reopen settings using [itd_cookies_settings]. Five text sizes retain the theme font. Includes Russian translation. Category cards describe configured services. [itd_cookies_policy] provides configuration-based Cookie Policy information; [itd_cookies_legal_links] outputs configured document links and the settings action. Cookie Policy page creation is explicit. The optional automatic footer is off by default. This plugin does not scan every cookie on a site.

Distributed through GitHub Releases, not the WordPress.org directory. Automatic updates accept stable releases only. Update checks contact api.github.com from admin/cron, with cached results. Packages download from GitHub over HTTPS through the native WordPress upgrader. No GitHub token is required.

When enabled, Yandex Metrika (mc.yandex.ru), GA4 and Google Tag Manager (www.googletagmanager.com), and Microsoft Clarity (www.clarity.ms) load after Analytics consent. Meta Pixel (connect.facebook.net) loads after Marketing consent. Configure provider IDs in Settings > ITD Cookies and legal documents according to your site. New GTM/Clarity/Meta integrations are off by default. VK Ads is not supported.

Limited Script Adapters are a developer API for explicit, closed classic WordPress script-handle groups. They retain native data/localization, inline before, external SDK and inline after, replay dependencies after the selected consent category, isolate failures and protect native provider ownership. Each group activates at most once per document. No groups are registered by default; existing settings and consent schema 1 are retained.

Unsupported: arbitrary hardcoded script tags, raw HTML firewall, dynamic DOM injection, modules/import graphs, preload, pixels, noscript, iframes, custom arbitrary JS and universal tracker detection. A timed-out SDK request may still complete or execute later; dependent/after code is withheld, a failure diagnostic is recorded and the group is not retried in that document. See the repository developer documentation for the supported nonce/hash CSP contract.

== Installation ==

Upload the built itd-cookies ZIP in Plugins > Add New > Upload Plugin. If migrating from ModuBricks, deactivate ModuBricks before activating ITD Cookies. The migration copies only consent/provider options once and preserves the original options.

== Frequently Asked Questions ==

= Where are updates published? =
https://github.com/itdream24/itd-cookies/releases . Only stable vX.Y.Z releases with a built itd-cookies-X.Y.Z.zip asset are accepted.

= Is this a legal compliance guarantee? =
No. Site owners must choose their policies and provider configuration.

== Changelog ==

= 0.4.0 =
* Limited developer adapters for closed classic WordPress script handles and native inline attachments.
* Existing consent categories and native provider ownership are retained; no groups are registered by default.
* Ancillary tracking resources, modules and universal external-script blocking are unsupported.


= 0.3.0 =
* Analytics: existing Yandex Metrika/GA4 retained; added Google Tag Manager and Microsoft Clarity after Analytics consent.
* Marketing: Meta Pixel after Marketing consent.
* Unified provider loader/service registry, script/init deduplication, strict provider-ID validation and dynamic Cookie Policy integration.
* Existing 0.2.0 settings and consent preserved; schema 1; new integrations OFF by default.
* Revoked categories reload without their providers; Clarity consentv2 follows the selected categories.
* GTM can run analytics/marketing tags; ITD Cookies gates its container without automatically classifying individual tags. VK Ads is not supported.

= 0.2.0 =
* New consent UI with category cards, descriptions, configured services and accessible toggles.
* Extensible descriptive service registry showing enabled Yandex Metrika and GA4 in Analytics.
* Dynamic [itd_cookies_policy] and managed Cookie Policy page with permalink fallback.
* [itd_cookies_legal_links], fourth optional legal link and opt-in automatic footer.
* Keyboard navigation, Escape/focus handling and responsive layout.
* Existing 0.1.0 settings, consent and migration marker preserved.

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

== Provider limitations ==
ITD Cookies controls its built-in integrations and explicitly registered closed classic WordPress script-handle groups. It does not automatically block unknown theme/plugin scripts or tracking resources. Limited adapters are a developer API, not a universal cookie firewall. GTM container loading follows Analytics consent; its tags may require Marketing consent and depend on container configuration. Google Consent Mode is not configured automatically. Avoid configuring the same tracker both natively and inside GTM.
