# ITD Cookies 0.3.0 — Provider Integrations

Branch: feature/itd-cookies-0.3.0. Baseline origin/main: 2705b9f51e71e9ab1db778fd32591c81239ebcf6. Candidate: 0.3.0-dev.1. Published stable v0.2.0 remains unchanged. No stable tag/release is created in this stage.

## Local QA environment

The owner's LOCAL-FIRST QA POLICY applies permanently from this stage
(2026-10-06). See [project rules](../AGENTS.md) and [setup/reset procedure](LOCAL-QA.md).
Previous remote tests are historical evidence only and do not substitute for
the completed local acceptance below. Remote QA is never resumed automatically.

### Installed reference environment and baseline

- OpenServer document root: C:\openserver\domains\itd-cookies.local.
- URL: http://itd-cookies.local; domain/hosts registration verified after the
  owner's normal OpenServer restart. The folder was initially empty.
- WordPress 7.1.2, PHP 8.1.5, MariaDB 10.6 at 127.0.0.1:3306, ru_RU,
  Twenty Twenty-Five 1.5. No global OpenServer configuration was changed.
- Dedicated DB itd_cookies_local and localhost user itd_cookies_qa: grants
  verified only for this DB. The owner supplied the working local admin login
  after the initial blank-password attempt failed. Existing accounts/passwords
  and unrelated databases were not changed or used. Runtime DB credentials
  are only in wp-config.php; temporary setup credentials/SQL were deleted.
  QA admin credentials remain private outside web-root/Git.
- Official WordPress en_US ZIP SHA-256:
  8fc96c59a78b7219e4a130222b7fadb51b03e503e8b0123beaa7e28961c21ce2.
  Core checksum verification PASS. ru_RU core download was unavailable via
  WP-CLI; native installation language selection downloaded Russian files.
  Initial Windows WP-CLI extraction produced no files; guarded ZIP extraction
  followed by checksum verification completed installation.
- Official WP-CLI 2.12.0 matched its published SHA-512. Windows DB import/export
  uses forward-slash SQL paths and process-local MariaDB/Git tool paths.
- Official immutable v0.2.0 installed/activated with native wp plugin install.
  Only ITD Cookies 0.2.0 is installed/active in the baseline. Providers OFF,
  standard text, default RU consent settings, empty legal URLs, footer OFF,
  marker fresh-v1; managed policy option and legacy ModuBricks option absent.
- Baseline is outside web-root/Git in the private task artifacts:
  030-local-qa/baseline (DB SQL, state.json, wp-content, SHA-256 manifest).
  SQL: 147664 bytes, 12 tables; SHA-256
  30e83507104013fc3b82cb90673d9c4356c059478e2c11c030e562b4e8b49d5e.
  Manifest: 491 files. Capture and repeated Restore executed, with source dump
  and snapshot hashes validated before restoring the exact local target.
- Private baseline.ps1 -Mode Restore imports only this configured DB, restores
  wp-content, and compares every file hash plus WordPress/PHP/locale/theme,
  plugins/activity/settings/marker/page option. Initial Windows SQL-path failure
  was corrected in this private script; successful retries are the reset evidence.
  Never recapture over the immutable baseline.

### Executed local acceptance

| Check | Actual result |
| --- | --- |
| Core/WP-CLI integrity, isolated DB and ru_RU setup | PASS |
| Baseline capture and verified reset | PASS: DB, 491 file hashes, settings/theme/plugins |
| Direct development deployment | PASS: separate runtime copy, candidate activation/registry smoke; reset afterward |
| Native official v0.2.0 ZIP -> candidate | PASS: wp plugin install --force, native Plugin_Upgrader |
| Consent/category/reopen/dedup/revoke/reload | PASS: 26 recorded desktop/mobile scenarios |
| SDK script/init/actual request inspection | PASS: blocked category 0, allowed SDK <=1/document |
| RU Settings API, policy, links, footer and shortcodes | PASS |
| Desktop 1440x900 / mobile 390x844 | PASS: no horizontal overflow, Save accessible |
| Invalid ZIP / mocked download failure / actual broken GitHub asset URL | PASS: native upgrader rejects safely |
| Mocked GitHub API timeout | PASS: no update offered, installed data/files intact |
| Invalid IDs / malformed stored settings / malformed consent | PASS: fail closed; originals restored |
| Actual local ModuBricks 1.1.1 migration | PASS: allowlist, new providers OFF/empty, one-time marker |
| Real anonymous GitHub updater | PASS: published v0.2.0 found; official 0.1.0 -> 0.2.0 native update |
| Final baseline restoration, helper/options/pages/cache cleanup, homepage reload | PASS |

Native candidate upgrade retained itd-cookies/itd-cookies.php and active state,
header/runtime 0.3.0-dev.1. Seeded long description, very-large text, four local
legal links, 365 days, unchanged policy version, Metrika/GA4, footer ON and managed
policy page 5 survived exactly. Raw settings SHA-256 before/after:
dc7403f4edd0b0483fc5b1a94371699a4a6e35cfbeae1cc6654e4b0e8e3cb2a9.
Consent hash unchanged; schema 1 and existing analytics-only consent accepted
without another prompt. Marker fresh-v1 and updater file hash unchanged.
New provider defaults were OFF with empty IDs before any settings save.

The ordinary Russian Settings API form enabled GTM-TEST123, Clarity test12345
and synthetic Meta ID 123456789012345. It displays Analytics/Marketing grouping,
GTM warning and VK deferred status. Dynamic policy included all enabled native
services; disabling Meta removed it and disabled unused Marketing, then
re-enabling restored it. Four legal links/footer and policy/reopen shortcodes
rendered. Invalid GTM-invalid / INVALID-ID / 0 became empty through the ordinary
form; no new provider SDK/init followed. Corrupted stored option returns safe
defaults and no providers; malformed consent showed the banner and all SDKs 0.

Fresh and Reject/reload: all five SDK/script/init/request counts 0.
Analytics only: Yandex/GA4/GTM/Clarity one each, Meta 0. Marketing only: Meta
one (PageView one), Analytics 0. Accept all: all five one. Repeated Save,
Accept all, settings reopen, Analytics->all and Marketing->all kept each
script/init <=1/document. Revocation saved the new consent and reloaded without
the denied category; explicit further reload and stored-consent reload passed.
Clarity consentv2 ad storage changed denied->granted without another bootstrap.
Completed Performance Resource Timing entries were one for each allowed SDK
and zero for blocked categories. Immediate captures can precede request completion.
Fake GTM returned the expected 404; genuine vendor collection is not claimed.
The browser matrix did not mock SDK responses or GitHub HTTP.

Responsive checks used long text/four links/110% text: document widths 1425/1440
and 375/390; desktop panel scroll/client width 703/703, mobile 334/334.
Mobile Save reached through keyboard/internal scroll, rect x31/y664.84,
298x44, then actually used. Screenshots inspected. Computed Manrope, sans-serif
matches the theme. Viewport override reset; layout code was not changed.

Failure tests compared the full plugin file tree, raw settings, marker, policy
option and active state. Invalid archive returned incompatible_archive;
mocked network error and actual nonexistent GitHub ZIP returned download_failed.
All preserved the installed working candidate/data. API timeout returned no
release. Synthetic metadata existed only in the failure-test process/cache;
no GitHub Release was created and production updater code was unchanged.

Actual local ModuBricks 1.1.1 ZIP was temporarily installed/activated, seeded,
then deactivated before activating the candidate. Imported legacy consent/
Metrika/GA4/legal/text/version settings and copied-v1 marker passed; unexpected
new-provider legacy fields were excluded. Repeated migration preserved edited
ITD Cookies settings. ModuBricks file tree/options unchanged. A temporary local
HTTP guard blocked owner endpoints (including old update-host) during this test;
no remote WordPress or update-host request was used. Reset removed ModuBricks/guard.

Real updater check used anonymous public GitHub API without synthetic metadata
or HTTP filters: candidate sees latest stable v0.2.0 and offers no downgrade.
Separately installed official v0.1.0, cleared the native update cache, found
exact v0.2.0 asset, ran standard wp plugin update itd-cookies, and verified
0.2.0 ACTIVE, canonical folder, settings/marker/page option preserved.
The initial ad-hoc direct-upgrader harness lacked the standard CLI reactivation
flow and used an unsupported diagnostic skin method; it was discarded/reset.
Only the successful standard WP-CLI run counts as real-update PASS. Plugin code
was not changed. Real 0.2.0 -> 0.3.0 is a future post-publication gate, not run now.

### Final local state and cleanup

Option A: restored the official v0.2.0 baseline. ITD Cookies ACTIVE, only plugin;
Twenty Twenty-Five 1.5/ru_RU, original default settings and fresh-v1 marker.
All 491 wp-content paths/hashes and recorded options/activity match the baseline.
Temporary observer and network guard absent; test options/cache/metadata and
pages 5, 6 and 8 absent; managed policy and ModuBricks options absent.
All three consent test cookies were verified absent before reset; no later
consent action was performed. Homepage reload displays the normal RU banner,
without helper DOM or provider SDK scripts. Credentials/dump/evidence/private
tools remain outside Git/package; no installed QA helper is retained.

Private evidence: browser-evidence.json, failure-evidence.json,
migration-evidence.json, real-candidate-evidence.json, real-stable-evidence.json,
cleanup-evidence.json and desktop/mobile/restored screenshots under 030-local-qa.
An independent assertion pass verified all 26 recorded scenarios, preservation,
policy/IDs/font/geometry and four native failure outcomes.

Remote-access criterion for this stage: remote QA requests to testwp.itdream.su
= **0**; SSH/FTP operations against Timeweb = **0**; production mutations = **0**.
Existing remote pages/plugins were not inspected or changed. Historical remote
restoration remains unverified; no fresh remote cleanup claim is made.

Full nine-job CI on policy/setup commit 9163c7051419725cfec9dcbcd25bc3ec62aeb909
PASS, including all four WordPress matrix jobs and build:
[run 37468681592](https://github.com/itdream24/itd-cookies/actions/runs/37468681592).
The documentation closure commit is pushed on the same feature branch and its
separate full CI must also pass before handoff; the exact commit/run is reported
in the completion message. No stable tag/release or main merge in this task.

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

Local checks (2026-10-06): PHPUnit on PHP 7.4 PASS (21 tests, 340 assertions); JS PASS (31 tests); ESLint PASS; PHPCS PASS; PHPCompatibilityWP for PHP 7.4+ PASS; PHPStan level 5 PASS; Composer audit PASS (no advisories); npm audit PASS (0 vulnerabilities); Russian MO compilation PASS (93 messages); git diff --check PASS; Gitleaks PASS (one feature commit scanned, no leaks). JS reload tests reconstruct the next document with its cookie jar; jsdom emits its expected unsupported navigation notices. PHP tools use a writable local temporary directory; initial local temp-permission failures were resolved without changing project configuration. Coverage includes enabled/disabled/invalid IDs, category matrix, old consent compatibility, registry/policy used-state, legacy defaults, deduplication and reload after revocation. Disposable WordPress smoke additionally checks registry/runtime configuration, local script dependency, policy content and raw settings/marker/page preservation.

Implementation commit: da71f8387adb3d858a026f21145a23855da02a13. [Full existing CI run 37426737141](https://github.com/itdream24/itd-cookies/actions/runs/37426737141) PASS, all nine jobs: PHP 7.4, PHP 8.5, quality, node, WordPress 5.2/PHP 7.4, WordPress 5.2.24/PHP 7.4, latest/PHP 7.4, latest/PHP 8.5, build. No cancelled/skipped job. Production-format dev ZIP inspector PASS: 15 runtime entries, single itd-cookies/ root, version 0.3.0-dev.1, updater/PO/MO included, dev files excluded, all packaged PHP syntax valid on 7.4. Asset name: itd-cookies-0.3.0-dev.1.zip. SHA-256: 66e110a5c3d1da388e735643a4c98fbe2eb600f011827eafc53f55dfac6cb4c9. This ZIP is a test candidate; no stable release is published.

## Historical remote browser tests (before LOCAL-FIRST policy)

Owner confirmed a recoverable snapshot and the candidate/observer installation immediately before E2E (2026-10-06). Test scope is testwp only. The observer is outside Git/ZIP, restricted to this host and an admin preview; it counts application calls and Performance Resource Timing SDK entries. It does not replace HTTP metadata or SDK responses. Clarity's bootstrap count is its SDK script append; API consentv2 signals are counted separately. This does not claim backend delivery with test IDs.

### Upgrade preservation — PASS

Installed the immutable official 0.2.0 ZIP (SHA-256 d4a4251031e1905a7fd15412e8c8c7e3e96eafebc3f59c46037b16f72f6d78e9), then uploaded/replaced it with the dev candidate through the native WordPress upgrader. No synthetic release metadata and no public 0.3.0 release. WordPress reported successful update. Folder/basename itd-cookies/itd-cookies.php retained, plugin active, header/runtime 0.3.0-dev.1.

Seeded long description, very-large size, four legal links, 365 days, existing policy version, Metrika/GA4 IDs, auto_footer=1 and managed policy page ID 43. Raw settings SHA-256 before/after: fb01189b1dc9f6b2b790b4b54ca165454ad0a1834601f4a1db28a3e1beb24740. Existing analytics-only consent SHA-256 before/after: 200d254d19c60a801bf5658baccd68cefec90a21bf33f181cc06355f97d3b174. Migration marker copied-v1 and schema 1 preserved. New flags 0 and IDs empty without rewriting the old option. Existing Yandex/GA4 each bootstrap once after reload; new integrations absent until enabled. Installed candidate tree SHA-256: 3b31bcebc0c1ac2a9408eea7b824d36b447fa9e302433e5023b2ac3567fa43c9. Updater hash unchanged. ModuBricks files/settings were unchanged at this gate.

The ordinary Russian Settings API form saved GTM, Clarity and Meta IDs/flags successfully. It displays grouped sections, GTM limitations and VK deferred status. Four links, reopen/legal/policy shortcodes and optional footer continued rendering. Dynamic policy listed enabled GTM/Clarity and omitted Meta when disabled. Marketing becomes used/enabled with Meta and unused/disabled without it; Functional remains unused.

### Consent/runtime matrix — PASS for built-in bootstrap

The following scenarios were exercised on desktop 1440 and mobile 390:

| Choice | Yandex / GA4 / GTM / Clarity | Meta | SDK requests before disallowed consent |
| --- | --- | --- | --- |
| Fresh / Reject and reload | 0 each | 0 | 0 |
| Analytics only | 1 each | 0 | Meta 0 |
| Marketing only | 0 each | 1 | Analytics 0 |
| Accept all | 1 each | 1 | only after grant |
| Revoke Analytics and reload | 0 each | 1 if Marketing retained | Analytics 0 |
| Revoke Marketing and reload | 1 each if Analytics retained | 0 | Meta 0 |

Repeated reopen/save, repeated Accept all, custom Analytics→all and custom Marketing→all kept script/init counts at one per provider per document; Meta PageView one. Existing stored consent and subsequent reload also bootstrapped once. Completed SDK resource entries were one per allowed integration, zero for disallowed categories. Fake GTM-TEST123 returned the expected 404; successful tag execution/vendor collection with genuine IDs is not claimed. Clarity consentv2 ad storage changed denied→granted when Marketing was granted without another bootstrap.

### Responsive UI — PASS

Long description, four legal links and very-large text checked at 1440×900 and 390×844. Document scroll widths 1425 and 375 respectively, within viewport. Desktop dialog scroll/client width 703/703; mobile 334/334. No horizontal overflow. Mobile panel Save was reached by keyboard/internal scroll and successfully used (button rect x31/y664.84, 298×44). Banner/panel screenshots were inspected. Computed font Manrope, sans-serif matches the theme. Viewport override reset.

### Restoration/cleanup — PENDING; connection failure

During final policy verification/re-enabling Meta, the admin form request and a fresh observer-page navigation timed out. Browser displayed ERR_CONNECTION_TIMED_OUT for testwp; its data: error page then prevented browser control. Preview tab was closed, evidence saved, owner asked to open the observer page again. No cause is attributed to plugin code without evidence. The owner independently confirmed testwp is still unavailable. No restoration/cleanup PASS is claimed.

Last confirmed site state: ITD Cookies candidate and temporary itd-cookies-030-provider-observer active; ModuBricks temporarily inactive; test settings present. Meta was disabled and a subsequent re-enable save has unknown outcome. Owned pages: managed policy 43 and draft acceptance 44; old page 14 is untouched. Baseline to restore: ITD Cookies 0.1.0-dev.1 inactive, tree SHA-256 382758cc1b4c067fa0f291ed5fc445963cfbb4d4afc24d936f4d83839ed141dd; settings SHA-256 e2f17cbbe92ffa0c813d0550c609786abf21a90d5ba345303db6938c62875b08; marker copied-v1; no managed page option or three consent cookies; ModuBricks 1.1.1 active. Snapshot and restore controls remain in the observer's private test option. Restore original ZIP first, then raw options/activity/cookies; remove only owned pages/helper and its five options after restoration verification.

## Known limitations

ITD Cookies gates its own built-in integrations. Theme/plugin scripts and SDKs already on the page are outside its ownership. GTM container tags depend on the owner's configuration and can run marketing; Analytics consent for a container is not a compliance guarantee. Google Consent Mode is not automatically configured. Avoid enabling the same service natively and in GTM. Vendor backend delivery is not tested by synthetic IDs. Browser extensions/network failures may block allowed SDK requests. New provider support alone does not force visitors to consent again; owners should update their policy version if their processing policy changes.

## Future firewall design notes

Keep registry descriptions separate from executable adapters. A potential 0.4.x external-script firewall needs explicit ownership, parsing and ordering rules, category assignment, CSP/third-party compatibility and independent acceptance tests. No external-script interception is implemented here. Custom scripts require a separate future security design.

## Verdict

READY_FOR_ITD_COOKIES_0_3_0_RELEASE — implemented providers, automated CI and
local baseline/browser/native-upgrader/failure/migration/cleanup acceptance PASS.
VK remains explicitly deferred pending verified official installation API.
This accepts the development candidate; no stable tag/release is created.

Historical remote restoration remains unverified and must not be attempted
without a new task containing REMOTE FINAL SMOKE AUTHORIZED.
