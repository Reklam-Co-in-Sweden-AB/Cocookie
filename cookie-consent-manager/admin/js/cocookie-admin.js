/**
 * CoCookie Admin — shared safe DOM helpers.
 *
 * All admin JS should use these instead of innerHTML
 * to prevent XSS vulnerabilities.
 *
 * @package CoCookie
 */
(function () {
	'use strict';

	window.CoCookieAdmin = {

		/**
		 * Safely set text content on an element.
		 *
		 * @param {HTMLElement} el  Target element.
		 * @param {string}      text Text to set.
		 */
		setText: function (el, text) {
			if (el) {
				el.textContent = text;
			}
		},

		/**
		 * Create an element with optional attributes and text.
		 *
		 * @param {string} tag   Tag name.
		 * @param {Object} attrs Attributes to set.
		 * @param {string} text  Optional text content.
		 * @return {HTMLElement}
		 */
		createElement: function (tag, attrs, text) {
			var el = document.createElement(tag);
			if (attrs) {
				for (var key in attrs) {
					if (attrs.hasOwnProperty(key)) {
						if (key === 'className') {
							el.className = attrs[key];
						} else if (key === 'style' && typeof attrs[key] === 'object') {
							for (var prop in attrs[key]) {
								if (attrs[key].hasOwnProperty(prop)) {
									el.style[prop] = attrs[key][prop];
								}
							}
						} else {
							el.setAttribute(key, attrs[key]);
						}
					}
				}
			}
			if (text) {
				el.textContent = text;
			}
			return el;
		},

		/**
		 * Create a table row with text cells (safe — no innerHTML).
		 *
		 * @param {Array} values Array of cell text values.
		 * @return {HTMLElement} <tr> element.
		 */
		createTableRow: function (values) {
			var tr = document.createElement('tr');
			for (var i = 0; i < values.length; i++) {
				var td = document.createElement('td');
				td.textContent = values[i] || '';
				tr.appendChild(td);
			}
			return tr;
		},

		/**
		 * Create a badge element.
		 *
		 * @param {string} text     Badge text.
		 * @param {string} modifier BEM modifier (e.g. 'success', 'warning').
		 * @return {HTMLElement}
		 */
		createBadge: function (text, modifier) {
			var span = document.createElement('span');
			span.className = 'cocookie-badge' + (modifier ? ' cocookie-badge--' + modifier : '');
			span.textContent = text;
			return span;
		},

		/**
		 * Show an element by removing display:none.
		 *
		 * @param {string|HTMLElement} el Element or selector.
		 */
		show: function (el) {
			if (typeof el === 'string') {
				el = document.querySelector(el);
			}
			if (el) {
				el.style.display = '';
			}
		},

		/**
		 * Hide an element with display:none.
		 *
		 * @param {string|HTMLElement} el Element or selector.
		 */
		hide: function (el) {
			if (typeof el === 'string') {
				el = document.querySelector(el);
			}
			if (el) {
				el.style.display = 'none';
			}
		},

		/**
		 * Make a REST API call.
		 *
		 * @param {string} endpoint Endpoint path (relative to REST root).
		 * @param {Object} options  Fetch options.
		 * @return {Promise}
		 */
		api: function (endpoint, options) {
			var defaults = {
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': ''
				}
			};

			// Merge headers
			if (options && options.headers) {
				for (var key in options.headers) {
					defaults.headers[key] = options.headers[key];
				}
				delete options.headers;
			}

			var merged = Object.assign({}, defaults, options);

			return fetch(endpoint, merged).then(function (r) {
				return r.json();
			});
		},

		/**
		 * Lägg en cookie i ignoreringslistan via REST.
		 *
		 * @param {string} restUrl REST-rot.
		 * @param {string} nonce   wp_rest-nonce.
		 * @param {string} name    Cookie-namn.
		 * @return {Promise}
		 */
		ignoreCookie: function (restUrl, nonce, name) {
			return this.api(restUrl + 'cocookie/v1/scan/ignore', {
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
				body: JSON.stringify({ name: name })
			});
		},

		/**
		 * Ta bort en cookie ur ignoreringslistan via REST.
		 *
		 * @param {string} restUrl REST-rot.
		 * @param {string} nonce   wp_rest-nonce.
		 * @param {string} name    Cookie-namn.
		 * @return {Promise}
		 */
		unignoreCookie: function (restUrl, nonce, name) {
			return this.api(restUrl + 'cocookie/v1/scan/unignore', {
				method: 'POST',
				headers: { 'X-WP-Nonce': nonce },
				body: JSON.stringify({ name: name })
			});
		},

		/**
		 * Kontrollera om ett dokument (skannerns iframe) innehåller
		 * Google Analytics eller Tag Manager.
		 *
		 * @param {Document} doc Iframe-dokumentet.
		 * @param {Window}   win Iframe-fönstret.
		 * @return {boolean}
		 */
		hasGoogleTracking: function (doc, win) {
			try {
				if (doc && doc.querySelector('script[src*="googletagmanager.com"], script[src*="google-analytics.com"]')) {
					return true;
				}
				return !!(win && typeof win.gtag === 'function');
			} catch (e) {
				return false;
			}
		},

		/**
		 * Ta reda på varför analytics-cookies kan saknas i skanningen.
		 *
		 * Skanningen körs i en inloggad admins webbläsare. Många GA-plugins
		 * (Site Kit, MonsterInsights m.fl.) laddar inte spårningen för
		 * inloggade administratörer. Här hämtas startsidan anonymt (utan
		 * cookies) och jämförs med det som fanns i skannerns iframe.
		 *
		 * @param {string}   siteUrl          Webbplatsens startsida.
		 * @param {boolean}  iframeHadTracking GA/GTM fanns i iframen.
		 * @param {string[]} cookieNames      Hittade cookienamn.
		 * @param {boolean}  crossOrigin      Iframen kunde inte läsas (annan origin).
		 * @return {Promise<string[]>} Hint-nycklar: ga_logged_in, ga_blocked, cross_origin.
		 */
		scanHints: function (siteUrl, iframeHadTracking, cookieNames, crossOrigin) {
			var hints = crossOrigin ? ['cross_origin'] : [];
			var hasGa = cookieNames.some(function (n) { return /^_ga/.test(n); });

			if (hasGa) {
				return Promise.resolve(hints);
			}

			return fetch(siteUrl, { credentials: 'omit', cache: 'no-store' })
				.then(function (r) { return r.text(); })
				.then(function (html) {
					var anonHasTracking = /googletagmanager\.com|google-analytics\.com|gtag\s*\(/i.test(html);
					if (anonHasTracking && !iframeHadTracking) {
						hints.push('ga_logged_in');
					} else if (iframeHadTracking) {
						hints.push('ga_blocked');
					}
					return hints;
				})
				.catch(function () {
					return hints;
				});
		},

		/**
		 * Visa de varningsrutor som matchar hint-nycklarna.
		 * Rutorna renderas dolda av PHP med id "<prefix>-<nyckel>".
		 *
		 * @param {string[]} hints  Hint-nycklar.
		 * @param {string}   prefix Element-id-prefix.
		 */
		showScanHints: function (hints, prefix) {
			var self = this;
			(hints || []).forEach(function (key) {
				self.show('#' + prefix + '-' + key);
			});
		}
	};
})();
