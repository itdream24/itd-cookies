# ITD Cookies 02 — standalone repository and GitHub updater

## 1. Repository extraction

Source: ITD's accepted core commit `485c6ad8b74ef46d9dc56b3e8bfeac8ed116adb2`. Target: `https://github.com/itdream24/itd-cookies`, already public and empty when inspected. Clean history avoids unrelated modules/deployment history. Initial commit contains only the accepted core and translation compiler. Work proceeds on `codex/standalone-github-updater`; neither old branch nor main is changed.

## 2. Final repository structure

Repository root is plugin source (`itd-cookies.php`, admin/includes/public/assets/languages). Tests, scripts and CI are development-only. Production ZIP adds exactly one `itd-cookies/` wrapper. No runtime vendor dependencies.

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

Stable-tag workflow validates version and main ancestry, invokes the entire CI, then attaches the checked ZIP and SHA-256 once. Existing release causes refusal. No release workflow has been triggered in this stage.

## 9. Removed ModuBricks infrastructure

Excluded old updater, manifest transport, signing keys/tooling, hosting credentials, file-transfer publishing, production deployment and unrelated modules. Only old consent option/cookie names remain for one-time migration compatibility. No legacy update host or deployment credentials are needed.

## 10. Security

Trust boundary: controlled GitHub account/release permissions, HTTPS with WordPress certificate validation and native upgrader. This does not protect against account compromise; enable account 2FA, branch protections and restricted release rights. Custom signatures would not solve compromised signing credentials and are not introduced without an independent trust requirement. SHA-256 is integrity/audit evidence, not an independent signature. Cache failure handling prevents repeated anonymous-rate-limit calls. No production secrets/tokens/cookies/HAR files are shipped.

Core copyright/attribution retained under GPL-2.0-or-later; GNU GPL v2 license included. No third-party runtime libraries are bundled. Dev dependencies have their own package licenses.

## 11. Automated tests

Initial local results: PHP 7.4 and 8.1 PHPUnit 12 tests/167 assertions PASS; JS 16 tests PASS; PHPCS PASS; PHPStan PASS; PHPCompatibilityWP 7.4+ PASS. npm audit found two vulnerable transitive development packages, fixed within supported ranges; repeat audit zero vulnerabilities. Composer install/audit clean. Package build/inspection PASS (11 entries). Final reruns and remote CI pending.

## 12. End-to-end updater test

Pending testwp browser acceptance. Controlled synthetic stable metadata can exercise native installation without changing the source development version or publishing a release. Any simulated metadata version must be explicitly distinguished from the installed development header; this is an installation-path test, not proof of a published stable release.

## 13. Known limitations

No beta UI or private repository support. No custom signing trust anchor. GitHub outages leave current plugin operating and cache failures for 15 minutes. Latest stable release must have a matching asset. Current development version is intentionally unchanged. Real higher-version stable installation can only be proven after an authorized stable version/release; no such release is created here.

## 14. First release procedure

After acceptance and owner authorization: align header/constant/package.json/lock/readme/changelog with 0.1.0; inspect diff and scan source/history for secrets; merge reviewed feature branch into main; tag v0.1.0 once. Workflow must be green before release publication. Verify anonymous release JSON and asset, checksum, ZIP root and a native upgrade on a disposable copy. Never overwrite a tag/release; fix with a new version.

## 15. Verdict

NOT_READY_FOR_FIRST_ITD_COOKIES_RELEASE — remote CI and real testwp update/settings/failure acceptance are pending. Stable release is not published.
