# Embedded maps — project integration policy

Accepted owner decision, 2026-10-13; included in the planned 0.4.4 typography/footer UX work.

## Behaviour

Existing embedded Yandex Maps load automatically, including direct iframe and
Map Constructor integrations. They remain visible after Reject and after
revoking Analytics. No “Show map” click or Analytics consent prerequisite is
introduced. Preserve the site's map URL, dimensions, placement, language and
other settings; do not add a second map instance.

The site's **native Yandex Metrika provider configured in ITD Cookies** remains
an Analytics provider and loads only when `ITDCookies.allowed('analytics')` is
true. A map's own vendor requests are a separate integration and must be
attributed by counter ID, document/frame and initiator, rather than by the
presence of a Yandex hostname alone.

## Technical decision: no frontend runtime change

The current 0.4.3 architecture already supports this behaviour:

- `assets/js/providers.js` loads explicitly configured providers, checks their
  categories and deduplicates attempts. It does not load or block maps.
- `includes/class-itd-cookies-script-adapters.php` owns only explicitly
  registered WordPress script handles. An unregistered map handle remains in
  the normal WordPress queue; a literal iframe/constructor embed is untouched.
- `assets/js/script-adapters.js` replays owned templates. It does not scan or
  intercept existing iframe elements or arbitrary dynamic script insertion.

No map toggle, URL/domain allowlist bypass, iframe MutationObserver, global
script interception, map loader, consent-schema change or special “necessary”
category is required in ITD Cookies. Existing services/API behaviour stays intact.
The project rule does not make ITD Cookies a universal script firewall.

## Site integration responsibilities

1. Keep one normal map embed/constructor or unregistered WordPress map handle.
2. Do not register a map handle/dependency as a consent-gated Script Adapter.
   Do not use `ITDCookies.allowed('analytics')` as its display prerequisite.
   Existing explicit adapter groups continue to obey their declared category;
   the plugin cannot infer that a handle contains a map and does not silently
   override registrations. Remove an incompatible map registration in its
   owning site integration after backup and local QA.
3. If an existing theme/plugin itself gates maps, change only that confirmed
   integration. Removing a gate is a site change, not an automatic plugin
   migration. A no-op is preferred when the current map already meets the rule.
4. Do not transfer QA router/CSP, synthetic provider IDs, local safety plugins,
   database dumps or the GAZ-YALTA-specific scrollUp/footer module to other sites.
5. Preserve Metrika provider settings, consent/version/marker and legal pages.
   Keep tracking snippets out of duplicate theme/GTM/native integrations.

The rule covers Yandex Maps embeds; another map provider needs its own resource
inventory and review. It does not add Google Maps or any new provider to the plugin.

## Administrator information and privacy

An external map can request its own SDK/tiles, transmit technical data, write
vendor cookies/storage and run its own analytics **before consent and after
Reject**. This traffic is not disabled by the native Analytics toggle. Removing
or blocking third-party cookies in the browser does not prove absence of telemetry.

Do not label the map a technically necessary cookie service merely to avoid a
consent decision. A descriptive service entry does not control loading; generated
Cookie Policy content alone does not inventory all theme/iframe services.
Administrator/owner review must cover the actual map integration and documents.
Automatic maps are the accepted product rule, **not a legal-compliance guarantee**.
Do not reintroduce a map gate without a new owner decision.

### Admin UI copy for the combined 0.4.4 UX task

Use a static, non-dismissible explanatory paragraph near Analytics settings;
it describes the boundary and does not pretend to detect/control a site's maps.
No checkbox, stored option, consent change, global admin warning or map detector.
The administrator receives this information through installation/rollout
documentation. The paragraph is now implemented in the 0.4.4 settings
renderer near Analytics and included in the Russian PO/MO catalog. No maps
runtime behavior or stored map setting has been added.

English source, escaped and translated with text domain `itd-cookies`:

> Embedded Yandex Maps load independently of Analytics consent. External maps
> may transmit data and use their own cookies or analytics before consent and
> after rejection. ITD Cookies controls the site analytics configured below,
> not map requests. Review the map integration and privacy information separately.

Russian translation:

> Встроенные Яндекс.Карты загружаются независимо от согласия на аналитические cookie.
> Внешние карты могут передавать данные и использовать собственные cookie или аналитику
> до согласия и после отказа. ITD Cookies управляет настроенной аналитикой сайта,
> но не запросами карты. Проверьте интеграцию карты и сведения о конфиденциальности отдельно.

Implement later using the existing settings renderer with `esc_html__()`;
update POT/PO/MO in that combined change and verify ru_RU/admin accessibility.
Do not display a green compliance badge or automatically rewrite a banner/policy.

## Acceptance and rollout

Test both iframe and constructor on an isolated local WordPress, with no groups
and with unrelated Analytics/Marketing Script Adapters. Fresh/Reject/Analytics/
Accept all/reload/Revoke must preserve one map and its dimensions. The site's
Metrika loader remains absent before Analytics, appears once after permission,
and is absent in the new document after revoke. Test adapter dependencies,
same-choice deduplication and error isolation; an adapter failure must not break maps.

Use desktop/mobile screenshots, real browser clicks/zoom, keyboard, Network and
Console. Separate map-owned tracking from native provider requests. Synthetic
native SDK responses do not prove delivery to a real Metrika account.
Restore the saved local files/settings/database and clear isolated QA cookies.
Remote smoke/deployment requires separate authorization; no fixtures on production.

For legal wording, disclose the map's independence from Analytics/Reject and
review vendor requests/cookies without inventing purposes/retention/legal basis.
Changing legal text or consent_version is a separate approved site operation.

No standalone release is made for this policy. Combine it and the planned admin
paragraph with the 0.4.4 typography/footer work; complete the usual CI, browser,
release and production gates for that combined candidate. This document does
not mark the complete 0.4.4 release ready.
