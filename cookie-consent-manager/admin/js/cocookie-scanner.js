/**
 * CoCookie Scanner — safe client-side cookie scanning.
 *
 * Replaces the original scanner.js with XSS-safe DOM manipulation.
 * Uses CoCookieAdmin helpers instead of innerHTML.
 *
 * @package CoCookie
 */
(function () {
	'use strict';

	var config = window.cocookieScanner;
	if (!config) return;

	var ui = window.CoCookieAdmin;

	var scanBtn = document.getElementById('cocookie-start-scan');
	var importAllBtn = document.getElementById('cocookie-import-all');

	if (scanBtn) {
		scanBtn.addEventListener('click', startScan);
	}

	if (importAllBtn) {
		importAllBtn.addEventListener('click', importAll);
	}

	function startScan() {
		scanBtn.disabled = true;
		ui.show('#cocookie-scan-status');
		ui.hide('#cocookie-scan-toolbar');

		var iframe = document.createElement('iframe');
		iframe.style.display = 'none';
		iframe.src = config.siteUrl + '?ccm_clean_scan=' + config.cleanScanNonce;
		document.body.appendChild(iframe);

		var allCookies = {};
		var round = 0;
		var maxRounds = 5;

		iframe.addEventListener('load', function () {
			collect();
		});

		function collect() {
			try {
				parseCookies(iframe.contentDocument.cookie);
				collectStorage(iframe.contentWindow.localStorage, 'localStorage');
				collectStorage(iframe.contentWindow.sessionStorage, 'sessionStorage');
			} catch (e) {
				parseCookies(document.cookie);
			}

			round++;
			ui.setText(document.getElementById('cocookie-scan-status-text'),
				'Skannar... (omgång ' + round + '/' + maxRounds + ')');

			if (round < maxRounds) {
				setTimeout(collect, 2000);
			} else {
				document.body.removeChild(iframe);
				submitResults();
			}
		}

		function parseCookies(str) {
			if (!str) return;
			str.split(';').forEach(function (c) {
				var parts = c.trim().split('=');
				var name = parts[0];
				if (name && !allCookies[name]) {
					allCookies[name] = {
						name: name,
						value: parts.slice(1).join('=').substring(0, 50),
						domain: location.hostname,
						storage_type: 'cookie'
					};
				}
			});
		}

		function collectStorage(storage, type) {
			if (!storage) return;
			try {
				for (var i = 0; i < storage.length; i++) {
					var key = storage.key(i);
					if (!allCookies[key]) {
						allCookies[key] = {
							name: key,
							value: (storage.getItem(key) || '').substring(0, 50),
							domain: location.hostname,
							storage_type: type
						};
					}
				}
			} catch (e) { /* cross-origin */ }
		}

		function submitResults() {
			var arr = Object.values(allCookies);
			ui.setText(document.getElementById('cocookie-scan-status-text'),
				'Klassificerar ' + arr.length + ' cookies...');

			ui.api(config.restUrl + 'cocookie/v1/scan', {
				method: 'POST',
				headers: { 'X-WP-Nonce': config.nonce },
				body: JSON.stringify({ cookies: arr })
			}).then(function (data) {
				ui.hide('#cocookie-scan-status');
				scanBtn.disabled = false;
				renderResults(data.results || []);
			});
		}
	}

	function renderResults(results) {
		var tbody = document.getElementById('cocookie-scan-tbody');
		if (!tbody) return;

		tbody.textContent = ''; // Safe clear

		ui.show('#cocookie-scan-table');
		ui.show('#cocookie-scan-toolbar');

		ui.setText(document.getElementById('cocookie-scan-count'),
			results.length + ' cookies hittade');

		results.forEach(function (r) {
			var tr = document.createElement('tr');

			// Name (code element)
			var tdName = document.createElement('td');
			var code = document.createElement('code');
			code.textContent = r.name;
			tdName.appendChild(code);
			tr.appendChild(tdName);

			// Type
			var tdType = document.createElement('td');
			tdType.textContent = r.storage_type || 'cookie';
			tr.appendChild(tdType);

			// Category badge
			var tdCat = document.createElement('td');
			tdCat.appendChild(ui.createBadge(r.suggested_category, r.suggested_category));
			tr.appendChild(tdCat);

			// Provider
			var tdProv = document.createElement('td');
			tdProv.textContent = r.suggested_provider;
			tr.appendChild(tdProv);

			// Purpose
			var tdPurp = document.createElement('td');
			tdPurp.textContent = r.suggested_purpose;
			tr.appendChild(tdPurp);

			// Status
			var tdStatus = document.createElement('td');
			tdStatus.appendChild(ui.createBadge(
				r.is_imported ? 'Importerad' : 'Ej importerad',
				r.is_imported ? 'success' : 'muted'
			));
			tr.appendChild(tdStatus);

			// Actions
			var tdActions = document.createElement('td');
			if (!r.is_imported) {
				var btn = ui.createElement('button', {
					className: 'button button-small',
					'data-id': r.id
				}, 'Importera');
				btn.addEventListener('click', function () {
					importSingle(r.id, btn);
				});
				tdActions.appendChild(btn);
			}
			tr.appendChild(tdActions);

			tbody.appendChild(tr);
		});
	}

	function importSingle(id, btn) {
		btn.disabled = true;
		btn.textContent = '...';

		ui.api(config.restUrl + 'cocookie/v1/scan/import', {
			method: 'POST',
			headers: { 'X-WP-Nonce': config.nonce },
			body: JSON.stringify({ scan_id: id })
		}).then(function () {
			btn.textContent = 'OK';
			btn.className = 'button button-small disabled';
		});
	}

	function importAll() {
		importAllBtn.disabled = true;
		importAllBtn.textContent = 'Importerar...';

		ui.api(config.restUrl + 'cocookie/v1/scan/import-all', {
			method: 'POST',
			headers: { 'X-WP-Nonce': config.nonce },
			body: JSON.stringify({})
		}).then(function (data) {
			importAllBtn.textContent = 'Importerat ' + (data.imported || 0) + ' cookies';
		});
	}
})();
