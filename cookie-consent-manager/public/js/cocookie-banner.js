/**
 * CoCookie Banner — behavior only.
 *
 * The banner HTML is server-rendered via PHP template.
 * This JS handles: show/hide, toggles, consent save,
 * GCM updates, script activation, and cookie interceptor.
 *
 * @package CoCookie
 */
(function () {
	'use strict';

	var config = window.cocookieConfig;
	if (!config) return;

	var COOKIE_NAME = 'cc_consent';
	var isReopen = false;

	// --- Elements ---
	var banner       = document.getElementById('cocookie-banner');
	var floatBtn     = document.getElementById('cocookie-float-btn');
	var acceptBtn    = document.getElementById('cocookie-accept-all');
	var rejectBtn    = document.getElementById('cocookie-reject-all');
	var saveBtn      = document.getElementById('cocookie-save');
	var settingsBtn  = document.getElementById('cocookie-toggle-details');
	var withdrawBtn  = document.getElementById('cocookie-withdraw');
	var details      = document.getElementById('cocookie-details');
	var consentInfo  = document.getElementById('cocookie-consent-info');
	var dntNotice    = document.getElementById('cocookie-dnt-notice');
	var bannerTitle  = document.getElementById('cocookie-banner-title');
	var bannerText   = document.getElementById('cocookie-banner-text');

	if (!banner) return;

	// --- Google Consent Mode v2 ---
	function gtagUpdate(consentData) {
		if (typeof gtag !== 'function') {
			window.dataLayer = window.dataLayer || [];
			window.gtag = function () { dataLayer.push(arguments); };
		}
		var m = !!consentData.marketing;
		var a = !!consentData.analytics;
		gtag('consent', 'update', {
			ad_storage:              m ? 'granted' : 'denied',
			ad_user_data:            m ? 'granted' : 'denied',
			ad_personalization:      m ? 'granted' : 'denied',
			analytics_storage:       a ? 'granted' : 'denied',
			functionality_storage:   'granted',
			personalization_storage: a ? 'granted' : 'denied',
			security_storage:        'granted'
		});
		gtag('set', 'ads_data_redaction', !m);
	}

	// --- DNT ---
	function isDNT() {
		return navigator.doNotTrack === '1' || window.doNotTrack === '1';
	}

	// --- Cookie helpers ---
	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
		return match ? decodeURIComponent(match[2]) : null;
	}

	function setCookie(name, value, days) {
		if (days > 395) days = 395;
		var d = new Date();
		d.setTime(d.getTime() + days * 86400000);
		var c = name + '=' + encodeURIComponent(value) + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
		if (location.protocol === 'https:') c += ';Secure';
		document.cookie = c;
	}

	function getConsent() {
		var raw = getCookie(COOKIE_NAME);
		if (!raw) return null;
		try { return JSON.parse(raw); } catch (e) { return null; }
	}

	function isExpired(consent) {
		if (!consent || !consent.timestamp) return false;
		var lifetime = (config.settings.cookie_lifetime || 365) * 86400000;
		return (Date.now() - consent.timestamp) > lifetime;
	}

	// --- Script/iframe activation ---
	function activateScripts(cats) {
		var scripts = document.querySelectorAll('script[type="text/plain"][data-cc-category]');
		for (var i = 0; i < scripts.length; i++) {
			var cat = scripts[i].getAttribute('data-cc-category');
			if (cats[cat]) {
				var s = document.createElement('script');
				for (var j = 0; j < scripts[i].attributes.length; j++) {
					var attr = scripts[i].attributes[j];
					if (attr.name !== 'type') s.setAttribute(attr.name, attr.value);
				}
				s.type = 'text/javascript';
				if (!scripts[i].src) s.textContent = scripts[i].textContent;
				scripts[i].parentNode.replaceChild(s, scripts[i]);
			}
		}
	}

	function activateIframes(cats) {
		// Metod 1: Neutraliserade iframes (nya metoden — iframe finns kvar med src="about:blank")
		var neutralized = document.querySelectorAll('iframe[data-cc-category][data-cc-src]');
		for (var i = 0; i < neutralized.length; i++) {
			var cat = neutralized[i].getAttribute('data-cc-category');
			if (cats[cat]) {
				var realSrc = neutralized[i].getAttribute('data-cc-src');
				if (realSrc) {
					// Sätt tillbaka både src och data-src (lazy load)
					neutralized[i].src = realSrc;
					if (neutralized[i].hasAttribute('data-src')) {
						neutralized[i].setAttribute('data-src', realSrc);
					}
					neutralized[i].removeAttribute('data-cc-src');
					neutralized[i].removeAttribute('data-cc-category');
				}
				// Ta bort overlay-placeholder om den finns
				var wrapper = neutralized[i].parentNode;
				var overlay = wrapper ? wrapper.querySelector('.ccm-iframe-placeholder, .cocookie-iframe-placeholder') : null;
				if (overlay) overlay.parentNode.removeChild(overlay);
			}
		}

		// Metod 2: Gamla placeholder-divs (bakåtkompatibilitet)
		var placeholders = document.querySelectorAll('.cocookie-iframe-placeholder[data-cc-category], .ccm-iframe-placeholder[data-cc-category]');
		for (var j = 0; j < placeholders.length; j++) {
			var pCat = placeholders[j].getAttribute('data-cc-category');
			if (cats[pCat]) {
				var pSrc = placeholders[j].getAttribute('data-cc-src');
				if (!pSrc) continue;
				var iframe = document.createElement('iframe');
				iframe.src = pSrc;
				iframe.setAttribute('frameborder', '0');
				iframe.setAttribute('allowfullscreen', '');
				var ps = placeholders[j].style;
				if (ps.width) iframe.style.width = ps.width;
				if (ps.height) iframe.style.height = ps.height;
				placeholders[j].parentNode.replaceChild(iframe, placeholders[j]);
			}
		}
	}

	// --- Show/hide banner ---
	function showBanner(existingConsent) {
		isReopen = !!existingConsent;
		// Växla med visibility/opacity istället för display för att undvika CLS
		banner.style.visibility = 'visible';
		banner.style.opacity = '1';
		banner.style.pointerEvents = 'auto';

		if (isReopen && existingConsent) {
			// Update title/text for manage mode
			if (bannerTitle) bannerTitle.textContent = config.settings.manage_title;
			if (bannerText) bannerText.textContent = config.settings.manage_text;

			// Show consent info
			if (consentInfo) {
				consentInfo.style.display = '';
				if (existingConsent.timestamp) {
					var d = new Date(existingConsent.timestamp);
					var locale = document.documentElement.lang || 'sv';
					document.getElementById('cocookie-consent-date').textContent =
						d.toLocaleDateString(locale) + ' ' + d.toLocaleTimeString(locale);
				}
				if (existingConsent.uuid) {
					document.getElementById('cocookie-consent-id').textContent = existingConsent.uuid;
				}
			}

			// Show details and withdraw
			if (details) details.style.display = '';
			if (saveBtn) saveBtn.style.display = '';
			if (withdrawBtn) withdrawBtn.style.display = '';
			if (settingsBtn) settingsBtn.style.display = 'none';

			// Restore checkbox states
			config.categories.forEach(function (cat) {
				var cb = banner.querySelector('[data-cocookie-category="' + cat.slug + '"]');
				if (cb && !cat.is_required) {
					cb.checked = !!existingConsent[cat.slug];
				}
			});
		}

		if (isDNT() && !isReopen && dntNotice) {
			dntNotice.style.display = '';
		}
	}

	function hideBanner() {
		// Växla med visibility/opacity istället för display för att undvika CLS
		banner.style.visibility = 'hidden';
		banner.style.opacity = '0';
		banner.style.pointerEvents = 'none';
		// Reset to initial state
		if (consentInfo) consentInfo.style.display = 'none';
		if (details) details.style.display = 'none';
		if (saveBtn) saveBtn.style.display = 'none';
		if (withdrawBtn) withdrawBtn.style.display = 'none';
		if (settingsBtn) settingsBtn.style.display = '';
		if (floatBtn) floatBtn.style.display = '';
	}

	// --- Save consent ---
	function postConsent(data, nonce) {
		return fetch(config.restUrl + '/consent', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			body: JSON.stringify({ categories: data })
		}).then(function (r) { return r.json(); });
	}

	function saveConsent(categories) {
		var data = {};
		config.categories.forEach(function (cat) {
			data[cat.slug] = cat.is_required ? true : !!categories[cat.slug];
		});

		if (isDNT()) {
			config.categories.forEach(function (cat) {
				if (!cat.is_required) data[cat.slug] = false;
			});
		}

		gtagUpdate(data);

		if (typeof window.__ccmUpdateConsent === 'function') {
			window.__ccmUpdateConsent(data);
		}

		// Sätt cookien direkt så samtycket sparas även om REST-anropet misslyckas
		// (t.ex. pga cachad nonce). UUID uppdateras i efterhand om anropet lyckas.
		var timestamp = Date.now();
		var cookieVal = JSON.stringify(Object.assign({}, data, { timestamp: timestamp }));
		setCookie(COOKIE_NAME, cookieVal, config.settings.cookie_lifetime);

		postConsent(data, config.nonce)
			.then(function (resp) {
				// 403 = utgången nonce (vanligt med full page cache). Hämta en
				// färsk nonce från /config och gör om anropet en gång, annars
				// tappas samtyckesloggen tyst.
				if (resp && resp.code === 'invalid_nonce') {
					return fetch(config.restUrl + '/config', { credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (cfg) {
							if (!cfg || !cfg.nonce) return resp;
							config.nonce = cfg.nonce;
							return postConsent(data, cfg.nonce);
						});
				}
				return resp;
			})
			.then(function (resp) {
				if (resp && resp.uuid) {
					var val = JSON.stringify(Object.assign({}, data, { uuid: resp.uuid, timestamp: timestamp }));
					setCookie(COOKIE_NAME, val, config.settings.cookie_lifetime);
				} else {
					// Cookien är satt lokalt, men serverloggen saknar posten.
					console.warn('CoCookie: samtycket kunde inte loggas på servern.', resp);
				}
				if (isReopen) {
					window.location.reload();
				}
			})
			.catch(function (err) {
				// Nätverksfel — cookien är redan satt ovan, men inget loggades.
				console.warn('CoCookie: samtycket kunde inte loggas på servern.', err);
				if (isReopen) {
					window.location.reload();
				}
			});

		if (!isReopen) {
			activateScripts(data);
			activateIframes(data);
		}
		hideBanner();
	}

	// --- Init ---
	// The banner template is rendered in wp_footer with priority 100,
	// but this script may execute before that. We wait for the banner
	// element to exist before initializing.
	function init() {
		// Re-query elements since they may not have existed at script parse time
		banner      = document.getElementById('cocookie-banner');
		floatBtn    = document.getElementById('cocookie-float-btn');
		acceptBtn   = document.getElementById('cocookie-accept-all');
		rejectBtn   = document.getElementById('cocookie-reject-all');
		saveBtn     = document.getElementById('cocookie-save');
		settingsBtn = document.getElementById('cocookie-toggle-details');
		withdrawBtn = document.getElementById('cocookie-withdraw');
		details     = document.getElementById('cocookie-details');
		consentInfo = document.getElementById('cocookie-consent-info');
		dntNotice   = document.getElementById('cocookie-dnt-notice');
		bannerTitle = document.getElementById('cocookie-banner-title');
		bannerText  = document.getElementById('cocookie-banner-text');

		if (!banner) {
			// console.log('[CoCookie] init() called but banner still not found');
			return;
		}

		// console.log('[CoCookie] init() running, consent:', getConsent());

		// Bind event listeners (safe to call multiple times — we only bind once)
		bindEvents();

		var consent = getConsent();
		// console.log('[CoCookie] consent check:', consent, 'expired:', consent ? isExpired(consent) : 'N/A');

		if (consent && !isExpired(consent)) {
			if (isDNT()) {
				config.categories.forEach(function (c) {
					if (!c.is_required) consent[c.slug] = false;
				});
			}
			activateScripts(consent);
			gtagUpdate(consent);
			activateIframes(consent);
			if (floatBtn) floatBtn.style.display = '';
			return;
		}

		// No consent or expired — show banner
		// console.log('[CoCookie] No consent found, showing banner');
		showBanner();
	}

	var eventsBound = false;
	function bindEvents() {
		if (eventsBound) return;
		eventsBound = true;

		if (acceptBtn) {
			acceptBtn.addEventListener('click', function () {
				var all = {};
				config.categories.forEach(function (c) { all[c.slug] = true; });
				saveConsent(all);
			});
		}

		if (rejectBtn) {
			rejectBtn.addEventListener('click', function () {
				var req = {};
				config.categories.forEach(function (c) { req[c.slug] = c.is_required; });
				saveConsent(req);
			});
		}

		if (withdrawBtn) {
			withdrawBtn.addEventListener('click', function () {
				var req = {};
				config.categories.forEach(function (c) { req[c.slug] = c.is_required; });
				saveConsent(req);
			});
		}

		if (saveBtn) {
			saveBtn.addEventListener('click', function () {
				var sel = {};
				config.categories.forEach(function (c) {
					var cb = banner.querySelector('[data-cocookie-category="' + c.slug + '"]');
					sel[c.slug] = cb ? cb.checked : c.is_required;
				});
				saveConsent(sel);
			});
		}

		// Tab navigation
		var tabs = banner.querySelectorAll('[data-cocookie-tab]');
		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				var target = tab.getAttribute('data-cocookie-tab');
				// Deactivate all tabs and panels
				tabs.forEach(function (t) { t.classList.remove('cocookie-banner__tab--active'); });
				var panels = banner.querySelectorAll('[data-cocookie-panel]');
				panels.forEach(function (p) { p.style.display = 'none'; });
				// Activate selected
				tab.classList.add('cocookie-banner__tab--active');
				var panel = banner.querySelector('[data-cocookie-panel="' + target + '"]');
				if (panel) panel.style.display = '';
				// Show save button when on details tab
				if (saveBtn) {
					saveBtn.style.display = (target === 'details') ? '' : 'none';
				}
			});
		});

		// Legacy settings button (kept for backward compat)
		if (settingsBtn) {
			settingsBtn.addEventListener('click', function () {
				// Switch to details tab
				var detailsTab = banner.querySelector('[data-cocookie-tab="details"]');
				if (detailsTab) detailsTab.click();
			});
		}

		if (floatBtn) {
			floatBtn.addEventListener('click', function () {
				if (banner.style.visibility !== 'hidden') return;
				showBanner(getConsent());
			});
		}

		// Category accordion toggles
		banner.addEventListener('click', function (e) {
			var expandBtn = e.target.closest('.cocookie-category__expand');
			if (!expandBtn) return;
			var category = expandBtn.closest('.cocookie-category');
			var body = category.querySelector('.cocookie-category__body');
			var arrow = expandBtn.querySelector('.cocookie-category__arrow');
			var open = body.style.display !== 'none';
			body.style.display = open ? 'none' : '';
			expandBtn.setAttribute('aria-expanded', !open);
			if (arrow) arrow.innerHTML = open ? '&#9654;' : '&#9660;';
		});

		// Iframe placeholder accept buttons
		document.addEventListener('click', function (e) {
			if (!e.target.classList.contains('cocookie-iframe-placeholder__btn') && !e.target.classList.contains('ccm-iframe-accept')) return;
			e.preventDefault();
			if (banner.style.visibility !== 'hidden') return;
			showBanner(getConsent());
		});
	}

	// Try init immediately, then retry after DOM is ready
	// console.log('[CoCookie] Script loaded, looking for banner...');
	if (document.getElementById('cocookie-banner')) {
		// console.log('[CoCookie] Banner found immediately, initializing');
		init();
	} else {
		// console.log('[CoCookie] Banner not in DOM yet, waiting...');
		// Banner not in DOM yet — wait for it
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', init);
		} else {
			// DOM already loaded but banner not found — use MutationObserver
			var observer = new MutationObserver(function (mutations, obs) {
				if (document.getElementById('cocookie-banner')) {
					obs.disconnect();
					init();
				}
			});
			observer.observe(document.body || document.documentElement, { childList: true, subtree: true });
			// Fallback timeout
			setTimeout(function () { observer.disconnect(); init(); }, 2000);
		}
	}
})();
