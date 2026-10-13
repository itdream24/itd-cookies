# ITD Cookies 0.4.4 — planned UX work

Published stable remains 0.4.3. Version metadata, main and release assets are unchanged.

## Accepted addition: universal embedded maps policy

Owner decision 2026-10-13: Yandex Maps iframe/constructor embeds load automatically
on project sites, independently of Analytics and after Reject. Preserve the
integration's geometry and settings. Native Metrika stays consent-controlled.

See [technical decision, site responsibilities and admin copy](MAPS-INTEGRATION-POLICY.md).
Frontend runtime changes: **none required**. No fictitious map switch or global
interception. A site that registered/gated its map must correct that integration;
the generic Script Adapter API is not silently changed.

## Combined implementation scope

- Include this policy in installation and production rollout documentation.
- Include the translated explanatory admin paragraph in the same 0.4.4 settings
  UX implementation as typography/footer improvements. It is specified here;
  the settings renderer/translations have not been edited in this branch.
- Typography/footer requirements and their final implementation/acceptance remain
  the separately agreed UX scope. Do not infer their release readiness from a maps test.
- Preserve consent API, provider categories, Script Adapters and saved settings.
- No separate tag/Release for documentation; normal full 0.4.4 CI and release gate later.

## Local browser verification

Verification uses official 0.4.3 on `http://itd-cookies.local`, temporary local-only
fixture, real Yandex map resources and synthetic native provider/adapter scripts.
Chrome/Chromium155.0.8059.39, Playwright1.62.1; independent contexts, no user profile.

| Case | Result |
|---|---|
| Direct iframe, zero groups, 320/390/768/1440px | PASS4/4 composite lifecycles |
| Constructor, zero groups, 320/390/1440px | PASS3/3 composite lifecycles |
| Constructor, zero groups, 768px | FAIL: external constructor request HTTP504 on reload; real rerun also HTTP504 |
| Iframe + unrelated adapter groups, 390/1440px | PASS2/2: closed dependency order, categories and dedup |
| Constructor + unrelated adapter groups, 390/1440px | PASS2/2 |
| Constructor + failed adapter, 390px | PASS: deliberate local404, dependent skipped, marketing independent, map unaffected |
| Separate 768px constructor contract | PASS with explicitly mocked constructor response and a real map iframe; not a real-constructor availability PASS |
| Existing JS regression suite | PASS42/42; no failed/skipped tests |

Each successful lifecycle checks Fresh, Reject/reload, Analytics-only, same-choice
Save, Accept all and Revoke/new document; one iframe and one constructor request
where applicable, preserved 100%/400px fixture geometry, real map zoom, native
Metrika load/init0 before consent and1 after, keyboard/reopen. Map vendor SDK/
tracking is attributed separately from the synthetic site's SDK.

**Actual vendor matrix:12/13 PASS,1 FAIL**, with an additional failed real retry.
Do not report full real Browser E2E PASS or diagnose an ITD defect from the
external504. The separate constructor-response mock confirms the plugin contract
at768px and is recorded independently. Real vendor availability at that case
requires a later bounded recheck when the external request succeeds.

Native Metrika's SDK response and adapter scripts are synthetic local QA;
account-side tracking delivery is NOT_RUN. Maps iframe/SDK/tiles/telemetry are
real except the explicitly identified constructor response in the separate test.

Before QA, immutable baseline0.4.3 archive/hash integrity was checked and a fresh
task checkpoint was actually restored. Initial WP-CLI import with Windows
backslash paths failed before import; forward-slash paths fixed the QA command,
then all12 table schemas/rows matched. No runtime fix was made for this harness issue.

Cleanup removed the one temporary MU fixture and its newly created empty folder.
No persistent QA pages/options/counters were created. Independent contexts cleared
their cookies and closed. Final504 wp-content hashes and all12 database tables
match the pre-task checkpoint; original settings/marker/activity preserved,
official0.4.3 ACTIVE. Baseline0.4.3 and older snapshots remain unchanged.
Screenshots/raw Network/SQL stay outside Git and webroot; no production requests.

**Maps policy/specification: accepted. Real vendor Browser gate: partially blocked
by external HTTP504.** Admin settings paragraph is still planned for the combined
UX implementation. No runtime/version/translation files changed in this branch.

**Complete 0.4.4 release gate: NOT_RUN.** No merge/tag/Release is authorized by this document.
