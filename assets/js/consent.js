(function () {
	'use strict';

	var config = window.ITDCookiesConfig || {};
	var banner = document.querySelector('[data-itd-cookies-banner]');
	var summary = banner ? banner.querySelector('[data-itd-cookies-summary]') : null;
	var panel = banner ? banner.querySelector('[data-itd-cookies-panel]') : null;
	var previousFocus = null;
	var backdrop = document.querySelector('[data-itd-cookies-backdrop]');
	var state = null;
	var decision = null;
	var receipt = null;
	var categories = ['functional', 'analytics', 'marketing'];

	if (
		config.cookieName !== 'itd_cookies_consent' ||
		config.receiptCookieName !== 'itd_cookies_services' ||
		config.legacyCookieName !== 'itd_modubricks_consent' ||
		config.legacyMarkerName !== 'itd_cookies_legacy_migrated' ||
		(config.schema !== 1 && config.schema !== '1') ||
		typeof config.version !== 'string' ||
		!config.version ||
		!hasOnlyKeys(config.inventory, categories) ||
		categories.some(function (category) {
			return !Array.isArray(config.inventory[category]) || config.inventory[category].some(function (token) {
				return typeof token !== 'string' || !/^[a-f0-9]{32}$/.test(token);
			});
		})
	) {
		return;
	}
	// wp_localize_script() serializes top-level scalar values as strings.
	config.schema = 1;

	function cookieValue(name) {
		var prefix = name + '=';
		var parts = document.cookie ? document.cookie.split(';') : [];
		var i;
		var part;
		for (i = 0; i < parts.length; i += 1) {
			part = parts[i].replace(/^\s+/, '');
			if (part.indexOf(prefix) === 0) {
				return part.slice(prefix.length);
			}
		}
		return null;
	}

	function parseJsonCookie(value, limit) {
		if (typeof value !== 'string' || value.length > (limit || 2048)) {
			return null;
		}
		try {
			return JSON.parse(decodeURIComponent(value));
		} catch {
			return null;
		}
	}

	function hasOnlyKeys(value, keys) {
		return value && typeof value === 'object' && !Array.isArray(value) &&
			Object.keys(value).every(function (key) { return keys.indexOf(key) !== -1; });
	}

	function validateConsent(candidate) {
		var choice;
		var i;
		if (
			!hasOnlyKeys(candidate, ['schema', 'version', 'expiresAt', 'categories']) ||
			candidate.schema !== config.schema ||
			candidate.version !== config.version ||
			!Number.isSafeInteger(candidate.expiresAt) ||
			candidate.expiresAt <= Date.now() ||
			!hasOnlyKeys(candidate.categories, ['necessary', 'functional', 'analytics', 'marketing']) ||
			candidate.categories.necessary !== true
		) {
			return null;
		}
		choice = candidate.categories;
		for (i = 0; i < categories.length; i += 1) {
			if (typeof choice[categories[i]] !== 'boolean') {
				return null;
			}
		}
		return {
			necessary: true,
			functional: choice.functional,
			analytics: choice.analytics,
			marketing: choice.marketing,
		};
	}

	function lifetimeDays() {
		var days = parseInt(config.lifetimeDays, 10);
		return Number.isFinite(days) ? Math.max(1, Math.min(3650, days)) : 365;
	}

	function validateReceipt(candidate, primary) {
		if (!primary || !hasOnlyKeys(candidate, ['schema', 'version', 'expiresAt', 'services']) ||
			candidate.schema !== 1 || candidate.version !== primary.version || candidate.expiresAt !== primary.expiresAt ||
			typeof candidate.services !== 'string' || !/^(?:[a-f0-9]{32}){0,96}$/.test(candidate.services)) {
			return null;
		}
		return candidate.services.match(/[a-f0-9]{32}/g) || [];
	}

	function knownCategory(category) {
		return !!receipt && config.inventory[category].every(function (token) { return receipt.indexOf(token) !== -1; });
	}

	function needsChoice() {
		if (!state) { return true; }
		// An old Reject remains a Reject. A positive unbound decision cannot grant new services.
		return categories.some(function (category) { return state[category]; }) && categories.some(function (category) {
			return config.inventory[category].length && !knownCategory(category);
		});
	}

	function setCookie(name, value, seconds) {
		var attributes = [
			name + '=' + encodeURIComponent(value),
			'Max-Age=' + seconds,
			'Expires=' + new Date(Date.now() + seconds * 1000).toUTCString(),
			'Path=/',
			'SameSite=Lax',
		];
		if (window.location.protocol === 'https:') {
			attributes.push('Secure');
		}
		document.cookie = attributes.join('; ');
	}

	function markLegacyChecked() {
		setCookie(config.legacyMarkerName, '1', 3650 * 86400);
	}

	function writeConsent(choice) {
		var seconds = lifetimeDays() * 86400;
		var payload = {
			schema: config.schema,
			version: config.version,
			expiresAt: Date.now() + seconds * 1000,
			categories: {
				necessary: true,
				functional: choice.functional === true && config.inventory.functional.length > 0,
				analytics: choice.analytics === true && config.inventory.analytics.length > 0,
				marketing: choice.marketing === true && config.inventory.marketing.length > 0,
			},
		};
		var tokens = categories.some(function (category) { return payload.categories[category]; }) ?
			Array.from(new Set([].concat(config.inventory.functional, config.inventory.analytics, config.inventory.marketing))).sort() : [];
		var proof = { schema: 1, version: payload.version, expiresAt: payload.expiresAt, services: tokens.join('') };
		var encoded = JSON.stringify(proof);
		if (tokens.length > 96 || encodeURIComponent(encoded).length > 3800 || encodeURIComponent(JSON.stringify(payload)).length > 2048) {
			return false;
		}
		setCookie(config.receiptCookieName, encoded, seconds);
		setCookie(config.cookieName, JSON.stringify(payload), seconds);
		if (cookieValue(config.cookieName) !== encodeURIComponent(JSON.stringify(payload)) || cookieValue(config.receiptCookieName) !== encodeURIComponent(encoded)) {
			// A partial write cannot retain in-memory grants for a mismatched cookie pair.
			decision = parseJsonCookie(cookieValue(config.cookieName));
			state = validateConsent(decision);
			receipt = validateReceipt(parseJsonCookie(cookieValue(config.receiptCookieName), 3800), decision);
			return false;
		}
		markLegacyChecked();
		decision = payload;
		receipt = tokens;
		state = payload.categories;
		return true;
	}

	function migrateLegacyCookie() {
		var raw = cookieValue(config.legacyCookieName);
		if (cookieValue(config.cookieName) !== null || cookieValue(config.legacyMarkerName) !== null) {
			return null;
		}
		if (raw === null) {
			return null;
		}
		markLegacyChecked();
		// Legacy category flags have no service identities. Ask for an explicit bound choice.
		return null;
	}

	function allowed(category) {
		if (category === 'necessary') {
			return true;
		}
		return categories.indexOf(category) !== -1 && !!(state && state[category] === true) &&
			config.inventory[category].length > 0 && knownCategory(category);
	}

	function focusElement(element) {
		if (!element || typeof element.focus !== 'function') {
			return;
		}
		try {
			element.focus({ preventScroll: true });
		} catch {
			element.focus();
		}
	}

	function showBanner() {
		if (!banner || !summary) {
			return;
		}
		banner.hidden = false;
		banner.setAttribute('aria-hidden', 'false');
		banner.setAttribute('aria-labelledby', 'itd-cookies-title');
		banner.setAttribute('aria-describedby', 'itd-cookies-description');
		banner.setAttribute('aria-modal', 'false');
		banner.classList.remove('itd-cookies--settings');
		if (backdrop) { backdrop.hidden = true; }
		summary.hidden = false;
		if (panel) {
			panel.hidden = true;
		}
		focusElement(banner.querySelector('[data-itd-cookies-accept]'));
	}

	function hideBanner() {
		if (!banner) {
			return;
		}
		banner.hidden = true;
		banner.setAttribute('aria-hidden', 'true');
		if (backdrop) { backdrop.hidden = true; }
		if (previousFocus && document.contains(previousFocus)) {
			focusElement(previousFocus);
		}
		previousFocus = null;
	}

	function openSettings() {
		var i;
		var input;
		if (!banner || !summary || !panel) {
			return false;
		}
		previousFocus = document.activeElement;
		banner.hidden = false;
		banner.setAttribute('aria-hidden', 'false');
		banner.setAttribute('aria-labelledby', 'itd-cookies-settings-title');
		banner.setAttribute('aria-describedby', 'itd-cookies-settings-description');
		banner.setAttribute('aria-modal', 'true');
		banner.classList.add('itd-cookies--settings');
		banner.scrollTop = 0;
		var choices = panel.querySelector('.itd-cookies__choices');
		if (choices) { choices.scrollTop = 0; }
		if (backdrop) { backdrop.hidden = false; }
		summary.hidden = true;
		panel.hidden = false;
		for (i = 0; i < categories.length; i += 1) {
			input = panel.querySelector('[data-itd-cookies-category="' + categories[i] + '"]');
			if (input) {
				input.checked = allowed(categories[i]);
			}
		}
		focusElement(panel.querySelector('#itd-cookies-settings-title'));
		return true;
	}

	function eventChanged(choice, source) {
		var detail = { categories: Object.assign({}, choice), source: source };
		if (typeof window.CustomEvent === 'function') {
			window.dispatchEvent(new window.CustomEvent('itd_cookies_consent_changed', { detail: detail }));
		}
	}

	function loadProviders() {
		if (window.ITDCookiesProviderLoader) { window.ITDCookiesProviderLoader.load(config.providers, allowed); }
	}

	function saveChoice(choice, source) {
		var revoked = categories.filter(allowed);
		var error = banner ? banner.querySelector('[data-itd-cookies-error]') : null;
		if (!writeConsent(choice)) {
			if (error) { error.textContent = config.saveError; error.hidden = false; }
			if (revoked.some(function (category) { return !allowed(category); })) { window.location.reload(); }
			return;
		}
		if (error) { error.hidden = true; }
		hideBanner();
		eventChanged(state, source);
		if (revoked.some(function (category) { return !allowed(category); })) {
			// Third-party JavaScript cannot be reliably unloaded from an active document.
			window.location.reload();
			return;
		}
		loadProviders();
	}

	function button(selector, callback) {
		var elements = banner ? banner.querySelectorAll(selector) : [];
		Array.prototype.forEach.call(elements, function (element) {
			element.addEventListener('click', callback);
		});
	}

	button('[data-itd-cookies-accept]', function () {
		saveChoice({ functional: true, analytics: true, marketing: true }, 'accept-all');
	});
	button('[data-itd-cookies-reject]', function () {
		saveChoice({ functional: false, analytics: false, marketing: false }, 'reject');
	});
	button('[data-itd-cookies-customize]', openSettings);
	button('[data-itd-cookies-save]', function () {
		var choice = {};
		categories.forEach(function (category) {
			var input = panel.querySelector('[data-itd-cookies-category="' + category + '"]');
			choice[category] = !!(input && input.checked);
		});
		saveChoice(choice, 'custom');
	});
	button('[data-itd-cookies-cancel]', function () {
		if (state) {
			hideBanner();
		} else {
			showBanner();
		}
	});

	// A reopened panel can close without saving. An initial choice stays pending.
	document.addEventListener('keydown', function (event) {
		if (!banner || banner.hidden || !panel || panel.hidden) { return; }
		if (event.key === 'Escape') {
			event.preventDefault();
			if (state) { hideBanner(); } else { showBanner(); }
			return;
		}
		if (event.key !== 'Tab') { return; }
		var controls = panel.querySelectorAll('button:not(:disabled), input:not(:disabled), a[href]');
		var first = controls[0];
		var last = controls[controls.length - 1];
		if (event.shiftKey && (document.activeElement === first || document.activeElement === panel.querySelector('#itd-cookies-settings-title'))) {
			event.preventDefault(); focusElement(last);
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault(); focusElement(first);
		}
	});

	document.addEventListener('click', function (event) {
		if (event.target && event.target.closest('[data-itd-cookies-open]')) {
			openSettings();
		}
	});

	decision = parseJsonCookie(cookieValue(config.cookieName));
	state = validateConsent(decision);
	receipt = validateReceipt(parseJsonCookie(cookieValue(config.receiptCookieName), 3800), decision);
	if (!state) {
		state = migrateLegacyCookie();
	}
	window.ITDCookies = { allowed: allowed, openSettings: openSettings };
	loadProviders();
	if (needsChoice()) {
		showBanner();
	}
})();
