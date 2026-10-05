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

## Pending publication evidence

Full CI on the release commit/main must pass before READY_TO_TAG_V0.1.0. No tag/release has been created at the time of this preparation commit. After green CI: tag once, wait for release workflow, verify anonymous metadata/ZIP/checksum, then run native real-release update on testwp without synthetic HTTP metadata. Capture original settings/activity and restore the agreed test site state afterward.

## Verdict

NOT_READY_TO_RELEASE_V0.1.0 — full main CI, publication and real-release test-site update evidence pending. This preparation does not claim publication success.
