(function () {
	"use strict";
	if (window.ITDCookiesScriptAdapters) return;
	var events = [];
	var ready = false;
	var groups = [];
	var attempts = Object.create(null);
	var timeout = 5000;

	function report(group, code, handle) {
		var event = {group_id: group, code: code};
		if (handle) event.handle = handle;
		events.push(event);
		window.dispatchEvent(new CustomEvent("itd_cookies_script_group_diagnostic", {detail: Object.assign({}, event)}));
	}
	window.ITDCookiesScriptAdapters = {
		getDiagnostics: function () { return events.map(function (event) { return Object.assign({}, event); }); }
	};

	function allowed(category) {
		return !!(window.ITDCookies && window.ITDCookies.allowed(category));
	}
	function templateScripts(node) {
		var template = document.getElementById(node.template);
		if (!template || template.tagName !== "TEMPLATE") throw new Error("UNSUPPORTED_UNPRINTED_HANDLE");
		var scripts = [];
		var external = 0;
		Array.prototype.forEach.call(template.content.childNodes, function (child) {
			if (child.nodeType === 3 && !child.textContent.trim()) return;
			if (child.nodeType !== 1 || child.tagName !== "SCRIPT") throw new Error("UNSUPPORTED_SCRIPT");
			var type = (child.getAttribute("type") || "").toLowerCase();
			if (["", "text/javascript", "application/javascript", "text/ecmascript", "application/ecmascript"].indexOf(type) === -1 || child.hasAttribute("nomodule")) throw new Error("UNSUPPORTED_SCRIPT");
			// Event-handler attributes can evade controlled SDK completion semantics.
			Array.prototype.forEach.call(child.attributes, function (attribute) {
				if (/^on/i.test(attribute.name)) throw new Error("UNSUPPORTED_SCRIPT");
			});
			if (child.hasAttribute("src")) {
				external += 1;
				if (!child.getAttribute("src") || child.textContent.trim()) throw new Error("UNSUPPORTED_SCRIPT");
			}
			scripts.push(child);
		});
		if (external !== 1) throw new Error("UNSUPPORTED_SCRIPT");
		return {template: template, scripts: scripts};
	}
	function replay(old, template, category) {
		if (!allowed(category)) return Promise.reject(new Error("CONSENT_REVOKED"));
		return new Promise(function (resolve, reject) {
			var script = document.createElement("script");
			Array.prototype.forEach.call(old.attributes, function (attribute) {
				if (attribute.name !== "src" && attribute.name !== "async" && attribute.name !== "defer") script.setAttribute(attribute.name, attribute.value);
			});
			// CSP may hide the serialized nonce on connected elements.
			if (old.nonce) script.nonce = old.nonce;
			script.textContent = old.textContent;
			if (!old.hasAttribute("src")) {
				template.parentNode.insertBefore(script, template);
				resolve();
				return;
			}
			// Each DAG controls its own order. async=false would serialize all groups.
			script.async = true;
			script.setAttribute("async", "");
			var timer = window.setTimeout(function () { finish("LOAD_TIMEOUT"); }, timeout);
			var settled = false;
			function finish(code) {
				if (settled) return;
				settled = true;
				window.clearTimeout(timer);
				script.onload = script.onerror = null;
				if (code) {
					script.remove();
					reject(new Error(code));
				} else resolve();
			}
			script.onload = function () { finish(""); };
			script.onerror = function () { finish("LOAD_ERROR"); };
			script.src = old.getAttribute("src");
			template.parentNode.insertBefore(script, template);
		});
	}
	function activate(group) {
		if (group.failure || attempts[group.group_id] || !allowed(group.category)) return;
		attempts[group.group_id] = true;
		var prepared = Object.create(null);
		try {
			// Validate the whole group before executing any owned inline statement.
			group.nodes.forEach(function (node) { prepared[node.handle] = templateScripts(node); });
		} catch (error) {
			report(group.group_id, error.message);
			return;
		}
		var memo = Object.create(null);
		function run(node) {
			if (memo[node.handle]) return memo[node.handle];
			memo[node.handle] = Promise.all(node.deps.map(function (dependency) {
				return run(group.nodes.find(function (candidate) { return candidate.handle === dependency; }));
			})).then(function (results) {
				if (results.some(function (result) { return !result; })) {
					report(group.group_id, "SKIPPED_DEPENDENCY", node.handle);
					return false;
				}
				var captured = prepared[node.handle];
				return captured.scripts.reduce(function (chain, script) {
					return chain.then(function () { return replay(script, captured.template, group.category); });
				}, Promise.resolve()).then(function () { return true; }).catch(function (error) {
					report(group.group_id, error.message, node.handle);
					return false;
				});
			});
			return memo[node.handle];
		}
		Promise.all(group.nodes.map(run)).then(function (results) {
			report(group.group_id, results.every(Boolean) ? "ACTIVATED" : "FAILED");
		});
	}
	function consentChanged() {
		if (ready) groups.forEach(activate);
	}
	function initialize() {
		var manifest = document.getElementById("itd-cookies-script-groups");
		if (!manifest) return;
		var data;
		try { data = JSON.parse(manifest.textContent); } catch { return; }
		groups = data.groups;
		data.diagnostics.forEach(function (event) { report(event.group_id, event.code); });
		groups.forEach(function (group) {
			if (group.failure) report(group.group_id, group.failure);
		});
		ready = true;
		consentChanged();
	}
	window.addEventListener("itd_cookies_consent_changed", consentChanged);
	if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initialize, {once: true});
	else initialize();
}());
