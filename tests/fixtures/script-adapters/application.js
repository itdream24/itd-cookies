(function () {
	"use strict";
	window.itdFixtureTrace = window.itdFixtureTrace || [];
	window.itdFixtureApplication = (window.itdFixtureApplication || 0) + 1;
	var violations = [];
    var replayStarted = {};
    var replayDurations = {};
    window.addEventListener("itd_cookies_script_group_diagnostic", function (event) {
        var state = event.detail;
        if (state.code === "WAITING_FOR_CONSENT") replayStarted[state.group_id] = performance.now();
        if (state.code === "ACTIVATED" || state.code === "FAILED") replayDurations[state.group_id] = Math.round((performance.now() - replayStarted[state.group_id]) * 100) / 100;
    });
    window.addEventListener("itd_cookies_consent_changed", function () {
        Object.keys(replayStarted).forEach(function (group) {
            if (replayDurations[group] === undefined) replayStarted[group] = performance.now();
        });
    });
	window.addEventListener("securitypolicyviolation", function (event) {
		violations.push({directive: event.effectiveDirective, blocked: event.blockedURI === "inline" ? "inline" : "resource"});
	});
	function cookieState() {
		var item = document.cookie.split("; ").find(function (value) { return value.indexOf("itd_cookies_consent=") === 0; });
		if (!item) return null;
		try {
			var state = JSON.parse(decodeURIComponent(item.slice("itd_cookies_consent=".length)));
			return {schema: state.schema, version: state.version, expiresAt: state.expiresAt, categories: state.categories};
		} catch { return "INVALID"; }
	}
	function collect() {
		var output = document.getElementById("itd-fixture-evidence");
		if (!output) return;
		var url = new URL(window.location.href);
		url.searchParams.set("itd_adapter_counts", "1");
		fetch(url.href, {cache: "no-store"}).then(function (response) { return response.json(); }).then(function (counts) {
			var events = window.ITDCookiesScriptAdapters ? window.ITDCookiesScriptAdapters.getDiagnostics() : [];
			var unsettled = events.some(function (event) {
				return event.code === "WAITING_FOR_CONSENT" && window.ITDCookies.allowed(event.group_id === "fixture-marketing" ? "marketing" : "analytics") && !events.some(function (terminal) {
					return terminal.group_id === event.group_id && (["ACTIVATED", "FAILED"].indexOf(terminal.code) !== -1 || terminal.code.indexOf("UNSUPPORTED_") === 0);
				});
			});
			var evidence = {
				counts: counts,
				trace: window.itdFixtureTrace,
				application: window.itdFixtureApplication,
				neighbor: window.itdFixtureNeighbor,
				jquery: !!window.jQuery,
				cdn: typeof window.dayjs === "function",
				theme: !!document.querySelector(".wp-site-blocks"),
				consent: cookieState(),
				diagnostics: events,
				cspViolations: violations,
				nativeGA4: (window.dataLayer || []).filter(function (call) { return call[0] === "config"; }).length,
				nativeGA4Tags: document.querySelectorAll('script[data-itd-cookies-analytics="ga4"],script[data-itd-cookies-provider="ga4"]').length,
				replayDurationMs: replayDurations
			};
			output.textContent = JSON.stringify(evidence, null, 2);
			output.setAttribute("aria-busy", unsettled ? "true" : "false");
		});
	}
	document.addEventListener("DOMContentLoaded", function () {
		var reset = document.getElementById("itd-fixture-fresh");
		if (!reset) return;
		reset.addEventListener("click", function () {
			["itd_cookies_consent", "itd_modubricks_consent", "itd_cookies_legacy_migrated"].forEach(function (name) { document.cookie = name + "=;path=/;max-age=0"; });
			window.location.reload();
		});
		document.getElementById("itd-fixture-repeat").addEventListener("click", function () {
			window.dispatchEvent(new CustomEvent("itd_cookies_consent_changed"));
			collect();
		});
		collect();
		window.setInterval(collect, 800);
	});
}());
