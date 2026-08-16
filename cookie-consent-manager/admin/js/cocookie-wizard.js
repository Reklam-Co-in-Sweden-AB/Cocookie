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

	// Step 2: Import all
	var importAllBtn = document.getElementById('cocookie-wizard-import-all');
	if (importAllBtn) {
		importAllBtn.addEventListener('click', function () {
			importAllBtn.disabled = true;
			importAllBtn.textContent = 'Importerar...';

			ui.api(config.restUrl + 'cocookie/v1/scan/import-all', {
				method: 'POST',
				headers: { 'X-WP-Nonce': config.nonce },
				body: JSON.stringify({})
			}).then(function (data) {
				importAllBtn.textContent = 'Importerat ' + (data.imported || 0) + ' cookies';
				// Reload to show updated status
				setTimeout(function () {
					window.location.reload();
				}, 1000);
			});
		});
	}

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

		iframe.addEventListener('load', function () {
			collectCookies();
		});

		function collectCookies() {
			// Collect from iframe
			try {
				var iframeCookies = iframe.contentDocument.cookie;
				parseCookies(iframeCookies);
			} catch (e) {
				// Cross-origin — fall back to main document
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
			});
		}
	}
})();
