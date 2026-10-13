# 0.4.5 consent inventory decision (before implementation)

## Why category booleans alone are unsafe

0.4.4 Accept all stores true even for absent categories. Neither that cookie nor
legacy ModuBricks accepted consent identifies services known at the decision.
Silently binding old positive consent to today's registry would authorize newly
added services. CSS hiding is insufficient.

## Compatible payload, additional service receipt

Keep the four-field `itd_cookies_consent` schema1 (policy version, expiry and four
booleans). Add first-party `itd_cookies_services`: schema1, same policy version,
same expiry and packed sorted 128-bit service identity tokens. Native identities
include validated provider ID/privacy options; external descriptors use stable
id/category/provider type and optional `consent_revision` (default1). Script
groups include stable group/handle/provider identifiers and optional revision.
Labels, translations and descriptions are not identity changes. Registry order
does not matter. No script/iframe/cookie scanning or fingerprint of visitor data.

Only enabled registered services determine visible categories. Necessary always
appears; absent categories have no optional control/unused label and normalize
false on new choices. No-service UI acknowledges the plugin's necessary consent
cookie and states that external integrations can operate independently.

`allowed(category)` requires an enabled category, a valid unexpired primary
decision and complete coverage of the category's current services by its receipt.
New services cannot inherit an old category flag. Unchanged categories remain
allowed while a changed category fails closed and the visitor is asked again.
Removing a service does not force a new decision. Re-enabling the identical
previously consented service is not a new service; change its revision/policy
version for changed vendor/purpose. Changing native IDs/options is detected.
External integrations and GTM owners must revise identity/policy for changes
inside a vendor container; those contents cannot be inferred automatically.

Old schema1 negative decisions are retained with original expiry, including
0.4.4 Reject. Positive schema1 without a matching receipt stays readable but
cannot authorize optional services; it prompts once for a bound choice. Legacy
accepted ModuBricks consent cannot be safely auto-granted; mark it inspected and
ask again, preserving old cookies/settings/migration marker. No silent cookie
rewrite merely on read. Policy-version changes still require a fresh decision.

Receipt has four exact keys (schema/version/expiresAt/services). The packed
string permits up to 96 tokens; encoded receipt is at most 3800 bytes and primary
at most 2048 bytes. Expiry must match exactly and be a JS-safe integer.
Receipt writes use the same Secure (HTTPS), SameSite=Lax, Path=/ and expiry as
primary consent. A maximum encoded receipt size prevents browser cookie limits:
oversize positive choices report an error without pretending to save/grant them;
Reject remains available. Partial/mismatched writes fail closed. Cookies remain
preference signals, not authentication or authorization.

## Cache and registration contract

Shared HTML/config is independent of visitor cookies. Freeze/localize the current
inventory after the existing script-registration/validation hook. Current-page
service sets may be subsets of the receipt: moving between pages does not itself
require re-consent. A newly encountered service does. No per-user cache variation,
nonce/REST polling, database writes on ordinary registry reads or new loader.
Cached old HTML cannot describe/load new services; invalidate page/asset caches
on deployment and service configuration changes for timely UI/policy updates.
Query-versioned assets remain in place. Missing inventory config fails closed.

PHP extensions must register their descriptors before calling `allowed()` and
must not vary shared cached HTML solely from visitor consent. Site-wide policy
inventories require site-wide descriptors; conditional frontend registrations
describe only their current page. The plugin cannot infer unregistered future
services. Script Adapters API stays compatible; optional consent_revision is
metadata, never executable code or an arbitrary URL. SDK gating/dependencies/
ownership/error behavior remains unchanged.

## Acceptance gates

Browser acceptance discovered that block themes render policy shortcodes before
wp_head/enqueue. The policy now requests group declarations once (no SDKs, no
queue freeze). Regular enqueue registration reuses that declaration and still
validates/quarantines dependencies at 999. Integrations should declare their
owned handles inside the public registration callback; a dependency registered
by ordinary enqueue callbacks is still resolved at the normal validation point.
Policy describes declared services; a later queue/network failure remains a
runtime diagnostic rather than evidence that a configured service never exists.
No JS DOM insertion, output buffering of theme markup or per-user cache variation.

Zero / Analytics / Marketing / both / Functional adapter / all; hidden false on
Accept/Save; new service after positive choice; old positive/Reject; PHP/JS/event
agreement; policy and legacy migration; cached HTML independence; mobile/keyboard;
native0.4.4 upgrade; PHPUnit/JS/full CI; restore immutable baseline-v0.4.4.

Candidate only. No main merge, stable tag/Release or production deployment.
