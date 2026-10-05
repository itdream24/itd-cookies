# ITD Cookies 02 — standalone repository and GitHub updater

## 1. Repository extraction

Source: ITD's accepted core commit `485c6ad8b74ef46d9dc56b3e8bfeac8ed116adb2`. Target: `https://github.com/itdream24/itd-cookies`, already public and empty when inspected. Clean history avoids unrelated modules/deployment history. Initial commit contains only the accepted core and translation compiler. Work proceeds on `codex/standalone-github-updater`; neither old branch nor main is changed.

## 2. Final repository structure

Repository root is plugin source (`itd-cookies.php`, admin/includes/public/assets/languages). Tests, scripts and CI are development-only. Production ZIP adds exactly one `itd-cookies/` wrapper. Built `dist/itd-cookies-0.1.0-dev.1.zip`: SHA-256 `2a23f54d5ed713b7fb50db88624d8976bd2bc0893c780922179c963906631183`. Two builds, including Pacific/Auckland timezone, yielded identical hashes. No runtime vendor dependencies.

## 3. Updater architecture

`ITD_Cookies_Updater` registers `pre_set_site_transient_update_plugins`, `plugins_api`, and native update-cache deletion hooks. WordPress owns HTTP download, extraction, installation and activation. Update URI protects against unrelated directory updates on modern WordPress; legacy transient integration supports WordPress 5.2. Consent/provider code is unchanged.

## 4. GitHub API behavior

Anonymous GET `/repos/itdream24/itd-cookies/releases/latest`, Accept JSON, API version 2022-11-28, 10-second timeout, no redirects/token. Metadata must contain exact false draft/prerelease flags, strict tag and an uploaded nonempty matching asset at the exact repository HTTPS URL. Missing asset/source archives are rejected. Release body is escaped in the details modal.

References: [GitHub Releases API](https://docs.github.com/en/rest/releases/releases), [WordPress plugins_api](https://developer.wordpress.org/reference/hooks/plugins_api/).

## 5. Caching

Transient `itd_cookies_github_release`: valid metadata 6 hours; negative results 15 minutes. No network during hook registration/ordinary page loads/frontend. Admin, cron and WP-CLI update checks may fetch. Dashboard → Updates → Check again clears the cache through WordPress's native cache invalidation.

## 6. Version and tag rules

Source remains `0.1.0-dev.1`. Planned beta/rc/stable: `0.1.0-beta.1`, `0.1.0-rc.1`, `0.1.0`. First stable 0.1.0 reflects the young API and avoids an unsupported 1.0 compatibility promise. Production accepts strict `vX.Y.Z`, without leading zeros or suffixes. `version_compare` handles equal/newer/older versions. Tag, package.json, header and constant must agree at release build.

## 7. Release asset design

Built asset `itd-cookies-X.Y.Z.zip`; explicit allowlist, fixed timestamp, sorted entries, fixed permissions and compression. Inspector validates one root, paths, required main/updater/license/catalog, version and PHP syntax. Source archives are never used. Development ZIP is not a published stable asset.

## 8. GitHub Actions pipeline

Standalone CI: PHP 7.4/8.5, PHPUnit, PHPCS, PHPStan level 5, PHPCompatibilityWP, Composer audit, Node 22/ESLint/JS tests/npm audit, WordPress 5.2/5.2.24/latest with PHP 7.4 and latest with PHP 8.5. Disposable WordPress tests migration/translation/shortcode/consent/native upgrade and failure preservation. Build waits for every gate.

Stable-tag workflow validates version and main ancestry, invokes the entire CI, then attaches the checked ZIP and SHA-256 once. Existing release causes refusal. No release workflow has been triggered in this stage. Because the repository was empty, GitHub automatically made the first pushed feature branch its default. A reviewed main branch must be established before stable tagging; no main merge/default-setting mutation was performed.

## 9. Removed ModuBricks infrastructure

Excluded old updater, manifest transport, signing keys/tooling, hosting credentials, file-transfer publishing, production deployment and unrelated modules. Only old consent option/cookie names remain for one-time migration compatibility. No legacy update host or deployment credentials are needed.

## 10. Security

Trust boundary: controlled GitHub account/release permissions, HTTPS with WordPress certificate validation and native upgrader. This does not protect against account compromise; enable account 2FA, branch protections and restricted release rights. Custom signatures would not solve compromised signing credentials and are not introduced without an independent trust requirement. SHA-256 is integrity/audit evidence, not an independent signature. Cache failure handling prevents repeated anonymous-rate-limit calls. Gitleaks 8.30.1 redacted Git history scan: 6 commits, no leaks (exit 0). Scanner archive checked against the official SHA-256 list. Additional forbidden-infrastructure and sensitive-file/pattern scans: no matches. Test IDs and disposable CI database credentials are explicit synthetic fixtures. No production secrets/tokens/captured cookies/HAR files are shipped.

Core copyright/attribution retained under GPL-2.0-or-later; GNU GPL v2 license included. No third-party runtime libraries are bundled. Dev dependencies have their own package licenses.

## 11. Automated tests

Initial local results: PHP 7.4 and 8.1 PHPUnit 12 tests/167 assertions PASS; JS 16 tests PASS; PHPCS PASS; PHPStan PASS; PHPCompatibilityWP 7.4+ PASS. npm audit found two vulnerable transitive development packages, fixed within supported ranges; repeat audit zero vulnerabilities. Composer install/audit clean. Package build/inspection PASS (12 entries). Remote CI [run #6](https://github.com/itdream24/itd-cookies/actions/runs/37322478058) on `22510abd11461fb51b51f23a33ad2ace686e3adf`: all 9 jobs PASS. Prior code CI [run #5](https://github.com/itdream24/itd-cookies/actions/runs/37321841651) on `22a778884d9809e2a3b6ef38aab71cc0d8078a5c`: all 9 jobs PASS (PHP 7.4/8.5, quality, JS, 4 WordPress matrices, build). Earlier red runs exposed a test assertion/skin selection error: early download/unpack failures can leave the upgrader result null, and errors are recorded by the native AJAX skin. The corrected test asserts native skin errors and preserves plugin files/options/activity; production updater required no change.

## 12. End-to-end updater test

Browser acceptance completed on 2026-10-05 on testwp, WordPress 7.1.2, through the native WordPress admin UI. Owner authorized the isolated synthetic fixture and separately confirmed permanent cleanup. No GitHub Release/tag was created. No product code changed during this closure.

### Scenario and version evidence

Before installing the standalone baseline, the testwp-only helper captured original plugin settings, migration marker, plugin activity and SHA-256 fingerprints of both plugin directories. Original installation: ITD Cookies `0.1.0-dev.1` inactive (accepted stage 01C files); ModuBricks `1.1.1` active; Akismet and Hello Dolly inactive. Original ITD Cookies settings were also recorded from its settings form before test seeding. Its settings SHA-256 was `e2f17cbbe92ffa0c813d0550c609786abf21a90d5ba345303db6938c62875b08`; migration marker was `copied-v1`.

Baseline installed using WordPress Upload Plugin/Replace; ModuBricks temporarily inactive, ITD Cookies active. Fixture seeded a long Russian description, three legal links, `very-large` text size, Metrika `12345678`, GA4 `G-TEST12345`, retaining policy version `1.1.0-rc.1`, consent lifetime and migration marker. Seeded settings SHA-256: `257ed037ca766e865b2be55b217e347a6092f076031bb8bced3607056567135f`.

Fixture returned synthetic stable `v0.1.0` metadata at the exact production API/asset URLs via WordPress `pre_http_request`, without exposing a public test endpoint. WordPress Dashboard Updates → Check again offered `0.1.0` over `0.1.0-dev.1`. Plugins → Update ITD Cookies now used the native AJAX upgrader to download/extract/install the controlled package. The helper did not install files itself.

**Version distinction:** metadata advertised `0.1.0`; both packaged header and runtime constant intentionally remained `0.1.0-dev.1`, following the requirement not to bump source version solely for updater tests. File replacement was proved independently by an inert fixture comment and updater SHA-256 changing from `8642dc56bfeee4923d1b73d024c9ff7a2ca5f4e4dee64edab350c3de5df6b7b8` to `72b481047a9bf6ab8d1f74b08635af9f16a043a3880e4c972cf0044eca8800a5`. Test payload ZIP SHA-256: `3e528799e16cbd8ddcfee2fb76bb9308232e7fcd8be36f858ff66a4259d0259e`. This proves the native installation path with synthetic metadata, not installation of an actual published 0.1.0 stable binary. While the fixture remains enabled, unchanged dev headers can cause the synthetic offer to recur; fixture/cache were removed after testing.

### Acceptance results

| Browser check | Actual result |
| --- | --- |
| Native manual check/discovery | PASS: WordPress offered synthetic 0.1.0 |
| Native update/file replacement | PASS: marker present, updater/file-tree hashes changed |
| Directory/basename | PASS: `itd-cookies/itd-cookies.php` retained |
| Activity after update | PASS: ITD Cookies remained active |
| Consent/legal links/text size/analytics settings | PASS: full settings SHA-256 unchanged; Russian form showed all three links, very-large, both test IDs and toggles |
| Migration marker | PASS: `copied-v1` unchanged |
| Existing consent | PASS: analytics-only choice survived update/reload, functional/marketing remained false, no banner re-prompt |
| Shortcode | PASS: rendered settings button opened dialog with saved analytics checkbox |
| Providers before consent | PASS: zero provider scripts/init calls after clearing synthetic choice |
| Providers after consent/repeated save/reload | PASS: one script and one init/config call per provider per document; repeated save did not add another |
| Desktop 1280×900 | PASS: long description/three links/buttons visible; no horizontal overflow |
| Mobile 390×844 | PASS: document scroll/client width 375 (scrollbar excluded), dialog 351 wide, inner scroll/client width 349; accept button available at y=665..709 |
| Theme font | PASS: dialog/button retained Manrope from the active Twenty Twenty-Five theme |
| ModuBricks | PASS: entire directory SHA-256 and stored settings matched initial snapshot throughout and after reactivation |
| Reload after update/failures | PASS: shortcode page rendered, saved consent remained effective, providers initialized once |

Counters are controlled preview instrumentation: they count actual runtime calls to provider entry points and tagged script insertion. They do not claim receipt of analytics events by vendor services; synthetic IDs are not real tracking credentials.

### Native failure scenarios

Separate update attempts used WordPress's own updater button:

- Invalid ZIP: native error `PCLZIP_ERR_BAD_FORMAT (-10)` / archive could not be installed.
- Unavailable ZIP: native download error from a synthetic HTTP network failure.
- GitHub metadata timeout: native Check again displayed no ITD Cookies update.

After each failure, ITD Cookies remained active; complete installed tree SHA-256 stayed `9118325d234dee0d90cc68a7f8e980283c6a949a15e44940f743575a2493e9f6`; updater hash, settings hash and migration marker were unchanged. The shortcode page still worked on reload and preserved consent. No custom installer/recovery mechanism was used.

### Restoration and cleanup

Original settings/marker restored; entire original stage 01C plugin restored through WordPress Upload Plugin/Replace. Its complete directory fingerprint returned to `382758cc1b4c067fa0f291ed5fc445963cfbb4d4afc24d936f4d83839ed141dd`, identical to the initial snapshot. Original settings form matched field-for-field. ITD Cookies inactive, ModuBricks 1.1.1 active; other plugins unchanged.

Synthetic consent was cleared; fixture mode disabled. Temporary helper then deactivated and deleted through WordPress. Its uninstall removed all four test options and own release cache; admin options UI confirmed no remaining fixture/cache keys. Newly created test page ID 21 permanently deleted after owner confirmation; pre-existing stage 01C page ID 14 remained in Trash. Plugin list returned to the original four plugins, with no helper. Homepage reloaded normally with the original ModuBricks banner and no ITD Cookies scripts/test instrumentation/provider SDKs.

Local browser evidence (screenshots, status/form comparisons) is retained outside Git in the task's artifact directory: `02-browser-evidence.json`, `02-update-offered.jpg`, `02-update-success.jpg`, `02-desktop.jpg`, `02-mobile.jpg`, `02-invalid-zip.jpg`, `02-network-failure.jpg`, `02-restored-status.jpg`, `02-final-plugins.jpg`. No browser cookies, nonces, auth headers, helper or synthetic payload are committed/published. Production, old main, plugin product code, public tags/releases and repository visibility were not changed.

## 13. Known limitations

No beta UI or private repository support. No custom signing trust anchor. GitHub outages leave current plugin operating and cache failures for 15 minutes. Latest stable release must have a matching asset. Current development version is intentionally unchanged. Real higher-version stable installation can only be proven after an authorized stable version/release; no such release is created here.

## 14. First release procedure

After acceptance and owner authorization: align header/constant/package.json/lock/readme/changelog with 0.1.0; inspect diff and scan source/history for secrets; merge reviewed feature branch into main; tag v0.1.0 once. Workflow must be green before release publication. Verify anonymous release JSON and asset, checksum, ZIP root and a native upgrade on a disposable copy. Never overwrite a tag/release; fix with a new version.

## 15. Verdict

READY_FOR_FIRST_ITD_COOKIES_RELEASE — standalone package/updater, stable filtering, API failure handling, native browser update and failure preservation, settings/consent/runtime acceptance, full testwp restoration and fixture cleanup PASS. Standalone CI is green; the report-only closure commit also runs CI before final handoff. No stable release/tag is published. Readiness authorizes no publication: first release still requires the separately reviewed version/main/tag procedure in section 14.
