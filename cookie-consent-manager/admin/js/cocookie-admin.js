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
		}
	};
})();
