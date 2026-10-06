# ITD Cookies 0.3.0 — Provider Integrations

Branch: feature/itd-cookies-0.3.0. Baseline origin/main: 2705b9f51e71e9ab1db778fd32591c81239ebcf6. Candidate: 0.3.0-dev.1. Published stable v0.2.0 remains unchanged. No stable tag/release is created in this stage.

## Provider architecture

ITD_Cookies_Services is the descriptive registry for both category cards and dynamic policy. Each entry exposes id, name, category, enabled, description and provider_type. Older filtered entries without provider_type become external. Runtime configuration is generated only for validated native IDs; descriptive extensions cannot supply executable snippets or arbitrary SDK URLs.

assets/js/providers.js contains a closed map of isolated adapters. consent.js uses one loader for stored consent and all save/accept paths. Each provider type is attempted at most once per document. Existing SDK elements are respected without initializing them again. Unknown types, invalid IDs and category mismatches fail closed. Local provider script is an explicit dependency of the consent controller.

## Built-in integrations

| Service | Type | Category | ID validation | Bootstrap |
| --- | --- | --- | --- | --- |
| Yandex Metrika | yandex | analytics | existing nonzero numeric string, 1–15 digits | mc.yandex.ru/metrika/tag.js, existing init/options |
| GA4 | ga4 | analytics | existing G- plus 4–32 uppercase letters/digits | googletagmanager.com/gtag/js, existing js/config |
| Google Tag Manager | gtm | analytics | GTM- plus 4–32 uppercase letters/digits | googletagmanager.com/gtm.js, one gtm.start event |
| Microsoft Clarity | clarity | analytics | 1–64 lowercase letters/digits | clarity.ms/tag/ID, consentv2 |
| Meta Pixel | meta | marketing | nonzero numeric string, 1–20 digits | connect.facebook.net/en_US/fbevents.js, one init and trackSingle PageView |

New IDs accept strings only, trimmed; booleans, numbers, arrays, HTML, URLs and malformed values are rejected. Length bounds are application validation limits. New enable flags default off and IDs empty. No noscript tracking fallback is emitted.

Official sources: [GTM installation](https://support.google.com/tagmanager/answer/14847097?hl=en), [Clarity setup](https://learn.microsoft.com/en-ca/clarity/setup-and-installation/clarity-setup), [Microsoft template](https://raw.githubusercontent.com/microsoft/clarity-gtm-template/main/template.tpl), [Clarity consent API v2](https://learn.microsoft.com/en-gb/clarity/setup-and-installation/clarity-consent-api-v2), [CMP integration](https://learn.microsoft.com/en-gb/clarity/setup-and-installation/cmp-integration-guide), [Meta-owned integration source](https://raw.githubusercontent.com/facebook/facebook-for-woocommerce/main/facebook-commerce-pixel-event.php). Checked 2026-10-06. SDK bootstraps are implemented in this plugin's architecture.

VK Ads is **deferred**. The official installation page could not be read, and browser access was blocked by the environment safety policy. No undocumented API was guessed; admin shows a translated deferred status without an enable field.

## Consent mapping

Analytics grants the four analytics adapters; Marketing grants Meta. Fresh/reject grants neither. Clarity is not loaded before Analytics; consentv2 sets analytics_Storage granted and ad_Storage according to Marketing. A change granting Marketing updates this signal without initializing Clarity again.

Revoke saves the new choice then reloads for any previously allowed optional category. The next document loads only allowed providers. Already executed SDKs are not promised to unload from the current document. Cookie schema remains 1 and the stored policy version is not bumped.

## Admin UI

Normal WordPress Settings API form, manage_options, existing nonce and sanitizer. Analytics groups Yandex/GA4/GTM/Clarity; Marketing groups Meta and VK deferred status. Labels, descriptions and warnings use itd-cookies; Russian PO/MO include them. Theme fonts and frontend CSS are unchanged.

## Policy integration

Policy renderer iterates the registry; no per-provider rendering branches. Only enabled services with valid IDs appear. Meta automatically makes Marketing used; disabling it returns the category to the unused state. Third-party descriptive services continue to work through the filter. Managed page content is not overwritten.

## Security

Hardcoded HTTPS SDK hosts, strict PHP and JS ID validation, no script/snippet input, no user data/advanced matching added to Meta. New integrations remain off on legacy import, even if unexpected similarly named fields occur in ModuBricks options. Existing permissions, escaping and save flow are retained. No credentials, raw cookies or HAR are included in the package/report.

## Compatibility with 0.2.0

Existing raw settings, four legal links, text size, lifetime/version, migration marker, managed policy page, footer and Yandex/GA4 configuration are preserved. Missing new fields merge safe defaults on read without rewriting the stored option. Existing valid consent remains valid. ModuBricks and its options are untouched.

Updater SHA-256 remains 8642dc56bfeee4923d1b73d024c9ff7a2ca5f4e4dee64edab350c3de5df6b7b8. Updater code, GitHub API, cache/filter rules, ZIP convention and CI/release workflows are unchanged. package.json/package-lock.json only synchronize the root version; dependency graph is unchanged.

## Automated tests

Local results are recorded after the final run. Coverage includes enabled/disabled/invalid IDs, category matrix, old consent compatibility, registry/policy used-state, legacy defaults, deduplication and reload after revocation. Disposable WordPress smoke additionally checks registry/runtime configuration, local script dependency, policy content and raw settings/marker/page preservation.

Full existing CI (nine jobs: two PHP, quality, node, four WordPress matrix, build) pending.

## Browser tests

Pending candidate installation and acceptance on testwp with a recoverable snapshot. Required scenarios: 0.2.0 upgrade, fresh/reject, analytics only, marketing only, accept all, repeated settings/save/accept, custom→all, revoke/reload; bootstrap network/script and initialization counts; desktop 1440 and mobile 390. Temporary settings/files/activity/pages/helper must be restored/removed.

## Known limitations

ITD Cookies gates its own built-in integrations. Theme/plugin scripts and SDKs already on the page are outside its ownership. GTM container tags depend on the owner's configuration and can run marketing; Analytics consent for a container is not a compliance guarantee. Google Consent Mode is not automatically configured. Avoid enabling the same service natively and in GTM. Vendor backend delivery is not tested by synthetic IDs. Browser extensions/network failures may block allowed SDK requests. New provider support alone does not force visitors to consent again; owners should update their policy version if their processing policy changes.

## Future firewall design notes

Keep registry descriptions separate from executable adapters. A potential 0.4.x external-script firewall needs explicit ownership, parsing and ordering rules, category assignment, CSP/third-party compatibility and independent acceptance tests. No external-script interception is implemented here. Custom scripts require a separate future security design.

## Verdict

NOT_READY_FOR_ITD_COOKIES_0_3_0_RELEASE — implementation underway; full CI and browser acceptance/cleanup must complete before READY.
