# Limited Script Adapters (0.4.0)

Scope: **WP_HANDLES_ONLY**. This is a developer integration API, **not a
universal cookie firewall**. Nothing is registered or blocked by default.

## Public API and lifecycle

```php
itd_cookies_register_script_group( $group_id, array $args ); // true | WP_Error
itd_cookies_get_script_group_diagnostics();                 // read-only array
```

Register scripts, dependencies and inline data normally on `wp_enqueue_scripts`
(usually priority 10). Register groups on **`itd_cookies_register_script_groups`**,
dispatched at `wp_enqueue_scripts` priority **999**. Direct earlier frontend
registration is also supported. All handles and inline attachments must exist
before this dedicated hook returns. Preparation then freezes the registry
**before WordPress traverses dependencies**, including the native datepicker
queue query at priority 1000 in WordPress 5.2. Native head/footer print actions
also validate the frozen queue and provide a fallback for direct early
registration when the normal enqueue action is absent. Edits to owned footer
handles after freezing are unsupported and withheld. Themes must run `wp_head`
and `wp_footer` normally; the graph manifest is emitted at footer priority 10000.
Do not register or print new owned handles after that manifest.

`LATE_REGISTRATION` returns `WP_Error`, records the error and acquires no ownership.
It cannot undo already printed native output. Handle registration errors in your
integration; do not emit an executable fallback. Structural invalid declarations
also acquire no ownership. Explicit, structurally valid ancillary declarations
reserve the named handles and quarantine them when preparation starts.

## Declaration

Required fields: `category`, `handles`, `provider_types`, `resources`.
`group_id` is the first API argument. Optional `label` is a plain, localized service
name (up to 160 bytes), used by the existing category cards and Cookie Policy.
Without a label, a translated “Additional site service” description is used.
No handle/URL/code editor is added to admin settings.

- Categories: `necessary`, `functional`, `analytics`, `marketing`. Necessary is
  always allowed by the existing engine. Classify honestly; this API does not
  infer categories from a script's effects.
- IDs, handles and provider types use a deliberately strict subset of WordPress
  names: `[a-z][a-z0-9_.-]{0,63}`. Lists must be nonempty, sequential and distinct.
- Maximum 32 groups, 32 handles per group, 256 owned handles per document and
  8 provider types per group. Diagnostic history is bounded to 512 PHP events.
- `resources` **must be `array()`**. Preload, resource hints, pixels, noscript,
  iframes and tracking fallbacks are unsupported. The integrator must disclose
  and stop ancillary output; adapters do not discover it in page HTML.
- Unknown fields, executable code and URLs in API arguments are rejected.
  Executable URLs still come from ordinary trusted `wp_register_script` calls.

## Closed dependencies and capture

Every handle must be a registered classic external script. All dependency edges
touching owned handles must stay inside the **same group**. Shared prerequisites,
cross-group dependencies, source-less aliases, translations attached to script
handles, conditional/module/nomodule scripts, concatenation, event-handler
attributes and wrappers adding non-script children are unsupported. In particular,
do not acquire ownership of theme scripts or a shared jQuery handle.

The graph is taken from WordPress's registered `deps`, not guessed from URLs.
Head and footer handles can belong to one group. Each handle retains the native
order: data/localization → before → external → after. Core renderers produce the
inline tags, including version-specific wrappers/sourceURL comments. The normal
`script_loader_tag` pipeline sees its native before/external/after bundle; data
remains outside it as in WordPress. Only owned script output is placed in inert
templates. No full-page buffer, raw HTML tokenizer or page-wide scanning is used.

The controller uses `ITDCookies.allowed(category)` and
`itd_cookies_consent_changed`. It waits for the complete document/manifest,
validates a group's entire template set, then runs its DAG. Each handle and group
is attempted once per document. Dependencies settle before dependents; independent
groups progress concurrently using asynchronous SDK scripts, without a shared
ordered browser queue. Ordinary unknown scripts retain their native output.

## Ownership

Native enabled ITD provider types win: `yandex`, `ga4`, `gtm`, `clarity`, `meta`.
An external group declaring any currently enabled native type is withheld as a
whole with `FAILED_OWNERSHIP_CONFLICT`. No hostname matching is involved. External
groups cannot share handles; for repeated external provider types the first
registered valid group wins and later groups are withheld. These declarations
cannot discover an undeclared third-party tracker or individual tags inside GTM.

## Failures and developer diagnostics

PHP action: `itd_cookies_script_group_diagnostic`, one array argument.
Browser event: the same name as a `CustomEvent`, safe `detail` copy.
Browser snapshot: `ITDCookiesScriptAdapters.getDiagnostics()`.
Snapshots/events contain only validated group/handle identifiers and state codes;
never source, URLs, provider IDs, settings or visitor data. No technical errors
are shown in visitor UI and no console logging is enabled by this runtime.

States include `REGISTERED`, `WAITING_FOR_CONSENT`, `ACTIVATED`, `FAILED`.
Error codes include:

| Code | Result |
| --- | --- |
| `INVALID_GROUP` | Invalid/bounded API declaration; no ownership acquired |
| `MISSING_HANDLE` / `MISSING_DEPENDENCY` | Explicit group quarantined before core traversal |
| `CYCLE` | Owned cyclic graph quarantined before native recursion |
| `FAILED_OWNERSHIP_CONFLICT` | Conflicting external owner withheld; native provider preserved |
| `UNSUPPORTED_ANCILLARY_RESOURCES` | Declared non-script tracking resources rejected |
| `UNSUPPORTED_UNPRINTED_HANDLE` | Incomplete head/footer capture; no partial replay |
| `UNSUPPORTED_SCRIPT` / `UNSUPPORTED_OPEN_GROUP` | Unsupported tag/queue contract or non-closed dependencies |
| `UNSUPPORTED_QUEUE_MUTATION` | Owned footer registration/inline data changed after freezing |
| `LATE_REGISTRATION` | No retroactive rewrite or newly acquired ownership |
| `LOAD_ERROR` / `LOAD_TIMEOUT` | SDK failed/5-second load deadline expired; no retry this document |
| `SKIPPED_DEPENDENCY` | Dependent SDK/init withheld after dependency failure |
| `CONSENT_REVOKED` | Consent no longer allows a pending replay step |

Network completion does not prove SDK business success. Inline code must be valid;
adapters do not sandbox or classify arbitrary runtime effects. A timed-out request
may still complete/execute later despite removing its script element. Its dependent
handles and after-code remain withheld. Already executed JS cannot be unloaded.
Revocation uses the existing save → reload behavior; denied groups do not replay
in the next document. Consent schema remains 1, with the existing policy version.

## CSP contract

Classic scripts, native nonce attributes/properties and exact known inline bodies
are preserved. Native inline renderer output is not supplemented with callback
code. `integrity`, `crossorigin` and other native attributes survive replay.
Executable SDKs intentionally use async loading; native async/defer timing is
replaced by the group's explicit dependency order. Supported nonce and hash CSP
policies must allow the local consent/adapter controller and configured SDK origins.
This does **not** promise strict-dynamic, Trusted Types or arbitrary CSP support.
Older WordPress nonce support depends on the native renderer/nonce integration;
the native before/external/after tag filter contract is retained on WordPress 5.2.

## Minimal complete integration

```php
// Inside an integration plugin, after ITD Cookies is available.
add_action( 'wp_enqueue_scripts', function () {
    wp_register_script( 'my-statistics-library',
        plugins_url( 'assets/library.js', __FILE__ ), array(), '1.0', false );
    wp_enqueue_script( 'my-statistics-client',
        plugins_url( 'assets/client.js', __FILE__ ),
        array( 'my-statistics-library' ), '1.0', true );
    wp_localize_script( 'my-statistics-library', 'MyStatisticsConfig',
        array( 'mode' => 'site' ) );
    wp_add_inline_script( 'my-statistics-library',
        'window.myStatisticsReady = false;', 'before' );
    wp_add_inline_script( 'my-statistics-client',
        'window.myStatisticsReady = true;', 'after' );
} );

add_action( 'itd_cookies_register_script_groups', function () {
    $result = itd_cookies_register_script_group( 'my-statistics', array(
        'category'       => 'analytics',
        'handles'        => array( 'my-statistics-library', 'my-statistics-client' ),
        'provider_types' => array( 'my-statistics-service' ),
        'resources'      => array(),
        'label'          => __( 'Site statistics', 'my-integration' ),
    ) );
    if ( is_wp_error( $result ) ) {
        // Do not emit tracking hints/pixels/inline fallbacks from this integration.
        return;
    }
} );
```

`tests/fixtures/script-adapters/` is a local-only reference fixture using these
public functions and ordinary WordPress APIs. It includes positive analytics/
marketing groups and public-API negative cases. It is never packaged, must only
run on the named disposable local QA site, and must be removed after acceptance.
