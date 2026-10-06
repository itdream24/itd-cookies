# External script firewall research POC

Original GPL-2.0-or-later research code. No third-party implementation was copied.
This is **not production code**, a security boundary or a universal zero-request
guarantee. It is deliberately absent from the stable package allowlist.

The dev plugin runs only when `home_url()` has host `itd-cookies.local` and
`fw_case` is supplied. ITD Cookies 0.3.0 must remain active. Never install this
experiment on a remote WordPress instance.

## Strategies

| Query `fw_strategy` | Mechanism |
| --- | --- |
| A | `script_loader_tag`, explicit handle classification, replay controller |
| B | Final HTML buffer + limited tokenizer + replay controller |
| C | Early `appendChild`/`insertBefore` interception + replay controller |
| D | A + B + C |
| observer | MutationObserver tries to block after insertion; negative control |
| off | No firewall; negative control |

`rules.json` is the shared PHP/JS known-provider registry. Fixture URL rules are
explicit same-origin developer rules. Host equality prevents suffix spoofing;
path matching is a **limited prefix match**. PHP `parse_url` and browser `URL`
normalization differ; `dom-tests.mjs` preserves that counterexample. Missing,
unknown, malformed and stale consent never grants optional categories.

`data-itd-cookies-category="analytics|marketing"` explicitly classifies executable
external/inline script. `itd_fw_poc_handles` is a **draft experiment filter**, not
a released developer API. Unknown scripts and non-JS data blocks remain intact.

The tokenizer changes only opening-tag byte spans. Unsupported input returns
the original document with a diagnostic. This preserves availability but allows
known tracker requests on bypassed documents. It has no complete HTML5 namespace,
escaped script, streaming or JavaScript parser. The head insertion locator is a
regex; the script transformation itself uses a tokenizer.

Replay preserves attributes and inline body bytes, uses an ordered promise queue
and deduplicates nodes. It cannot recreate parser timing or infer dependencies.
An inline module executes in the tested browser but its load wait times out.
The diagnostic observer is not a blocking mechanism. `Element.append`, connected
`script.src`, saved native methods, `document.write`, network APIs, workers,
iframes and pixels are outside the current synchronous interception coverage.

`fw_hints=1` is a separate experimental preload suppression switch. It is off by
default; it does not implement complete host-only preconnect/DNS handling or hint
replay. Do not enable it as an unreviewed production policy.

## Standalone checks (from repository root)

Use the installed project dependencies (`npm ci` if not already available).
No browser or vendor network is used by the DOM/controller unit tests.

```powershell
php experiments/firewall-poc/tests.php
node --test experiments/firewall-poc/controller-tests.mjs
$env:PHP_BINARY = 'path/to/php-7.4/php.exe'
node experiments/firewall-poc/dom-tests.mjs
node node_modules/eslint/bin/eslint.js --config experiments/firewall-poc/eslint.config.mjs experiments/firewall-poc/controller.js experiments/firewall-fixtures/application.js experiments/firewall-poc/*.mjs
php experiments/firewall-poc/benchmark.php 100 tokenizer 20
php experiments/firewall-poc/benchmark.php 500 tokenizer 20
php experiments/firewall-poc/benchmark.php 1024 tokenizer 20
```

Benchmark alternatives: `regex` and `dom`, same input and 15 iterations. These
are timing comparators, not safe alternative implementations. Peak memory is the
PHP allocator high water mark in a fresh process, not total server RSS.
Avoid unbounded dense-script stress runs: the 2000-tag trial was interrupted.

## Local integration

Restore the owner's immutable `baseline-v0.3.0` first. Copy only `poc.php`,
`parser.php`, `controller.js`, `rules.json` into
`wp-content/plugins/itd-cookies-firewall-poc/`. Install the sibling fixture as its
README describes, activate both via local WP-CLI and use its shortcode page.
No settings of the main plugin need to change.

While fixtures are active:

```powershell
node experiments/firewall-poc/http-tests.mjs
```

This checks local response MIME/status/flush/compression/download safety and
common blocked HTML for two synthetic consent states. It is not browser E2E.
The repository's normal stable CI commands do not automatically include these
experiments; use the explicit commands above. Full new CI/old-WP browser runs
were not performed for this audit.

Before removing fixtures, visit their consent cleanup action; then close test
tabs and restore the immutable files and SQL. Compare every file and all DB
tables, not only plugin settings. WordPress can refresh expired theme caches on
bootstrap: after homepage smoke, a final DB import/export comparison without
WordPress bootstrap verifies the exact snapshot state. Never recapture or
overwrite the immutable baseline.
