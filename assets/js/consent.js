(function () {
	'use strict';

	var config = window.ITDCookiesConfig || {};
	var banner = document.querySelector('[data-itd-cookies-banner]');
	var summary = banner ? banner.querySelector('[data-itd-cookies-summary]') : null;
	var panel = banner ? banner.querySelector('[data-itd-cookies-panel]') : null;
	var previousFocus = null;
	var state = null;
	var loaded = {};
	var categories = ['functional', 'analytics', 'marketing'];

	if (
		config.cookieName !== 'itd_cookies_consent' ||
		config.legacyCookieName !== 'itd_modubricks_consent' ||
		config.legacyMarkerName !== 'itd_cookies_legacy_migrated' ||
		(config.schema !== 1 && config.schema !== '1') ||
		typeof config.version !== 'string' ||
		!config.version
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

	function parseJsonCookie(value) {
		if (typeof value !== 'string' || value.length > 2048) {
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
				functional: choice.functional === true,
				analytics: choice.analytics === true,
				marketing: choice.marketing === true,
			},
		};
		setCookie(config.cookieName, JSON.stringify(payload), seconds);
		markLegacyChecked();
		state = payload.categories;
	}

	function migrateLegacyCookie() {
		var raw = cookieValue(config.legacyCookieName);
		var old;
		if (cookieValue(config.cookieName) !== null || cookieValue(config.legacyMarkerName) !== null) {
			return null;
		}
		if (raw === null) {
			return null;
		}
		old = parseJsonCookie(raw);
		markLegacyChecked();
		if (
			!hasOnlyKeys(old, ['choice', 'version', 'updatedAt']) ||
			old.choice !== 'accepted' ||
			old.version !== config.version
		) {
			return null;
		}
		writeConsent({ functional: false, analytics: true, marketing: false });
		return state;
	}

	function allowed(category) {
		if (category === 'necessary') {
			return true;
		}
		return categories.indexOf(category) !== -1 && !!(state && state[category] === true);
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

	function validYandexId(id) {
		return typeof id === 'string' && /^[1-9][0-9]{0,14}$/.test(id);
	}

	function validGa4Id(id) {
		return typeof id === 'string' && /^G-[A-Z0-9]{4,32}$/.test(id);
	}

	function hasExistingLoader(prefix) {
		var scripts = document.getElementsByTagName('script');
		var i;
		var src;
		for (i = 0; i < scripts.length; i += 1) {
			src = scripts[i].getAttribute('src') || '';
			if (src === prefix || src.indexOf(prefix + '?') === 0) {
				return true;
			}
		}
		return false;
	}

	function loadYandex(provider) {
		var key = 'yandex:' + provider.id;
		var url = 'https://mc.yandex.ru/metrika/tag.js';
		var script;
		if (!validYandexId(provider.id) || loaded[key] || hasExistingLoader(url)) {
			return;
		}
		loaded[key] = true;
		if (typeof window.ym === 'undefined') {
			window.ym = function () {
				(window.ym.a = window.ym.a || []).push(arguments);
			};
			window.ym.l = Date.now();
		}
		if (typeof window.ym !== 'function') {
			return;
		}
		script = document.createElement('script');
		script.async = true;
		script.src = url + '?id=' + encodeURIComponent(provider.id);
		script.setAttribute('data-itd-cookies-analytics', 'yandex');
		(document.head || document.documentElement).appendChild(script);
		window.ym(provider.id, 'init', provider.options && typeof provider.options === 'object' ? provider.options : {});
	}

	function loadGa4(provider) {
		var key = 'ga4:' + provider.id;
		var url = 'https://www.googletagmanager.com/gtag/js';
		var script;
		if (!validGa4Id(provider.id) || loaded[key] || hasExistingLoader(url)) {
			return;
		}
		loaded[key] = true;
		if (!Array.isArray(window.dataLayer)) {
			if (typeof window.dataLayer !== 'undefined') {
				return;
			}
			window.dataLayer = [];
		}
		if (typeof window.gtag === 'undefined') {
			window.gtag = function () {
				window.dataLayer.push(arguments);
			};
		}
		if (typeof window.gtag !== 'function') {
			return;
		}
		script = document.createElement('script');
		script.async = true;
		script.src = url + '?id=' + encodeURIComponent(provider.id);
		script.setAttribute('data-itd-cookies-analytics', 'ga4');
		(document.head || document.documentElement).appendChild(script);
		window.gtag('js', new Date());
		window.gtag('config', provider.id);
	}

	function loadAnalytics() {
		var providers = Array.isArray(config.providers) ? config.providers : [];
		if (!allowed('analytics')) {
			return;
		}
		providers.forEach(function (provider) {
			if (!provider || typeof provider !== 'object') {
				return;
			}
			if (provider.type === 'yandex') {
				loadYandex(provider);
			} else if (provider.type === 'ga4') {
				loadGa4(provider);
			}
		});
	}

	function saveChoice(choice, source) {
		var wasAnalyticsAllowed = allowed('analytics');
		writeConsent(choice);
		hideBanner();
		eventChanged(state, source);
		if (wasAnalyticsAllowed && !allowed('analytics')) {
			// Yandex and GA4 cannot be reliably unloaded from an active document.
			window.location.reload();
			return;
		}
		loadAnalytics();
	}

	function button(selector, callback) {
		var element = banner ? banner.querySelector(selector) : null;
		if (element) {
			element.addEventListener('click', callback);
		}
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

	document.addEventListener('click', function (event) {
		if (event.target && event.target.closest('[data-itd-cookies-open]')) {
			openSettings();
		}
	});

	state = validateConsent(parseJsonCookie(cookieValue(config.cookieName)));
	if (!state) {
		state = migrateLegacyCookie();
	}
	window.ITDCookies = { allowed: allowed, openSettings: openSettings };
	if (state) {
		loadAnalytics();
	} else {
		showBanner();
	}
})();
