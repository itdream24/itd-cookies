/* Native WordPress Color Picker; no frontend behavior or consent state. */
(function ($) {
	"use strict";
	$(function () {
		var editor = document.getElementById("itd-cookies-footer-editor");
		if (!editor) return;
		var preview = editor.querySelector(".itd-cookies-footer-preview");
		var fields = Array.from(editor.querySelectorAll(".itd-cookies-color"));
		var alignment = document.getElementById("itd-cookies-footer-alignment");
		function valid(value) { return value === "" || /^#[0-9a-f]{6}$/i.test(value); }
		function luminance(color) {
			var channels = color.match(/\d+(?:\.\d+)?/g).slice(0, 3).map(function (value) {
				var channel = Number(value) / 255;
				return channel <= 0.04045 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
			});
			return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
		}
		function update() {
			var invalid = false;
			fields.forEach(function (field) {
				var value = field.value.trim();
				invalid = invalid || !valid(value);
				field.setAttribute("aria-invalid", String(!valid(value)));
				preview.style[field.dataset.colorRole === "background" ? "backgroundColor" : "color"] = valid(value) ? value : "";
			});
			preview.style.justifyContent = { left: "flex-start", center: "center", right: "flex-end" }[alignment.value] || "center";
			var styles = window.getComputedStyle(preview);
			var background = luminance(styles.backgroundColor);
			var foreground = luminance(styles.color);
			var ratio = (Math.max(background, foreground) + 0.05) / (Math.min(background, foreground) + 0.05);
			preview.dataset.contrast = ratio.toFixed(2);
			editor.querySelector(".itd-cookies-color-invalid").hidden = !invalid;
			editor.querySelector(".itd-cookies-color-warning").hidden = invalid || ratio >= 4.5;
		}
		fields.forEach(function (field) {
			$(field).wpColorPicker({ change: function () { window.setTimeout(update, 0); }, clear: function () { window.setTimeout(update, 0); } });
			field.addEventListener("input", update);
			field.addEventListener("change", update);
		});
		editor.querySelectorAll("[data-reset-color]").forEach(function (button) {
			button.addEventListener("click", function () {
				var field = document.getElementById(button.dataset.resetColor);
				field.value = "";
				field.dispatchEvent(new Event("change", { bubbles: true }));
				update();
			});
		});
		alignment.addEventListener("change", update);
		update();
	});
})(window.jQuery);
