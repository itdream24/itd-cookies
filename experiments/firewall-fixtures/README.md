# Local firewall fixtures

Original GPL-2.0-or-later dev-only fixture. Host guard: `itd-cookies.local`.
No real analytics IDs, cookies from visitors, IPs or vendor SDKs are collected.
Only same-origin mock script/image requests are counted.

Copy `fixtures.php` and `application.js` into
`wp-content/plugins/itd-cookies-firewall-fixtures/`, activate via local WP-CLI,
and create a temporary local page containing `[itd_firewall_fixture]`.
Keep official ITD Cookies 0.3.0 active and all built-in providers disabled.

Use `http://itd-cookies.local/?page_id=PAGE_ID&fw_case=full&fw_strategy=D`.
Cases are independent: `enqueue`, `head`, `footer`, `inline`, `dynamic`,
`attributes`, `dependency`, `unannotated`, `module`, `hints`, `pixel`,
`append-bypass`, `late-src`, `observer`, `response`, `csp` and `full`.
The POC README explains strategy selection.

## Browser procedure

1. Fresh visitor: **Clear local test consent**, wait for **Fixtures ready**,
   **Refresh evidence**, wait until `#fw-evidence` has `aria-busy="false"`.
2. Use the real ITD Cookies buttons: Reject, Settings → Analytics only,
   independent fresh Settings → Marketing only, Accept all, repeat Accept all,
   reload, revoke both optional categories. Capture per-document evidence after
   the replay settles; creation of the last replay tag does not mean its fetch
   has finished. Test at 1440×900 and 390×844.
3. Verify menu, required-field validation, filled form, SVG/entities/data blocks,
   unknown script, A→B→inline-B order and original script attributes.
4. Run A enqueue/head/footer, B full/dynamic, C head/dynamic, observer, D
   append-bypass/late-src/unannotated/hints/pixel. Leaks/errors in the latter
   cases are expected **negative findings**, not successful firewall acceptance.
5. `fw_case=hints&fw_hints=1` suppresses the recognized preload only. `pixel`
   includes a live image and inert JS-enabled noscript; JS-disabled behavior
   requires a separate browser/environment test and was not run here.
6. For `fw_case=csp&fw_response=nonce|hash`, first deny/clear real consent on the
   regular fixture. **Grant analytics (CSP probe)** dispatches an isolated test
   event; it does not persist consent or represent native consent-engine E2E.

`fw_asset`, `fw_label` and random `fw_doc` address local mock resources.
`fw_stats=DOC` returns counters. Each document/label has an atomic DB increment
in private non-autoload options `itd_fw_fixture_hits_*`; parallel loads cannot
overwrite other counters. `requests` counts server hits, `executions` counts JS
execution. A preload can increase the first while leaving the second zero.
Counter IDs are test document identifiers, never user identifiers.

Unknown CDN classification is tested offline with a synthetic CDN URL; the real
browser's unknown-functional fixture also stays same-origin. Network APIs are
not globally patched. No remote vendor requests are needed for any fixture.

## Restore

Visit `http://itd-cookies.local/?fw_cleanup=clear`. It expires only the three
ITD consent cookies and redirects to a minimal HTML verification page showing
presence booleans, never cookie values. Close all fixture tabs to prevent new
requests/counters during restoration. Restore `baseline-v0.3.0` using the
owner's existing local snapshot procedure; this removes both plugins, all
counter options and the temporary page. Leave official 0.3.0 active, verify exact
files, schema/all rows and normal homepage. Do not store snapshots, credentials,
raw SQL, browser session data or audit screenshots in this repository.
