# Changelog

## 0.4.0-dev.1 — Unreleased

- Developer API for explicitly registered closed classic WordPress script-handle groups.
- Complete native data/before/external/after capture and per-group dependency replay after existing consent.
- Native provider ownership priority, bounded registration and safe developer diagnostics.
- No groups by default; no universal firewall, ancillary resource blocking, module replay or admin script editor.
- Existing consent schema 1, provider settings, legal integration and GitHub updater retained.

## 0.3.0 — 2026-10-06

- Analytics integrations: existing Yandex Metrika/GA4 retained; added Google Tag Manager and Microsoft Clarity.
- Marketing integration: Meta Pixel after Marketing consent, with one PageView per document.
- Unified provider loader and descriptive service registry; each built-in SDK/init attempted at most once per document.
- Strict provider-ID validation, grouped admin settings and dynamic Cookie Policy service descriptions.
- New GTM/Clarity/Meta integrations default OFF; existing 0.2.0 settings/consent preserved, schema remains 1.
- Clarity consentv2 follows Analytics/Marketing choices; revoking a category saves consent and reloads without its providers.
- GTM can run its own analytics/marketing tags. ITD Cookies gates the container, without automatically classifying each tag inside it; Google Consent Mode is not configured automatically.
- VK Ads is not supported pending verified official installation instructions. No universal external-script firewall.

## 0.2.0 — 2026-10-05

- New consent banner and category-card settings panel with descriptions and accessible toggles.
- Descriptive service registry filter; configured Yandex Metrika and GA4 shown in Analytics, empty categories marked unused.
- Dynamic `[itd_cookies_policy]` shortcode and explicit managed Cookie Policy page creation with permalink fallback.
- `[itd_cookies_legal_links]` shortcode, optional fourth legal link and automatic footer output OFF by default.
- Footer-friendly settings action, keyboard navigation, Escape and focus return, responsive layout.
- Existing 0.1.0 settings, consent schema/decisions, migration marker, provider gating and GitHub updater retained.

## 0.1.0 — 2026-10-05

- Cookie consent banner with accept all, reject and custom preferences.
- Necessary, functional, analytics and marketing consent categories.
- Yandex Metrika and Google Analytics 4 load after analytics consent.
- Persistent consent with configurable duration and policy version.
- `[itd_cookies_settings]` shortcode to reopen preferences.
- Up to three legal document links and five theme-font text sizes.
- One-time allowlisted migration of legacy ModuBricks consent/provider settings.
- Anonymous stable GitHub Releases updater with cached failure handling and native WordPress installation.
- Russian translation; WordPress 5.2+ and PHP 7.4+ compatibility.
