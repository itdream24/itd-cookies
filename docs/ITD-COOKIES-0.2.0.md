# ITD Cookies 0.2.0 — Consent UX and legal integration

## State

Baseline main: 59ff27259895acd49cdd63cd5b1f28798ba70488. Branch feature/itd-cookies-0.2.0 created from freshly fetched origin/main; clean tree at start. Development version 0.2.0-dev.1. Historical v0.1.0 tag/release/assets unchanged.

## UI and compatibility

Own scoped UI: simple first banner; category-card settings panel, descriptions, services, accessible checkbox switches, save and accept all. Four category identifiers, schema 1, cookie format, consent version and legacy marker unchanged. Empty optional categories remain visible with Not currently used and disabled switches. Existing saved values remain displayed and preserved even in currently empty categories; Accept all retains the four-category API contract. Link-styled settings shortcode remains a native button.

Registry ITD_Cookies_Services::get exposes id/name/category/enabled/description through itd_cookies_services. Built-in enabled state requires an enabled provider and sanitized valid ID. Only enabled entries appear in categories/policy. The descriptive API does not load code: extension authors must gate their own integrations with the existing PHP/JS consent APIs and changed event. Invalid entries and duplicate IDs are ignored.

CookieRus public repository/template and live-page text examined as a UX reference: [repository](https://github.com/RuCoder-sudo/cookierus), [live example](https://xn--d1acnqieq.xn--p1ai/plag/). Interactive live UI failed to open with connection reset; no interactive visual comparison is claimed. No reference HTML/CSS/JS copied. Other categories, purposes, logging and compliance assertions from that project are outside this implementation.

## Legal integration

Four legal URL/label pairs; existing three retained. User agreement optional. No generators for privacy, personal-data or user-agreement documents.

[itd_cookies_policy] dynamically lists category descriptions, configured services, consent gating, configured choice lifetime and a reopen action. It does not invent provider cookie names, third-party retention or compliance guarantees; it describes plugin configuration, not a site-wide cookie scan.

Explicit admin-post action checks manage_options, publish_pages and nonce, creates a published Page containing only the policy shortcode and stores its ID in itd_cookies_policy_page_id. Existing non-trashed pages/content/status reused without editing; deleted pages can be replaced. A public managed permalink is used only when link_3_url is empty. Explicit URL wins. Page action does not rewrite the settings option.

[itd_cookies_legal_links] renders configured links and enabled settings action. auto_footer is OFF by default; opt-in wp_footer container uses the same renderer and local scoped styles. It does not search or move theme DOM. Theme font inherited. Manual shortcode plus auto output can intentionally produce two blocks; choose one placement.

## Accessibility and security

Settings dialog label/description/modal semantics, keyboard Tab wrap, focus heading on open and return to opener on close/save. Escape closes reopened settings without modifying consent; initial Escape returns to undecided summary and grants no optional category. Native Necessary switch checked and disabled; empty optional switches disabled with explanation. Focus-visible outlines and forced-colors fallback.

Settings API sanitizer retained; numeric Metrika/canonical GA4 validation unchanged. All new text/URLs escaped, registry description fields sanitized, privileged page action nonce/capability protected. No arbitrary JS settings. Updater class, API/cache/tag/asset behavior unchanged (SHA-256 8642dc56bfeee4923d1b73d024c9ff7a2ca5f4e4dee64edab350c3de5df6b7b8).

## Actual local gates

- PHPUnit PHP 7.4: 18 tests / 220 assertions PASS.
- JS: 19 tests PASS; ESLint PASS. JSDOM reports its expected unsupported reload warning; next-document consent/provider behavior is tested using the shared cookie jar.
- PHPCS PASS after formatting corrections; initial launch failed due PHP temp configuration, rerun used a working temp directory.
- PHPStan level 5 PASS.
- PHPCompatibilityWP 7.4+ PASS.
- Composer audit: no advisories; npm audit: zero vulnerabilities.
- Translation compilation: 76 Russian messages; PO/MO included.
- Dev and isolated candidate builds passed package inspection/PHP 7.4 syntax. Candidate source has only version metadata relabeled to 0.2.0 outside Git; no stable release or tag created.

## Full implementation CI

Implementation commit cee4d55ea180ca4eab6a776e2279381d842beee6: [CI run 37359328078](https://github.com/itdream24/itd-cookies/actions/runs/37359328078) completed SUCCESS, all nine jobs PASS: quality (Composer audit, PHPCS, PHPStan), PHP 7.4 and 8.5 (PHPUnit and compatibility), node (audit, ESLint, translations, JS tests), WordPress 5.2/5.2.24/latest on PHP 7.4 and latest on PHP 8.5, production package build/inspection. Disposable WordPress integration covered page reuse/content preservation/deleted-page replacement and footer OFF/ON in addition to updater/activation/migration checks.

## Browser acceptance — 2026-10-05

Only the designated test site was used, with owner-confirmed recoverable snapshot and action-time installation approval. WordPress reported 7.1.2; Twenty Twenty-Five theme, Russian locale. Fixture/helper and synthetic metadata are outside the repository and distributable package. No genuine v0.2.0 tag/release exists.

Candidate itd-cookies-0.2.0.zip contains the implementation above with version metadata relabeled to 0.2.0 in an isolated copy. SHA-256: 17252f3a21d01d55abf214976a4203267357340f9896c315669c17944a3a35ae. Package inspection and PHP 7.4 syntax PASS; root itd-cookies/, translation files included, development/test files excluded.

### Native updater and preservation

- Installed the immutable official v0.1.0 asset through WordPress upload/replace; canonical folder and activation confirmed.
- Seeded temporary long description, title, very-large text, three legal URLs/labels, test Metrika/GA4 IDs, 365-day lifetime and existing consent version. Set analytics-only custom consent in 0.1.0 and reloaded.
- Synthetic GitHub metadata offered exactly 0.2.0. Dashboard > Updates > Check again, select only ITD Cookies > Update plugins: WordPress reported successful update, maintenance mode disabled.
- Header/runtime 0.2.0; active; basename itd-cookies/itd-cookies.php. Raw settings hash before/after identical: 257ed037ca766e865b2be55b217e347a6092f076031bb8bced3607056567135f. Title/text/size, all three links, Metrika/GA4, lifetime/version preserved. Migration marker copied-v1 preserved. Schema still 1.
- Existing analytics-only consent survived reload: no new prompt, functional/marketing false, each provider script/init count exactly one. Registry and new shortcodes rendered immediately. Fourth URL blank and auto_footer unchecked after upgrade.
- Updater file hash identical to baseline; no updater implementation change.

### UI, consent and accessibility

All existing and new settings buttons opened the panel, including footer and policy actions. Necessary checked/disabled; empty optional categories explained and disabled; enabled analytics showed Yandex Metrika and Google Analytics 4.

Tab from final action wrapped to the only enabled category switch; Shift+Tab from heading reached Back. Escape on reopened settings returned focus to opener, closed without changing consent. Initial Escape returned to the banner with no optional permission or SDK. Dialog aria-modal true in settings.

Accept all, Reject, custom analytics-only and saving unchanged selection PASS. Before consent and after Reject: zero SDK scripts/config calls. After permission: one script and one initialization for each provider. Revoking analytics saved the choice and reloaded; subsequent reload showed zero scripts/initializations. Reopening/saving did not duplicate initialization. Fixture stubbed provider functions to count application initialization; this does not verify real vendor collection/remote backend behavior.

### Responsive results

Long five-sentence description, four legal links, very-large (110%) text, category/service descriptions and auto footer ON. DOM measurements, keyboard scrolling and desktop/mobile screenshots checked.

| Viewport | Document scroll/client width | Horizontal overflow | Panel height / scroll height | Actions |
| --- | --- | --- | --- | --- |
| 1440 x 900 | 1425 / 1425 | none | 863.59 / 862 | accessible |
| 1024 x 900 | 1009 / 1009 | none | 863.59 / 862 | accessible |
| 768 x 844 | 753 / 753 | none | 820 / 862 | accessible after internal scroll |
| 425 x 844 | 410 / 410 | none | 820 / 1120 | accessible after internal scroll |
| 390 x 844 | 375 / 375 | none | 820 / 1120 | accessible after internal scroll |
| 320 x 844 | 305 / 305 | none | 820 / 1298 | accessible after internal scroll |

Scrollbar explains the 15px difference from viewport width. Dialog scroll/client widths also identical at every size. Mobile actions stack vertically; legal links wrap; banner and panel use overflow-y auto when needed. Panel Accept all was reached by keyboard at every size within the viewport. Footer did not overflow. Computed banner font and body font both Manrope, sans-serif from theme; no custom font family added.

### Legal acceptance

Fourth optional URL saved and appeared in banner/manual links/auto footer. Policy creation made Page ID 32; admin then offered Open page. Explicit third URL retained priority, blank third URL used the managed permalink. Public page rendered dynamic shortcode with 365-day choice lifetime and settings action. Disabling both providers removed them from policy/panel and marked analytics unused. Original three links retained. Auto footer initially absent, explicitly enabled output its own container without moving theme footer. Repeat creation/content preservation/deletion replacement additionally PASS in automated tests and disposable WordPress CI.

### Restoration and cleanup

Restored original 01C dev files through native WordPress upload/replace, then original options/marker and activity. Original ITD Cookies full-tree hash matched: 382758cc1b4c067fa0f291ed5fc445963cfbb4d4afc24d936f4d83839ed141dd. Original settings hash matched: e2f17cbbe92ffa0c813d0550c609786abf21a90d5ba345303db6938c62875b08. Original managed page option absent again. ModuBricks files/settings unchanged throughout.

Owner confirmed permanent cleanup immediately before deletion. Temporary fixture uninstalled with its four test options and GitHub cache; only own pages 31/32 permanently deleted. Old page 14 left untouched in Trash. Read-only options screen contained only itd_cookies_settings and itd_cookies_migration_version, no test options, policy page marker or release cache. Plugins returned to four: ModuBricks 1.1.1 active; ITD Cookies 0.1.0-dev.1 inactive; Akismet/Hello Dolly inactive. Fresh homepage reload had original ModuBricks banner, no ITD Cookies/helper/analytics assets, no console error/warning or horizontal overflow. Viewport override reset. Cleanup PASS.

WordPress itself emitted a null preg_replace deprecation/header warning on the temporary helper deletion confirmation page (helper lacked Author metadata); final plugin list and homepage were clean. No runtime candidate warning observed; no unrelated core edits made.

## Release boundary and limitations

Implementation acceptance PASS. Merge to main may follow the accepted feature CI; keep dev version 0.2.0-dev.1 until a separate release gate synchronizes stable metadata and validates the final commit. Historical v0.1.0 tag, GitHub release and assets, ModuBricks source/update host and production sites unchanged.

Real stable-to-stable 0.1.0 -> 0.2.0 update without synthetic metadata remains a separate mandatory check after a future genuine v0.2.0 publication; it was not run or claimed here. No stable publication authorized at this implementation stage.

Policy describes plugin configuration rather than all cookies on a site. Registry extensions describe services but must implement their own existing consent API integration. Page creation reuses the stored page sequentially; concurrent admin requests are not serialized. Theme/layout validation here covers Twenty Twenty-Five and the listed sizes, not every WordPress theme.

## Verdict

READY_FOR_ITD_COOKIES_0_2_0_RELEASE — implementation, full CI, pre-release native updater E2E, UI/responsive/consent/legal acceptance and cleanup PASS. Stable release gate and real stable-to-stable verification are still required.

## Stable release gate — preparation

Accepted implementation main bf1eaf38d946265fdf70a3ec8684088a1d6300a5; release/itd-cookies-0.2.0 created from freshly fetched origin/main with a clean tree. Stable source version 0.2.0 synchronized in plugin header/constant, package/lock root metadata, readme, Russian PO/MO and PHPStan fixture. Changelog documents actual 0.2.0 changes. No consent schema or runtime function change.

Source audit found the pre-existing yocto-queue lock entry had erroneously received the plugin dev version while its resolved tarball/integrity still pinned 0.1.0. Corrected only that dependency version to the actual installed/pinned 0.1.0; no dependency or runtime package change. Package/release scripts already enforce strict stable tag/version agreement and main ancestry; unchanged.

Updater SHA-256 unchanged from accepted implementation. No temporary browser helper/observer, credentials, cookie/HAR captures, synthetic HTTP metadata or old deployment/update-host configuration in runtime/package. Automated disposable WordPress updater metadata mocks remain development tests and are excluded from ZIP; localhost-only disposable CI credentials are not test-site credentials.

Composer audit PASS (no advisories), npm audit PASS (0 vulnerabilities), diff whitespace check PASS. Production ZIP itd-cookies-0.2.0.zip: 14 allowlisted entries, single itd-cookies root, main/updater/license/readme/Russian PO+MO present, dev/tests/docs/reports excluded. Inspector and PHP 7.4 syntax PASS. Two builds under UTC and Pacific/Auckland matched SHA-256 d4a4251031e1905a7fd15412e8c8c7e3e96eafebc3f59c46037b16f72f6d78e9 (36324 bytes). Tag/publication requires full release-branch CI then a separate full main CI. Real stable-to-stable browser update and cleanup are pending until publication.
