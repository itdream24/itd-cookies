import test from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";
import { JSDOM } from "jsdom";

test("footer preview validates colors, warns on low contrast and resets independently", () => {
	const dom = new JSDOM(`<style>${fs.readFileSync(new URL("../../assets/css/footer-editor.css", import.meta.url), "utf8")}</style>
	<select id="itd-cookies-footer-alignment"><option>center</option><option>right</option></select>
	<div id="itd-cookies-footer-editor"><input id="background" class="itd-cookies-color" data-color-role="background"><input id="text" class="itd-cookies-color" data-color-role="text">
	<button data-reset-color="background"></button><button data-reset-color="text"></button><div class="itd-cookies-footer-preview"></div><p class="itd-cookies-color-invalid" hidden></p><p class="itd-cookies-color-warning" hidden></p></div>`, { runScripts: "outside-only" });
	const { window } = dom;
	const pickers = [];
	window.jQuery = (element) => {
		if (typeof element === "function") { element(); return; }
		return { wpColorPicker: (command, value) => {
			if (typeof command === "object") pickers.push(element);
			else element.value = value;
		} };
	};
	window.eval(fs.readFileSync(new URL("../../assets/js/footer-editor.js", import.meta.url), "utf8"));
	assert.equal(pickers.length, 2);
	const q = (selector) => window.document.querySelector(selector);
	const input = (id, value) => { q(id).value = value; q(id).dispatchEvent(new window.Event("input")); };
	input("#background", "#181C1E"); input("#text", "#ffffff");
	assert.ok(Number(q(".itd-cookies-footer-preview").dataset.contrast) > 17);
	assert.equal(q(".itd-cookies-color-warning").hidden, true);
	input("#text", "#181c1e");
	assert.equal(q(".itd-cookies-color-warning").hidden, false);
	input("#text", "url(javascript:alert(1))");
	assert.equal(q("#text").getAttribute("aria-invalid"), "true");
	assert.equal(q(".itd-cookies-color-invalid").hidden, false);
	input("#text", "#ffffff");
	q('[data-reset-color="background"]').click();
	assert.equal(q("#background").value, "");
	assert.equal(q("#text").value, "#ffffff");
	assert.equal(q(".itd-cookies-color-warning").hidden, false);
	q('[data-reset-color="text"]').click();
	assert.equal(q(".itd-cookies-color-warning").hidden, true);
	q("select").value = "right"; q("select").dispatchEvent(new window.Event("change"));
	assert.equal(q(".itd-cookies-footer-preview").style.justifyContent, "flex-end");
	dom.window.close();
});
