# Bounded adapters R2 (dev only, GPL-2.0-or-later)

This directory is not loaded by ITD Cookies and never enters its ZIP. Only a
closed set of registered, classic WordPress handles is a readiness candidate.
Each group declares `id`, analytics/marketing `category`, all `handles`, and
`resources: []`. Data/localization, before, SDK, after, dependency terminals are
one graph. Head and footer are supported. Capture uses each WordPress version's
native inline renderer; replay retains inline bytes, nonce, integrity and URL.
Dependencies sequence this graph, rather than a global dynamic-script queue.

Register before `wp_print_scripts`; registration after preparation, modules,
translation inline code, conditional tags, concatenation, source-less aliases,
unclosed dependency ownership and another filter replacing the tag are outside
the proven contract. The caller must explicitly describe provider ownership:
native enabled provider types win and conflicting adapter groups are withheld.
No URL catalog lookup can infer this ownership or integration completeness.

Ancillary preload/hints/pixel/noscript resources are UNSUPPORTED, not zero-request
coverage. The verified mock integrations contain none. All such resources must
be inventoried before recommending a real integration. No JS-disabled noscript
support is claimed. Unknown inline code executes normally, including the R1
unknown-dependency failure. Dynamic insertion, workers, module graphs, iframe
management and all-network interception remain excluded. R1 globals are retained
for reproduction; this controller adds none.

`Runner` validates missing dependencies/cycles, memoizes each attempt, reports
load errors/timeouts, skips dependent code and lets independent groups finish.
No retry on the same document; revocation follows stable consent's reload.
Removing a timed-out script is not proof that an in-flight response cannot later
execute. CSP probes cover allowed nonce/hash inline replay, not every CSP policy
or arbitrary runtime errors in SDKs. Late/partial consent changes cannot undo
already executed third-party code.

Commands (PHP 7.4+, Node 22+, `npm ci`):

```sh
php experiments/firewall-poc/tests.php
php experiments/bounded-adapters/tests.php
node --test experiments/firewall-poc/controller-tests.mjs experiments/bounded-adapters/controller-tests.mjs
node experiments/firewall-poc/dom-tests.mjs
node experiments/bounded-adapters/benchmark-tests.mjs
wp eval-file experiments/bounded-adapters/wordpress-tests.php --path=/disposable/wordpress
```

Performance budget is in `performance-budget.json`, declared before measurements.
Every benchmark subprocess has a 15s watchdog. Improved R1 HTML assembly is still
an experimental parser and explicitly returns `BYPASS_UNSUPPORTED_HTML`. R2's
shared PHP/JS URL comparator does not rewrite a download URL and does not replace
the historical R1 classifier: its PHP/browser dot-segment gap stays a regression.

Local browser fixture: copy bounded-adapters, bounded-fixtures and firewall-poc
as sibling dev folders in the dedicated local plugins directory; activate only
`bounded-fixtures/fixtures.php` (requires stable ITD Cookies). Create a temporary
page with `[itd_bounded_fixture]`; use `?r2_case=supported|error|timeout|missing|cycle|ownership`.
Standalone CSP probes: `/?r2_case=csp-nonce` and `/?r2_case=csp-hash`.
Mock requests use server counters `itd_r2_hits_<document>_<asset>`. Fixture is
hard-guarded to `itd-cookies.local`. Restore the immutable baseline afterwards.
