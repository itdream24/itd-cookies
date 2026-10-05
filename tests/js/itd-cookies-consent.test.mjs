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
			<input type="checkbox" data-itd-cookies-category="functional">
			<input type="checkbox" data-itd-cookies-category="analytics">
			<input type="checkbox" data-itd-cookies-category="marketing">
			<button data-itd-cookies-save>Save</button>
			<button data-itd-cookies-accept>Accept all in panel</button>
			<button data-itd-cookies-cancel>Back</button>
		</div>
	</section>
`;
const providers = [
	{ type: "yandex", id: "12345678", options: { webvisor: false } },
	{ type: "ga4", id: "G-PSW1MY7HB4" },
];

function createPage({ jar = new CookieJar(), version = "1", configured = providers } = {}) {
	const dom = new JSDOM(html, {
		cookieJar: jar,
		pretendToBeVisual: true,
		runScripts: "outside-only",
		url: "https://example.test/",
	});
	const { window } = dom;
	window.ITDCookiesConfig = {
		cookieName: "itd_cookies_consent",
		legacyCookieName: "itd_modubricks_consent",
		legacyMarkerName: "itd_cookies_legacy_migrated",
		lifetimeDays: 365,
		providers: configured,
		// Match the top-level scalar output of wp_localize_script().
		schema: "1",
		version,
	};
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

test("legacy accepted consent imports analytics only once", (t) => {
	const jar = new CookieJar();
	jar.setCookieSync(
		"itd_modubricks_consent=" + encodeURIComponent(JSON.stringify({ choice: "accepted", version: "1" })) + "; Path=/",
		"https://example.test/",
	);
	const first = createPage({ jar });
	t.after(() => first.window.close());
	assert.deepEqual(choice(first.window).categories, {
		necessary: true, functional: false, analytics: true, marketing: false,
	});
	assert.equal(scripts(first.window).length, 2);
	assert.match(first.window.document.cookie, /itd_cookies_legacy_migrated=/);
	const second = createPage({ jar });
	t.after(() => second.window.close());
	assert.equal(second.window.ITDCookies.allowed("analytics"), true);
	assert.equal(scripts(second.window).length, 2);
	assert.deepEqual(initializationCounts(second.window), { yandex: 1, ga4: 1 });
	click(second.window, "[data-itd-cookies-open]");
	second.window.document.querySelector('[data-itd-cookies-category="analytics"]').checked = false;
	click(second.window, "[data-itd-cookies-save]");
	const third = createPage({ jar });
	t.after(() => third.window.close());
	assert.equal(third.window.ITDCookies.allowed("analytics"), false);
	assert.equal(scripts(third.window).length, 0);
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
