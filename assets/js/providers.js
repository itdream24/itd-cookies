(function () {
	'use strict';
	if (window.ITDCookiesProviderLoader) { return; }
	var loaded = {};
	var attempted = {};
	var initialized = {};
	var clarityConsent = '';
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
		return true;
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
		return true;
	}


	function dataLayer() {
		if (typeof window.dataLayer === 'undefined') { window.dataLayer = []; }
		return Array.isArray(window.dataLayer);
	}

	function appendScript(url, type, category) {
		var script = document.createElement('script');
		script.async = true;
		script.src = url;
		script.setAttribute('data-itd-cookies-provider', type);
		script.setAttribute('data-itd-cookies-provider-category', category);
		(document.head || document.documentElement).appendChild(script);
	}

	function loadGtm(provider) {
		var url = 'https://www.googletagmanager.com/gtm.js';
		if (hasExistingLoader(url) || !dataLayer()) { return false; }
		window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
		appendScript(url + '?id=' + encodeURIComponent(provider.id), 'gtm', 'analytics');
		return true;
	}

	function loadClarity(provider) {
		var url = 'https://www.clarity.ms/tag/' + encodeURIComponent(provider.id);
		if (hasExistingLoader(url)) { return false; }
		if (typeof window.clarity === 'undefined') {
			window.clarity = function () { (window.clarity.q = window.clarity.q || []).push(arguments); };
		}
		if (typeof window.clarity !== 'function') { return false; }
		appendScript(url, 'clarity', 'analytics');
		return true;
	}

	function syncClarity(provider, allowed) {
		var signal = allowed('marketing') ? 'granted' : 'denied';
		if (clarityConsent === signal) { return; }
		clarityConsent = signal;
		window.clarity('consentv2', { analytics_Storage: 'granted', ad_Storage: signal });
	}

	function loadMeta(provider) {
		var url = 'https://connect.facebook.net/en_US/fbevents.js';
		if (hasExistingLoader(url)) { return false; }
		if (typeof window.fbq === 'undefined') {
			window.fbq = function () {
				if (window.fbq.callMethod) { window.fbq.callMethod.apply(window.fbq, arguments); }
				else { window.fbq.queue.push(arguments); }
			};
			window.fbq.queue = [];
			window.fbq.push = window.fbq;
			window.fbq.loaded = true;
			window.fbq.version = '2.0';
			if (typeof window._fbq === 'undefined') { window._fbq = window.fbq; }
		}
		if (typeof window.fbq !== 'function') { return false; }
		appendScript(url, 'meta', 'marketing');
		window.fbq('init', provider.id);
		window.fbq('trackSingle', provider.id, 'PageView');
		return true;
	}

	// Native adapters are a closed map; registry descriptions cannot add URLs/code.
	var adapters = {
		yandex: { category: 'analytics', valid: validYandexId, load: loadYandex },
		ga4: { category: 'analytics', valid: validGa4Id, load: loadGa4 },
		gtm: { category: 'analytics', valid: function (id) { return typeof id === 'string' && /^GTM-[A-Z0-9]{4,32}$/.test(id); }, load: loadGtm },
		clarity: { category: 'analytics', valid: function (id) { return typeof id === 'string' && /^[a-z0-9]{1,64}$/.test(id); }, load: loadClarity, sync: syncClarity },
		meta: { category: 'marketing', valid: function (id) { return typeof id === 'string' && /^[1-9][0-9]{0,19}$/.test(id); }, load: loadMeta },
	};

	function load(providers, allowed) {
		if (!Array.isArray(providers) || typeof allowed !== 'function') { return; }
		providers.forEach(function (provider) {
			if (!provider || !Object.prototype.hasOwnProperty.call(adapters, provider.type)) { return; }
			var adapter = adapters[provider.type];
			if ((provider.category && provider.category !== adapter.category) || !adapter.valid(provider.id) || !allowed(adapter.category)) { return; }
			if (!attempted[provider.type]) {
				attempted[provider.type] = true;
				initialized[provider.type] = adapter.load(provider) === true;
			}
			if (initialized[provider.type] && adapter.sync) { adapter.sync(provider, allowed); }
		});
	}
	window.ITDCookiesProviderLoader = { load: load };
})();
