/**
 * CoCookie Setup Wizard — step navigation and AJAX scanning.
 *
 * @package CoCookie
 */
(function () {
	'use strict';

	var config = window.cocookieWizard;
	if (!config) return;

	var ui = window.CoCookieAdmin;

	// Step 1: Scan
	var scanBtn = document.getElementById('cocookie-wizard-scan');
	if (scanBtn) {
		scanBtn.addEventListener('click', function () {
			ui.hide(scanBtn);
			ui.show('#cocookie-wizard-scan-status');
			startScan();
		});
	}

	// Step 2: Importera markerade rader (en i taget via /scan/import)
	var importAllBtn = document.getElementById('cocookie-wizard-import-all');
	if (importAllBtn) {
		importAllBtn.addEventListener('click', function () {
			var checked = document.querySelectorAll('.cocookie-wizard-check:checked');
			var ids = Array.prototype.map.call(checked, function (cb) { return cb.value; });

			if (!ids.length) {
				importAllBtn.textContent = 'Inga cookies markerade';
				return;
			}

			importAllBtn.disabled = true;
			importAllBtn.textContent = 'Importerar...';

			var imported = 0;
			var chain = Promise.resolve();
			ids.forEach(function (id) {
				chain = chain.then(function () {
					return ui.api(config.restUrl + 'cocookie/v1/scan/import', {
						method: 'POST',
						headers: { 'X-WP-Nonce': config.nonce },
						body: JSON.stringify({ scan_id: id })
					}).then(function (data) {
						if (data && data.success) imported++;
					}).catch(function () { /* fortsätt med nästa */ });
				});
			});

			chain.then(function () {
				importAllBtn.textContent = 'Importerat ' + imported + ' cookies';
				// Ladda om så statusen uppdateras
				setTimeout(function () {
					window.location.reload();
				}, 1000);
			});
		});
	}

	// Step 2: Markera alla / avmarkera alla
	var checkAll = document.getElementById('cocookie-wizard-check-all');
	if (checkAll) {
		checkAll.addEventListener('change', function () {
			var boxes = document.querySelectorAll('.cocookie-wizard-check');
			for (var i = 0; i < boxes.length; i++) {
				boxes[i].checked = checkAll.checked;
			}
		});
	}

	// Step 2: Ignorera en cookie — raden försvinner och cookien döljs i kommande skanningar
	var ignoreBtns = document.querySelectorAll('.cocookie-wizard-ignore');
	Array.prototype.forEach.call(ignoreBtns, function (btn) {
		btn.addEventListener('click', function () {
			var name = btn.getAttribute('data-name');
			btn.disabled = true;
			btn.textContent = '...';

			ui.ignoreCookie(config.restUrl, config.nonce, name).then(function (data) {
				if (!data || !data.success) {
					btn.disabled = false;
					btn.textContent = 'Ignorera';
					return;
				}
				var tr = btn.closest('tr');
				if (tr && tr.parentNode) {
					tr.parentNode.removeChild(tr);
				}
			});
		});
	});

	/**
	 * Start the cookie scan process.
	 * Loads the site in a hidden iframe and collects cookies in rounds.
	 */
	function startScan() {
		var iframe = document.createElement('iframe');
		iframe.style.display = 'none';
		iframe.src = config.siteUrl + '?ccm_clean_scan=' + config.cleanScanNonce;
		document.body.appendChild(iframe);

		var allCookies = {};
		var round = 0;
		var maxRounds = 5;
		var iframeHadTracking = false;
		var crossOrigin = false;

		iframe.addEventListener('load', function () {
			collectCookies();
		});

		function collectCookies() {
			// Collect from iframe
			try {
				var iframeCookies = iframe.contentDocument.cookie;
				parseCookies(iframeCookies);
				if (!iframeHadTracking) {
					iframeHadTracking = ui.hasGoogleTracking(iframe.contentDocument, iframe.contentWindow);
				}
			} catch (e) {
				// Annan origin — vi ser bara adminsidans cookies
				crossOrigin = true;
				parseCookies(document.cookie);
			}

			// Collect localStorage and sessionStorage
			try {
				var iframeWin = iframe.contentWindow;
				collectStorage(iframeWin.localStorage, 'localStorage');
				collectStorage(iframeWin.sessionStorage, 'sessionStorage');
			} catch (e) {
				// Silently skip if cross-origin
			}

			round++;
			ui.setText(
				document.getElementById('cocookie-wizard-scan-text'),
				'Skannar... (omgång ' + round + '/' + maxRounds + ')'
			);

			if (round < maxRounds) {
				setTimeout(collectCookies, 2000);
			} else {
				finishScan();
			}
		}

		// Endast namnet samlas in — aldrig värdet. Scanningen körs i en inloggad
		// admins webbläsare och värden kan innehålla sessionstokens från andra
		// plugins. Klassificeringen behöver bara namnet.
		function parseCookies(cookieString) {
			if (!cookieString) return;
			cookieString.split(';').forEach(function (c) {
				var name = c.trim().split('=')[0];
				if (name && !allCookies[name]) {
					allCookies[name] = {
						name: name,
						domain: location.hostname,
						storage_type: 'cookie'
					};
				}
			});
		}

		function collectStorage(storage, type) {
			if (!storage) return;
			for (var i = 0; i < storage.length; i++) {
				var key = storage.key(i);
				if (!allCookies[key]) {
					allCookies[key] = {
						name: key,
						domain: location.hostname,
						storage_type: type
					};
				}
			}
		}

		function finishScan() {
			document.body.removeChild(iframe);

			var cookieArray = Object.values(allCookies);
			ui.setText(
				document.getElementById('cocookie-wizard-scan-text'),
				'Klassificerar ' + cookieArray.length + ' cookies...'
			);

			ui.api(config.restUrl + 'cocookie/v1/scan', {
				method: 'POST',
				headers: { 'X-WP-Nonce': config.nonce },
				body: JSON.stringify({ cookies: cookieArray })
			}).then(function (data) {
				ui.hide('#cocookie-wizard-scan-status');
				ui.show('#cocookie-wizard-scan-done');

				var count = data.results ? data.results.length : 0;
				ui.setText(
					document.getElementById('cocookie-wizard-scan-summary'),
					count + ' cookies hittade och klassificerade.'
				);

				// Förklara varför analytics-cookies kan saknas
				var names = cookieArray.map(function (c) { return c.name; });
				ui.scanHints(config.siteUrl, iframeHadTracking, names, crossOrigin).then(function (hints) {
					ui.showScanHints(hints, 'cocookie-wizard-hint');
				});
			});
		}
	}
})();
