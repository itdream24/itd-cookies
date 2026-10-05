# ITD Cookies 02B — main and first stable release

## Preparation

Accepted source: `297107b398e25ab3f7ca109d90b00ae850c710d5`. Main created from that exact commit without history rewriting; initial diff against accepted feature branch was empty. GitHub default branch switched to main; feature branch retained. No old repository or production site changes.

## Version/package

Version 0.1.0 aligned in plugin header/constant, package.json/lock, readme/changelog, Russian PO/MO metadata and PHPStan fixture. Native CI update metadata fixture uses synthetic 0.1.1 so it still tests a newer remote version with installed 0.1.0. Consent/provider runtime/schema are unchanged.

ZIP: `itd-cookies-0.1.0.zip`, 12 allowlisted files, one itd-cookies root, updater and Russian PO/MO, required main/readme/license, PHP syntax valid. Inspector now rejects lock/readme version drift, including the packaged readme. SHA-256: `2156ecccf46c73041873e6d2a94072f54b91b77e1569a5a4f967475c3561445e`. Two builds with different timezones matched.

## Release gates

Release workflow accepts numeric vX.Y.Z tag patterns and strictly validates the stable tag against source version; freshly fetched origin/main must contain the tagged commit. Arbitrary unmerged feature commits fail the native Git ancestry check. All CI gates run again before publication. Publisher only attaches built asset and filename-relative SHA-256 sidecar; existing releases are not overwritten.

Pattern syntax checked against [official GitHub workflow syntax](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax#filter-pattern-cheat-sheet).

Local PHP 7.4 PHPUnit 12/167 PASS; JS 16/16 and ESLint PASS; stable ZIP inspection/reproducibility PASS. Gitleaks diff scan PASS. Committed path/content scan found no environment files, HAR, captured cookies, real local absolute paths, old update/deployment transport or credentials. Test-site mentions in historical acceptance documentation and synthetic IDs/disposable passwords in tests/CI are expected, not live credentials. Runtime package excludes documentation/tests/CI.

## Published release and CI

Release commit: `8d7fa81099480fada900792088ef08793ec26403` on main. Annotated tag `v0.1.0` resolves to this commit and was pushed once, after the explicit READY_TO_TAG_V0.1.0 gate. Feature branch remains at the accepted commit; main is the GitHub default branch.

[Main CI](https://github.com/itdream24/itd-cookies/actions/runs/37329089822): all 9 jobs SUCCESS before tag. PHPUnit on PHP 7.4/8.5, JS tests, ESLint, PHPCS, PHPStan, PHPCompatibilityWP, Composer audit, npm audit, WordPress 5.2/5.2.24/latest (PHP 7.4 and 8.5), translation regeneration and package build passed.

[Release workflow](https://github.com/itdream24/itd-cookies/actions/runs/37329453600): validate, all 9 reused CI jobs and publish SUCCESS. [GitHub Release v0.1.0](https://github.com/itdream24/itd-cookies/releases/tag/v0.1.0) is not draft and not prerelease. Anonymous latest API returns v0.1.0. Assets: `itd-cookies-0.1.0.zip` (27803 bytes) and `itd-cookies-0.1.0.zip.sha256` (88 bytes). Both downloaded anonymously; downloaded ZIP SHA-256 matches local reproducible build and the published sidecar. Downloaded ZIP passed the 12-entry package inspector and PHP 7.4 syntax checks.

Gitleaks 8.30.1 full-history scan at the release commit: 9 commits / 339655 bytes scanned, no leaks. No captured browser data or test observer is committed or packaged.

## Real WordPress update on testwp — 2026-10-05

Owner confirmed snapshot and the real-release test. Initial state: WordPress 7.1.2, ModuBricks 1.1.1 active; ITD Cookies original 01C 0.1.0-dev.1 inactive; Akismet and Hello Dolly inactive. Snapshot captured settings, migration marker, activity and plugin file hashes before changes.

Temporary observer has no pre_http_request filter, release metadata or package substitution. It records hashes/status and instruments provider init calls only on the administrator's test-page preview. It is not part of the repository/package. Test provider IDs are synthetic; SDK/init counts prove consent gating and deduplication, not successful delivery of analytics events.

Native upload/replace installed the accepted standalone updater dev package. Original settings hash remained unchanged. Test settings populated a long Russian description, all three legal links, very-large text size, Metrika and GA4 IDs; policy version remained unchanged. A draft page ID 26 exercised `[itd_cookies_settings]`.

| Check | Actual result |
| --- | --- |
| Before consent on dev | No provider script or init: 0/0 for both |
| Custom analytics-only choice, then reload | Analytics true, functional/marketing false; one script/init per provider |
| WordPress Check again | Offered 0.1.0 for installed 0.1.0-dev.1 from real GitHub Release |
| Native Update plugins, only ITD Cookies selected | WordPress reported successful completion; maintenance mode ended |
| Installed header/runtime | Both 0.1.0; plugin active |
| Canonical folder/basename | itd-cookies/itd-cookies.php preserved |
| Complete test settings SHA-256 | Before/after identical: 257ed037ca766e865b2be55b217e347a6092f076031bb8bced3607056567135f |
| Migration | copied-v1 preserved; no re-migration |
| Russian UI and shortcode | Принять все translated; shortcode reopened the category dialog |
| Existing consent after upgrade/reload | Analytics-only choice preserved without prompting again |
| Repeated shortcode save | Still one script/init per provider in the document |
| ModuBricks files/settings | Both unchanged throughout |
| Desktop 1280x900 | Document width 1265; banner/buttons inside viewport |
| Mobile 390x844 | Document width 375; banner x=12..363, y=54.16..832; all three buttons visible (last ends y=813) |
| Theme font | Manrope, sans-serif on body, title, description, legal links and buttons |
| Largest text level | Title 22px, description/buttons 16.5px, links 15.4px; long description and three links fit |

## Restoration and cleanup

Native upload/replace restored the exact original 01C files: tree SHA-256 `382758cc1b4c067fa0f291ed5fc445963cfbb4d4afc24d936f4d83839ed141dd`. Original settings SHA-256 restored: `e2f17cbbe92ffa0c813d0550c609786abf21a90d5ba345303db6938c62875b08`; original and restored settings forms are identical. Marker copied-v1 unchanged. ITD Cookies inactive again; ModuBricks 1.1.1 active again. Test consent cleared, draft ID 26 moved to trash, observer deactivated. Owner confirmed permanent deletion. Observer and its four test options plus GitHub release cache were deleted by native WordPress uninstall; draft ID 26 permanently deleted through the page-specific action. Native Plugins now lists only four original plugins: ModuBricks 1.1.1 active, original ITD Cookies inactive, Akismet/Hello Dolly inactive. Options UI contains only itd_cookies_settings and itd_cookies_migration_version (copied-v1), with no test-option, observer, synthetic metadata or GitHub release cache. Pages list contains only the original two pages; trash retains only old 01C page ID 14, untouched. Restoration hashes were verified immediately before observer removal; uninstall only deletes its own options/cache and files, leaving restored plugin files/settings intact. Homepage reload displays the original ModuBricks banner, with no ITD Cookies assets, test instrumentation or provider SDKs. Cleanup PASS.

No production site, old ModuBricks repository, update-host manifest or existing stable tag was changed. Only the standalone repository's first stable release was published as authorized. Tag v0.1.0 will not be moved when this evidence is committed.

## Verdict

ITD_COOKIES_V0.1.0_RELEASED_AND_VERIFIED
