import assert from "node:assert/strict";
import fs from "node:fs";
import test from "node:test";
import { JSDOM, requestInterceptor, VirtualConsole } from "jsdom";

const source = fs.readFileSync(new URL("../../assets/js/script-adapters.js", import.meta.url), "utf8");
const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function page({analytics = false, marketing = false, functional = false, failure = "", broken = "", module = false} = {}) {
	const requests = [];
	const resources = {interceptors: [requestInterceptor((request) => {
        const name = new URL(request.url).pathname.slice(1);
        requests.push(name);
        if (broken === "404" && name === "library") return new Response("fixture 404", {status: 404});
        if (broken === "timeout" && name === "library") return new Promise(() => {});
        return new Response(`window.trace.push('${name}');`, {headers: {"Content-Type": "application/javascript"}});
    })]};
    const groups = [
		{group_id: "analytics", category: "analytics", failure, nodes: [
			{handle: "library", deps: [], template: "lib"},
			{handle: "dependent", deps: ["library"], template: "dep"},
		]},
		{group_id: "marketing", category: "marketing", failure: "", nodes: [{handle: "marketing", deps: [], template: "market"}]},
		{group_id: "functional", category: "functional", failure: "", nodes: [{handle: "functional", deps: [], template: "func"}]},
		{group_id: "necessary", category: "necessary", failure: "", nodes: [{handle: "necessary", deps: [], template: "need"}]},
	];
	const dom = new JSDOM(`<!doctype html><html><head>
		<script>window.trace = []; window.unknown = 1;</script>
		<template id="lib"><script nonce="owned-nonce">window.trace.push('data');\nwindow.localized = 'unchanged';</script><script nonce="owned-nonce">window.trace.push('before');</script><script src="/library" ${module ? 'type="module"' : ''} nonce="owned-nonce" integrity="sha256-fixture" crossorigin="anonymous"></script><script nonce="owned-nonce">window.trace.push('after');</script></template>
		</head><body>
		<template id="dep"><script src="/dependent"></script><script>window.trace.push('dependent-after');</script></template>
		<template id="market"><script src="/marketing"></script><script>window.trace.push('marketing-init');</script></template>
		<template id="func"><script src="/functional"></script></template>
		<template id="need"><script src="/necessary"></script></template>
		<script type="application/json" id="itd-cookies-script-groups">${JSON.stringify({groups, diagnostics: []})}</script>
		</body></html>`, {url: "https://example.test/", runScripts: "dangerously", resources, virtualConsole: new VirtualConsole()});
	const permitted = {necessary: true, functional, analytics, marketing};
	dom.window.ITDCookies = {allowed: (category) => permitted[category]};
	dom.window.eval(source);
	return {dom, window: dom.window, requests, permitted};
}

async function settled(window, group) {
	for (let i = 0; i < 700; i += 1) {
		if (window.ITDCookiesScriptAdapters.getDiagnostics().some((event) => event.group_id === group && ["ACTIVATED", "FAILED"].includes(event.code))) return;
		await delay(10);
	}
	assert.fail(`No terminal state for ${group}`);
}

test("fresh/rejected optional groups are inert; unknown and necessary scripts work", async (t) => {
	const {dom, window, requests} = page();
	t.after(() => dom.window.close());
	await settled(window, "necessary");
	assert.deepEqual(requests, ["necessary"]);
	assert.equal(window.unknown, 1);
	assert.deepEqual(Array.from(window.trace), ["necessary"]);
	window.dispatchEvent(new window.CustomEvent("itd_cookies_consent_changed"));
	await delay(20);
	assert.deepEqual(requests, ["necessary"]);
});

for (const [name, consent, expected] of [
	["analytics only", {analytics: true}, ["library", "dependent", "necessary"]],
	["marketing only", {marketing: true}, ["marketing", "necessary"]],
	["all four categories", {analytics: true, marketing: true, functional: true}, ["library", "dependent", "marketing", "functional", "necessary"]],
]) {
	test(`${name}: allowed SDKs execute once with dependency/inline ordering`, async (t) => {
		const {dom, window, requests} = page(consent);
		t.after(() => dom.window.close());
		for (const group of ["analytics", "marketing", "functional", "necessary"]) if (consent[group] || group === "necessary") await settled(window, group);
		assert.deepEqual(requests.slice().sort(), expected.slice().sort());
		if (consent.analytics) {
			assert.deepEqual(Array.from(window.trace).filter((entry) => !["necessary", "functional", "marketing", "marketing-init"].includes(entry)), ["data", "before", "library", "after", "dependent", "dependent-after"]);
			assert.equal(window.localized, "unchanged");
			const replayed = window.document.querySelector('script[src="/library"]');
			assert.equal(replayed.nonce, "owned-nonce");
			assert.equal(replayed.getAttribute("integrity"), "sha256-fixture");
			assert.equal(replayed.getAttribute("crossorigin"), "anonymous");
			assert.equal(window.document.querySelector('script[nonce]').textContent, window.document.getElementById('lib').content.querySelector('script').textContent);
		}
		for (let i = 0; i < 3; i += 1) window.dispatchEvent(new window.CustomEvent("itd_cookies_consent_changed"));
		await delay(30);
		assert.equal(requests.length, expected.length);
	});
}

test("permission change uses the existing engine and never reinitializes", async (t) => {
	const {dom, window, requests, permitted} = page();
	t.after(() => dom.window.close());
	await settled(window, "necessary");
	permitted.analytics = true;
	window.dispatchEvent(new window.CustomEvent("itd_cookies_consent_changed"));
	await settled(window, "analytics");
	permitted.analytics = false;
	window.dispatchEvent(new window.CustomEvent("itd_cookies_consent_changed"));
	assert.equal(requests.filter((value) => value === "library").length, 1);
	const next = page();
	t.after(() => next.dom.window.close());
	await settled(next.window, "necessary");
	assert.deepEqual(next.requests, ["necessary"]);
});

for (const broken of ["404", "timeout"]) {
	test(`${broken}: dependent init is skipped and independent marketing progresses`, async (t) => {
		const {dom, window, requests} = page({analytics: true, marketing: true, broken});
		t.after(() => dom.window.close());
		await settled(window, "marketing");
		assert.ok(window.trace.includes("marketing-init"));
		assert.ok(!window.trace.includes("dependent-after"));
		await settled(window, "analytics");
		const codes = window.ITDCookiesScriptAdapters.getDiagnostics().map((event) => event.code);
		assert.ok(codes.includes(broken === "404" ? "LOAD_ERROR" : "LOAD_TIMEOUT"));
		assert.ok(codes.includes("SKIPPED_DEPENDENCY"));
		assert.ok(!requests.includes("dependent"));
		assert.ok(!window.trace.includes("after"));
		window.dispatchEvent(new window.CustomEvent("itd_cookies_consent_changed"));
		await delay(20);
		assert.equal(requests.filter((entry) => entry === "library").length, 1);
	});
}

test("unsupported module rejects the entire group before its data/before executes", async (t) => {
	const {dom, window, requests} = page({analytics: true, module: true});
	t.after(() => dom.window.close());
	await settled(window, "necessary");
	assert.deepEqual(requests, ["necessary"]);
	assert.ok(!window.trace.includes("data"));
	assert.ok(window.ITDCookiesScriptAdapters.getDiagnostics().some((event) => event.code === "UNSUPPORTED_SCRIPT"));
});

test("ownership rejection and missing/unprinted handles cannot execute partial groups", async (t) => {
	const {dom, window, requests} = page({analytics: true, failure: "FAILED_OWNERSHIP_CONFLICT"});
	t.after(() => dom.window.close());
	await settled(window, "necessary");
	assert.deepEqual(requests, ["necessary"]);
	const snapshot = window.ITDCookiesScriptAdapters.getDiagnostics();
	snapshot[0].code = "mutated";
	assert.ok(!window.ITDCookiesScriptAdapters.getDiagnostics().some((event) => event.code === "mutated"));
	assert.ok(!JSON.stringify(snapshot).includes("example.test"));
});

test("zero-group runtime contains no page scans or global interception", () => {
	for (const forbidden of [/MutationObserver/, /(?:Element|Node|Document)\.prototype\./, /querySelectorAll/, /document\.write/, /fetch\(/, /XMLHttpRequest/]) assert.ok(!forbidden.test(source));
	const dom = new JSDOM("<p>unknown site</p>", {runScripts: "outside-only"});
	dom.window.eval(source);
	assert.equal(dom.window.document.querySelectorAll("script").length, 0);
	dom.window.close();
});
