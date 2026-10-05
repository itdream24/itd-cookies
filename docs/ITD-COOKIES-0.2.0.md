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

## Pending gates

Full feature CI, browser responsive widths 1440/1024/768/425/390/320, keyboard acceptance, policy page/footer acceptance and official 0.1.0 to synthetic candidate 0.2.0 update on testwp remain pending. Test fixture is outside the repository/production ZIP. Existing files/options/activity must be restored and fixture/page/cache removed after the test.

Real stable-to-stable update is a separate mandatory gate after a future genuine v0.2.0 release; not run or claimed here. No merge/tag/release before implementation acceptance and separate release gate.

## Verdict

NOT_READY_FOR_ITD_COOKIES_0_2_0_RELEASE — browser and CI evidence pending.
