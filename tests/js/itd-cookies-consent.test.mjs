import assert from "node:assert/strict";
import fs from "node:fs";
import test from "node:test";
import { CookieJar, JSDOM } from "jsdom";

const source = fs.readFileSync(
	new URL("../../assets/js/consent.js", import.meta.url),
	"utf8",
);
const html = `
	<button data-itd-cookies-open>Open settings</button>
	<div data-itd-cookies-backdrop hidden></div>
	<section class="itd-cookies" data-itd-cookies-banner hidden aria-hidden="true" aria-labelledby="itd-cookies-title">
		<div data-itd-cookies-summary>
			<h2 id="itd-cookies-title">Cookies</h2>
			<button data-itd-cookies-accept>Accept all</button>
			<button data-itd-cookies-reject>Reject</button>
			<button data-itd-cookies-customize>Customize</button>
		</div>
		<div data-itd-cookies-panel hidden>
			<h2 id="itd-cookies-settings-title" tabindex="-1">Settings</h2>
			<div class="itd-cookies__choices">
			<input type="checkbox" data-itd-cookies-category="functional">
			<input type="checkbox" data-itd-cookies-category="analytics">
			<input type="checkbox" data-itd-cookies-category="marketing">
			</div>
			<button data-itd-cookies-save>Save</button>
			<button data-itd-cookies-accept>Accept all in panel</button>
			<button data-itd-cookies-cancel>Back</button>
		</div>
		<p role="alert" data-itd-cookies-error hidden></p>
	</section>
`;
const providers = [
	{ type: "yandex", id: "12345678", options: { webvisor: false } },
	{ type: "ga4", id: "G-PSW1MY7HB4" },
];

const inventory = { functional: ["1".repeat(32)], analytics: ["2".repeat(32)], marketing: ["3".repeat(32)] };

function createPage({ jar = new CookieJar(), version = "1", configured = providers, registered = inventory } = {}) {
	const dom = new JSDOM(html, {
		cookieJar: jar,
		pretendToBeVisual: true,
		runScripts: "outside-only",
		url: "https://example.test/",
	});
	const { window } = dom;
	window.ITDCookiesConfig = {
		cookieName: "itd_cookies_consent",
        receiptCookieName: "itd_cookies_services",
        inventory: registered,
        saveError: "Could not save",
		legacyCookieName: "itd_modubricks_consent",
		legacyMarkerName: "itd_cookies_legacy_migrated",
		lifetimeDays: 365,
		providers: configured,
		// Match the top-level scalar output of wp_localize_script().
		schema: "1",
		version,
	};
	window.eval(fs.readFileSync(new URL('../../assets/js/providers.js', import.meta.url), 'utf8'));
	window.eval(source);
	return dom;
}

function click(window, selector) {
	const element = window.document.querySelector(selector);
	assert.ok(element, selector);
	element.click();
}

function choice(window) {
	const raw = window.document.cookie.split("; ").find((item) => item.startsWith("itd_cookies_consent="));
	assert.ok(raw, "consent cookie exists");
	return JSON.parse(decodeURIComponent(raw.slice("itd_cookies_consent=".length)));
}

function scripts(window) {
	return window.document.querySelectorAll("script[data-itd-cookies-analytics]");
}

function initializationCounts(window) {
	return {
		yandex: (window.ym?.a || []).filter((call) => call[1] === "init").length,
		ga4: (window.dataLayer || []).filter((call) => call[0] === "config").length,
	};
}

function assertFailClosed(window) {
	assert.equal(window.ITDCookies.allowed('necessary'), true);
	for (const category of ['functional', 'analytics', 'marketing']) {
		assert.equal(window.ITDCookies.allowed(category), false, category);
	}
	assert.equal(window.document.querySelector('[data-itd-cookies-banner]').hidden, false);
	assert.equal(scripts(window).length, 0);
}

test("smart categories normalize absent choices and events for every inventory", (t) => {
	for (const active of [[], ['analytics'], ['marketing'], ['analytics', 'marketing'], ['functional'], ['functional', 'analytics', 'marketing']]) {
		const registered = Object.fromEntries(Object.entries(inventory).map(([category, tokens]) => [category, active.includes(category) ? tokens : []]));
		const jar = new CookieJar();
		const first = createPage({ jar, registered }); t.after(() => first.window.close());
		let event;
		first.window.addEventListener('itd_cookies_consent_changed', (change) => { event = change.detail; });
		click(first.window, '[data-itd-cookies-accept]');
		for (const category of Object.keys(inventory)) {
			assert.equal(choice(first.window).categories[category], active.includes(category));
			assert.equal(event.categories[category], active.includes(category));
			assert.equal(first.window.ITDCookies.allowed(category), active.includes(category));
		}
		const next = createPage({ jar, registered }); t.after(() => next.window.close());
		assert.equal(next.window.document.querySelector('[data-itd-cookies-banner]').hidden, true);
	}
});

test("new service fails closed only for its category and prompts; subsets/removals do not prompt", (t) => {
	const jar = new CookieJar();
	const first = createPage({ jar }); t.after(() => first.window.close());
	click(first.window, '[data-itd-cookies-accept]');
	const changed = { ...inventory, analytics: [...inventory.analytics, '4'.repeat(32)] };
	const next = createPage({ jar, registered: changed }); t.after(() => next.window.close());
	assert.equal(next.window.ITDCookies.allowed('analytics'), false);
	assert.equal(next.window.ITDCookies.allowed('functional'), true);
	assert.equal(scripts(next.window).length, 0);
	assert.equal(next.window.document.querySelector('[data-itd-cookies-banner]').hidden, false);
	click(next.window, '[data-itd-cookies-accept]');
	assert.equal(next.window.ITDCookies.allowed('analytics'), true);
	const subset = createPage({ jar, registered: inventory }); t.after(() => subset.window.close());
	assert.equal(subset.window.document.querySelector('[data-itd-cookies-banner]').hidden, true);
	assert.equal(subset.window.ITDCookies.allowed('analytics'), true);
});

test("new category does not inherit old accept all; old Reject retains exact cookie and expiry", (t) => {
	const jar = new CookieJar();
	const first = createPage({ jar, registered: { functional: [], analytics: inventory.analytics, marketing: [] } }); t.after(() => first.window.close());
	click(first.window, '[data-itd-cookies-accept]');
	assert.equal(choice(first.window).categories.marketing, false);
	const next = createPage({ jar }); t.after(() => next.window.close());
	assert.equal(next.window.ITDCookies.allowed('marketing'), false);
	assert.equal(next.window.ITDCookies.allowed('analytics'), true);
	assert.equal(next.window.document.querySelector('[data-itd-cookies-banner]').hidden, false);
	const old = { schema: 1, version: '1', expiresAt: Date.now() + 86400000, categories: { necessary: true, functional: false, analytics: false, marketing: false } };
	const rejectJar = new CookieJar();
	const raw = encodeURIComponent(JSON.stringify(old));
	rejectJar.setCookieSync('itd_cookies_consent=' + raw + '; Path=/', 'https://example.test/');
	const reject = createPage({ jar: rejectJar }); t.after(() => reject.window.close());
	assert.equal(reject.window.document.querySelector('[data-itd-cookies-banner]').hidden, true);
	assert.equal(reject.window.ITDCookies.allowed('analytics'), false);
	assert.equal(rejectJar.getCookiesSync('https://example.test/').find((cookie) => cookie.key === 'itd_cookies_consent').value, raw);
});

test("unbound positive choice and corrupt/mismatched service receipts never authorize SDKs", (t) => {
	for (const mutate of [() => null, (receipt) => ({ ...receipt, schema: '1' }), (receipt) => ({ ...receipt, version: 'wrong' }), (receipt) => ({ ...receipt, expiresAt: receipt.expiresAt + 1 }), (receipt) => ({ ...receipt, services: '2'.repeat(31) }), (receipt) => ({ ...receipt, extra: true })]) {
		const jar = new CookieJar();
		const first = createPage({ jar }); t.after(() => first.window.close());
		click(first.window, '[data-itd-cookies-accept]');
		const cookie = jar.getCookiesSync('https://example.test/').find((item) => item.key === 'itd_cookies_services');
		const value = mutate(JSON.parse(decodeURIComponent(cookie.value)));
		jar.setCookieSync('itd_cookies_services=' + (value ? encodeURIComponent(JSON.stringify(value)) : '') + '; Path=/', 'https://example.test/');
		const next = createPage({ jar }); t.after(() => next.window.close());
		assertFailClosed(next.window);
	}
});

test("oversized inventory refuses positive save with an error, but Reject remains available", (t) => {
	const registered = { functional: [], analytics: Array.from({ length: 97 }, (_, i) => i.toString(16).padStart(32, '0')), marketing: [] };
	const first = createPage({ registered }); t.after(() => first.window.close());
	let changes = 0;
	first.window.addEventListener('itd_cookies_consent_changed', () => { changes += 1; });
	click(first.window, '[data-itd-cookies-accept]');
	assert.equal(first.window.ITDCookies.allowed('analytics'), false);
	assert.equal(changes, 0);
	assert.equal(first.window.document.querySelector('[data-itd-cookies-error]').hidden, false);
	click(first.window, '[data-itd-cookies-reject]');
	assert.equal(changes, 1);
	assert.equal(choice(first.window).categories.analytics, false);
});

test("blocked cookie writes fail closed and never emit successful consent", (t) => {
	const first = createPage(); t.after(() => first.window.close());
	Object.defineProperty(first.window.document, 'cookie', { get: () => '', set: () => {} });
	let changes = 0;
	first.window.addEventListener('itd_cookies_consent_changed', () => { changes += 1; });
	click(first.window, '[data-itd-cookies-accept]');
	assert.equal(changes, 0);
	assertFailClosed(first.window);
});

test("primary and receipt use Secure/SameSite/Path and matching lifetime on HTTPS", (t) => {
	const jar = new CookieJar();
	const dom = createPage({ jar }); t.after(() => dom.window.close());
	click(dom.window, '[data-itd-cookies-accept]');
	const cookies = jar.getCookiesSync('https://example.test/');
	const primary = cookies.find((item) => item.key === 'itd_cookies_consent');
	const receipt = cookies.find((item) => item.key === 'itd_cookies_services');
	for (const item of [primary, receipt]) {
		assert.equal(item.secure, true);
		assert.equal(item.sameSite, 'lax');
		assert.equal(item.path, '/');
		assert.equal(item.maxAge, 365 * 86400);
	}
	assert.equal(JSON.parse(decodeURIComponent(primary.value)).expiresAt, JSON.parse(decodeURIComponent(receipt.value)).expiresAt);
});

test("fresh visitor has no optional consent and no analytics loader", (t) => {
	const dom = createPage();
	t.after(() => dom.window.close());
	assert.equal(dom.window.document.querySelector("[data-itd-cookies-banner]").hidden, false);
	assert.equal(dom.window.ITDCookies.allowed("necessary"), true);
	assert.equal(dom.window.ITDCookies.allowed("analytics"), false);
	assert.equal(dom.window.ITDCookiesConfig.schema, 1);
	assert.equal(scripts(dom.window).length, 0);
	assert.equal(typeof dom.window.ym, "undefined");
	assert.equal(typeof dom.window.gtag, "undefined");
});

test("accept all persists all categories and starts each tracker once", (t) => {
	const dom = createPage();
	t.after(() => dom.window.close());
	let changes = 0;
	dom.window.addEventListener("itd_cookies_consent_changed", () => { changes += 1; });
	click(dom.window, "[data-itd-cookies-accept]");
	assert.deepEqual(choice(dom.window).categories, {
		necessary: true, functional: true, analytics: true, marketing: true,
	});
	assert.equal(scripts(dom.window).length, 2);
	assert.equal(typeof dom.window.ym, "function");
	assert.equal(typeof dom.window.gtag, "function");
	assert.deepEqual(initializationCounts(dom.window), { yandex: 1, ga4: 1 });
	assert.equal(changes, 1);
	assert.equal(dom.window.document.querySelector("[data-itd-cookies-banner]").hidden, true);
	assert.ok(choice(dom.window).expiresAt > Date.now());
});

test("reject stores necessary only and never starts trackers", (t) => {
	const dom = createPage();
	t.after(() => dom.window.close());
	click(dom.window, "[data-itd-cookies-reject]");
	assert.deepEqual(choice(dom.window).categories, {
		necessary: true, functional: false, analytics: false, marketing: false,
	});
	assert.equal(scripts(dom.window).length, 0);
	assert.equal(dom.window.ITDCookies.allowed("analytics"), false);
});

test("custom functional-only choice persists through reload", (t) => {
	const jar = new CookieJar();
	const first = createPage({ jar });
	t.after(() => first.window.close());
	click(first.window, "[data-itd-cookies-customize]");
	first.window.document.querySelector('[data-itd-cookies-category="functional"]').checked = true;
	click(first.window, "[data-itd-cookies-save]");
	assert.deepEqual(choice(first.window).categories, {
		necessary: true, functional: true, analytics: false, marketing: false,
	});
	assert.equal(scripts(first.window).length, 0);
	const second = createPage({ jar });
	t.after(() => second.window.close());
	assert.equal(second.window.document.querySelector("[data-itd-cookies-banner]").hidden, true);
	assert.equal(second.window.ITDCookies.allowed("functional"), true);
	assert.equal(second.window.ITDCookies.allowed("analytics"), false);
	assert.equal(scripts(second.window).length, 0);
});

test("shortcode opener changes a saved decision", (t) => {
	const jar = new CookieJar();
	const first = createPage({ jar });
	t.after(() => first.window.close());
	click(first.window, "[data-itd-cookies-reject]");
	click(first.window, "[data-itd-cookies-open]");
	assert.equal(first.window.document.querySelector("[data-itd-cookies-panel]").hidden, false);
	assert.equal(first.window.document.querySelector('[data-itd-cookies-category="analytics"]').checked, false);
	first.window.document.querySelector('[data-itd-cookies-category="analytics"]').checked = true;
	click(first.window, "[data-itd-cookies-save]");
	assert.equal(choice(first.window).categories.analytics, true);
	assert.equal(scripts(first.window).length, 2);
	const second = createPage({ jar });
	t.after(() => second.window.close());
	assert.equal(second.window.ITDCookies.allowed("analytics"), true);
	assert.equal(scripts(second.window).length, 2);
	assert.deepEqual(initializationCounts(second.window), { yandex: 1, ga4: 1 });
});

test("turning analytics off persists and prevents loaders on the next page", (t) => {
	const jar = new CookieJar();
	const first = createPage({ jar });
	t.after(() => first.window.close());
	click(first.window, "[data-itd-cookies-accept]");
	assert.equal(scripts(first.window).length, 2);
	assert.deepEqual(initializationCounts(first.window), { yandex: 1, ga4: 1 });
	click(first.window, "[data-itd-cookies-open]");
	first.window.document.querySelector('[data-itd-cookies-category="analytics"]').checked = false;
	click(first.window, "[data-itd-cookies-save]");
	assert.equal(choice(first.window).categories.analytics, false);
	const next = createPage({ jar });
	t.after(() => next.window.close());
	assert.equal(next.window.ITDCookies.allowed("analytics"), false);
	assert.equal(scripts(next.window).length, 0);
});

test("legacy positive choice requires explicit confirmation without deleting the original", (t) => {
    const jar = new CookieJar();
    jar.setCookieSync("itd_modubricks_consent=" + encodeURIComponent(JSON.stringify({ choice: "accepted", version: "1" })) + "; Path=/", "https://example.test/");
    const first = createPage({ jar }); t.after(() => first.window.close());
    assertFailClosed(first.window);
    assert.match(first.window.document.cookie, /itd_cookies_legacy_migrated=/);
    assert.match(first.window.document.cookie, /itd_modubricks_consent=/);
    click(first.window, "[data-itd-cookies-accept]");
    const second = createPage({ jar }); t.after(() => second.window.close());
    assert.equal(second.window.ITDCookies.allowed("analytics"), true);
    assert.equal(scripts(second.window).length, 2);
});

test("mismatched legacy consent does not grant analytics", (t) => {
	const jar = new CookieJar();
	jar.setCookieSync(
		"itd_modubricks_consent=" + encodeURIComponent(JSON.stringify({ choice: "accepted", version: "old" })) + "; Path=/",
		"https://example.test/",
	);
	const dom = createPage({ jar });
	t.after(() => dom.window.close());
	assert.equal(dom.window.ITDCookies.allowed("analytics"), false);
	assert.equal(scripts(dom.window).length, 0);
	assert.equal(dom.window.document.querySelector("[data-itd-cookies-banner]").hidden, false);
});

test("absent, corrupt and unknown legacy cookies fail closed", (t) => {
	const cases = [
		null,
		"%ZZ",
		'{"choice":"accepted",',
		JSON.stringify({ choice: "unknown", version: "1" }),
		JSON.stringify({ choice: true, version: "1" }),
		JSON.stringify({ choice: "accepted", version: "1", schema: 0 }),
		JSON.stringify({ choice: "accepted", version: "1", schema: 2 }),
	];
	for (const raw of cases) {
		const jar = new CookieJar();
		if (raw !== null) {
			jar.setCookieSync("itd_modubricks_consent=" + encodeURIComponent(raw) + "; Path=/", "https://example.test/");
		}
		const dom = createPage({ jar });
		t.after(() => dom.window.close());
		assertFailClosed(dom.window);
	}
});

test("malformed and unknown new consent fields fail closed", (t) => {
	const valid = {
		schema: 1, version: '1', expiresAt: Date.now() + 86400000,
		categories: { necessary: true, functional: true, analytics: true, marketing: true },
	};
	const cases = [
		'{"schema":1,',
		{ ...valid, schema: '1' },
		{ ...valid, schema: 0 },
		{ ...valid, schema: 2 },
		{ ...valid, extra: true },
		{ ...valid, categories: { ...valid.categories, extra: true } },
		{ ...valid, categories: { necessary: true, analytics: true, marketing: true } },
		{ ...valid, categories: { ...valid.categories, analytics: 'true' } },
	];
	for (const candidate of cases) {
		const jar = new CookieJar();
		const raw = typeof candidate === 'string' ? candidate : JSON.stringify(candidate);
		jar.setCookieSync('itd_cookies_consent=' + encodeURIComponent(raw) + '; Path=/', 'https://example.test/');
		const dom = createPage({ jar });
		t.after(() => dom.window.close());
		assertFailClosed(dom.window);
	}
});

test("old consent schema fails closed", (t) => {
	const jar = new CookieJar();
	jar.setCookieSync("itd_cookies_consent=" + encodeURIComponent(JSON.stringify({
		schema: 0, version: "1", expiresAt: Date.now() + 86400000,
		categories: { necessary: true, functional: true, analytics: true, marketing: true },
	})) + "; Path=/", "https://example.test/");
	const dom = createPage({ jar });
	t.after(() => dom.window.close());
	assert.equal(dom.window.ITDCookies.allowed("analytics"), false);
	assert.equal(scripts(dom.window).length, 0);
});

test("expired or policy-version-mismatched consent shows banner", (t) => {
	const jar = new CookieJar();
	jar.setCookieSync(
		"itd_cookies_consent=" + encodeURIComponent(JSON.stringify({
			schema: 1, version: "1", expiresAt: Date.now() - 1000,
			categories: { necessary: true, functional: true, analytics: true, marketing: true },
		})) + "; Path=/",
		"https://example.test/",
	);
	const expired = createPage({ jar });
	t.after(() => expired.window.close());
	assert.equal(expired.window.ITDCookies.allowed("analytics"), false);
	const changed = createPage({ jar, version: "2" });
	t.after(() => changed.window.close());
	assert.equal(changed.window.ITDCookies.allowed("analytics"), false);
	assert.equal(scripts(changed.window).length, 0);
});

test("disabled or invalid provider configuration never creates a loader", (t) => {
	const dom = createPage({ configured: [{ type: "yandex", id: "<script>" }, { type: "ga4", id: "bad" }] });
	t.after(() => dom.window.close());
	click(dom.window, "[data-itd-cookies-accept]");
	assert.equal(scripts(dom.window).length, 0);
	const disabled = createPage({ configured: [] });
	t.after(() => disabled.window.close());
	click(disabled.window, "[data-itd-cookies-accept]");
	assert.equal(scripts(disabled.window).length, 0);
});

test("saving analytics consent twice does not append duplicate loaders", (t) => {
	const dom = createPage();
	t.after(() => dom.window.close());
	click(dom.window, "[data-itd-cookies-accept]");
	assert.deepEqual(initializationCounts(dom.window), { yandex: 1, ga4: 1 });
	click(dom.window, "[data-itd-cookies-accept]");
	assert.equal(scripts(dom.window).length, 2);
	assert.deepEqual(initializationCounts(dom.window), { yandex: 1, ga4: 1 });
	click(dom.window, "[data-itd-cookies-open]");
	click(dom.window, "[data-itd-cookies-save]");
	assert.equal(scripts(dom.window).length, 2);
	assert.deepEqual(initializationCounts(dom.window), { yandex: 1, ga4: 1 });
});

test('modal Escape and focus return preserve a v0.1.0 decision', (t) => {
	const dom = createPage(); t.after(() => dom.window.close());
	const {window} = dom;
	click(window, '[data-itd-cookies-reject]');
	const original = choice(window);
	const opener = window.document.querySelector('[data-itd-cookies-open]');
	opener.focus(); opener.click();
	const banner = window.document.querySelector('[data-itd-cookies-banner]');
	assert.equal(banner.getAttribute('aria-modal'), 'true');
	assert.equal(window.document.activeElement.id, 'itd-cookies-settings-title');
	window.document.querySelector('[data-itd-cookies-category="analytics"]').checked = true;
	window.document.dispatchEvent(new window.KeyboardEvent('keydown', {key:'Escape',bubbles:true}));
	assert.equal(banner.hidden, true);
	assert.equal(window.document.activeElement, opener);
	assert.deepEqual(choice(window), original);
	assert.equal(scripts(window).length, 0);
});

test('initial Escape returns to summary without granting consent', (t) => {
	const dom = createPage(); t.after(() => dom.window.close());
	const {window} = dom;
	click(window, '[data-itd-cookies-customize]');
	window.document.dispatchEvent(new window.KeyboardEvent('keydown', {key:'Escape',bubbles:true}));
	assert.equal(window.document.querySelector('[data-itd-cookies-summary]').hidden, false);
	assert.equal(window.document.querySelector('[data-itd-cookies-panel]').hidden, true);
	assertFailClosed(window);
	assert.equal(window.document.cookie.includes('itd_cookies_consent='), false);
});

test('panel Accept all and keyboard wrap work, disabled categories preserve existing consent', (t) => {
	const dom = createPage(); t.after(() => dom.window.close());
	const {window} = dom;
	click(window, '[data-itd-cookies-customize]');
	const cancel = window.document.querySelector('[data-itd-cookies-cancel]');
	cancel.focus();
	window.document.dispatchEvent(new window.KeyboardEvent('keydown', {key:'Tab',bubbles:true,cancelable:true}));
	assert.equal(window.document.activeElement, window.document.querySelector('[data-itd-cookies-category="functional"]'));
	window.document.dispatchEvent(new window.KeyboardEvent('keydown', {key:'Tab',shiftKey:true,bubbles:true,cancelable:true}));
	assert.equal(window.document.activeElement, cancel);
	window.document.querySelector('[data-itd-cookies-panel] [data-itd-cookies-accept]').click();
	assert.equal(choice(window).categories.analytics, true);
	click(window, '[data-itd-cookies-open]');
	window.document.querySelector('[data-itd-cookies-category="functional"]').disabled = true;
	click(window, '[data-itd-cookies-save]');
	assert.equal(choice(window).categories.functional, true);
	assert.deepEqual(initializationCounts(window), {yandex:1,ga4:1});
});

const allProviders = [...providers,
 {type:'gtm',id:'GTM-TEST123',category:'analytics'},
 {type:'clarity',id:'test12345',category:'analytics'},
 {type:'meta',id:'123456789012345',category:'marketing'},
];
function providerCounts(window) {
 const scripts=[...window.document.querySelectorAll('script[src]')];
 return {
  ...initializationCounts(window),
  gtm:(window.dataLayer||[]).filter(call=>call.event==='gtm.js').length,
  clarity:scripts.filter(script=>script.src.startsWith('https://www.clarity.ms/tag/')).length,
  meta:(window.fbq?.queue||[]).filter(call=>call[0]==='init').length,
 };
}
function assertProviders(window, analytics, marketing) {
 assert.deepEqual(providerCounts(window),{yandex:+analytics,ga4:+analytics,gtm:+analytics,clarity:+analytics,meta:+marketing});
 assert.equal(window.document.querySelectorAll('script[src]').length,4*+analytics + +marketing);
 if (analytics) {
  const signals=(window.clarity.q||[]).filter(call=>call[0]==='consentv2');
  assert.equal(signals.at(-1)[1].analytics_Storage,'granted');
  assert.equal(signals.at(-1)[1].ad_Storage,marketing?'granted':'denied');
 }
}
for (const [name,analytics,marketing] of [['reject',false,false],['analytics only',true,false],['marketing only',false,true],['accept all',true,true]]) {
 test('all five providers: '+name+' gates and survives reload',t=>{
  const jar=new CookieJar();const dom=createPage({jar,configured:allProviders});t.after(()=>dom.window.close());
  assertProviders(dom.window,false,false);
  click(dom.window,'[data-itd-cookies-customize]');
  dom.window.document.querySelector('[data-itd-cookies-category="analytics"]').checked=analytics;
  dom.window.document.querySelector('[data-itd-cookies-category="marketing"]').checked=marketing;
  click(dom.window,'[data-itd-cookies-save]');assertProviders(dom.window,analytics,marketing);
  const before=choice(dom.window);
  const next=createPage({jar,configured:allProviders});t.after(()=>next.window.close());
  assert.deepEqual(choice(next.window),before);assertProviders(next.window,analytics,marketing);
 });
}
for (const type of ['gtm','clarity','meta']) {
 test(type+' disabled and malformed IDs fail closed',t=>{
  const dom=createPage({configured:[]});t.after(()=>dom.window.close());click(dom.window,'[data-itd-cookies-accept]');assertProviders(dom.window,false,false);
  for(const id of ['', '<script>alert(1)</script>', 'https://example.test/', '123?x=1', {}, 123, 'A'.repeat(100)]) {
   const bad=createPage({configured:[{type,id}]});t.after(()=>bad.window.close());click(bad.window,'[data-itd-cookies-accept]');assertProviders(bad.window,false,false);
  }
 });
}
test('dedup: existing consent, reopen/save, repeated accept and custom to accept',t=>{
 const jar=new CookieJar();const old=createPage({jar,configured:providers});t.after(()=>old.window.close());
 click(old.window,'[data-itd-cookies-accept]');const original=choice(old.window);
 const dom=createPage({jar,configured:[...allProviders,...allProviders]});t.after(()=>dom.window.close());
 assert.deepEqual(choice(dom.window),original);assertProviders(dom.window,true,true);
 for(let i=0;i<3;i++) {click(dom.window,'[data-itd-cookies-open]');click(dom.window,'[data-itd-cookies-save]');click(dom.window,'[data-itd-cookies-accept]');}
 dom.window.eval(fs.readFileSync(new URL('../../assets/js/providers.js',import.meta.url),'utf8'));
 click(dom.window,'[data-itd-cookies-accept]');assertProviders(dom.window,true,true);
 assert.equal((dom.window.fbq.queue||[]).filter(call=>call[0]==='trackSingle').length,1);
 const custom=createPage({configured:allProviders});t.after(()=>custom.window.close());
 click(custom.window,'[data-itd-cookies-customize]');custom.window.document.querySelector('[data-itd-cookies-category="analytics"]').checked=true;click(custom.window,'[data-itd-cookies-save]');assertProviders(custom.window,true,false);
 click(custom.window,'[data-itd-cookies-accept]');assertProviders(custom.window,true,true);
});
for(const category of ['analytics','marketing']) {
 test('revoking '+category+' persists and prevents its providers next document',t=>{
  const jar=new CookieJar();const first=createPage({jar,configured:allProviders});t.after(()=>first.window.close());
  click(first.window,'[data-itd-cookies-accept]');click(first.window,'[data-itd-cookies-open]');
  first.window.document.querySelector('[data-itd-cookies-category="'+category+'"]').checked=false;click(first.window,'[data-itd-cookies-save]');
  assert.equal(choice(first.window).categories[category],false);
  const next=createPage({jar,configured:allProviders});t.after(()=>next.window.close());assertProviders(next.window,category!=='analytics',category!=='marketing');
 });
}
test('unknown/prototype types and category spoofing cannot bypass consent',t=>{
 const bad=[{type:'__proto__',id:'x'},{type:'constructor',id:'x'},{type:'custom',id:'x',url:'https://example.test/x.js'},
 {type:'meta',id:'12345678',category:'analytics'},{type:'gtm',id:'GTM-TEST123',category:'necessary'}];
 const dom=createPage({configured:bad});t.after(()=>dom.window.close());click(dom.window,'[data-itd-cookies-accept]');assertProviders(dom.window,false,false);
});
test('existing SDK tags are not reinserted or reinitialized',t=>{
 const dom=createPage({configured:allProviders});t.after(()=>dom.window.close());
 for (const src of ['https://mc.yandex.ru/metrika/tag.js','https://www.googletagmanager.com/gtag/js?id=G-TEST12345','https://www.googletagmanager.com/gtm.js?id=GTM-TEST123','https://www.clarity.ms/tag/test12345','https://connect.facebook.net/en_US/fbevents.js']) {
  const script=dom.window.document.createElement('script');script.src=src;dom.window.document.head.appendChild(script);
 }
 click(dom.window,'[data-itd-cookies-accept]');assert.equal(dom.window.document.querySelectorAll('script').length,5);
 assert.equal(typeof dom.window.fbq,'undefined');assert.equal(typeof dom.window.clarity,'undefined');assert.equal(typeof dom.window.ym,'undefined');
});


test("reopening a long settings panel resets its inner scroll without losing consent", (t) => {
	const dom = createPage();
	t.after(() => dom.window.close());
	const { window } = dom;
	click(window, '[data-itd-cookies-accept]');
	const saved = choice(window);
	click(window, '[data-itd-cookies-open]');
	const choices = window.document.querySelector('.itd-cookies__choices');
	choices.scrollTop = 500;
	click(window, '[data-itd-cookies-cancel]');
	click(window, '[data-itd-cookies-open]');
	assert.equal(choices.scrollTop, 0);
	assert.deepEqual(choice(window), saved);
});
