/**
 * CoCookie Audit — safe client-side cookie auditing.
 *
 * Replaces the original audit.js with XSS-safe DOM manipulation.
 *
 * @package CoCookie
 */
(function () {
	'use strict';

	var config = window.cocookieAudit;
	if (!config) return;

	var ui = window.CoCookieAdmin;

	var auditBtn = document.getElementById('cocookie-start-audit');
	if (auditBtn) {
		auditBtn.addEventListener('click', startAudit);
	}

	// Unblock buttons
	document.addEventListener('click', function (e) {
		if (!e.target.classList.contains('cocookie-unblock-btn')) return;
		e.preventDefault();
		var name = e.target.getAttribute('data-name');
		unblockCookie(name, e.target);
	});

	function startAudit() {
		auditBtn.disabled = true;
		ui.show('#cocookie-audit-status');

		var iframe = document.createElement('iframe');
		iframe.style.display = 'none';
		iframe.src = config.siteUrl;
		document.body.appendChild(iframe);

		iframe.addEventListener('load', function () {
			setTimeout(function () {
				var cookies = [];
				try {
					var str = iframe.contentDocument.cookie;
					if (str) {
						str.split(';').forEach(function (c) {
							var parts = c.trim().split('=');
							cookies.push({
								name: parts[0],
								domain: location.hostname
							});
						});
					}
				} catch (e) {
					document.cookie.split(';').forEach(function (c) {
						var parts = c.trim().split('=');
						cookies.push({
							name: parts[0],
							domain: location.hostname
						});
					});
				}

				document.body.removeChild(iframe);
				submitAudit(cookies);
			}, 3000);
		});
	}

	function submitAudit(cookies) {
		ui.setText(document.getElementById('cocookie-audit-status-text'),
			'Analyserar ' + cookies.length + ' cookies...');

		ui.api(config.restUrl + 'cocookie/v1/audit', {
			method: 'POST',
			headers: { 'X-WP-Nonce': config.nonce },
			body: JSON.stringify({ cookies: cookies })
		}).then(function (data) {
			ui.hide('#cocookie-audit-status');
			auditBtn.disabled = false;
			renderResults(data);
		});
	}

	function renderResults(data) {
		var container = document.getElementById('cocookie-audit-results');
		if (!container) return;

		container.textContent = ''; // Safe clear

		// Registered cookies status
		if (data.registered && data.registered.length > 0) {
			var section = ui.createElement('div', { className: 'cocookie-audit-section' });
			section.appendChild(ui.createElement('h3', {}, 'Registrerade cookies'));

			var table = ui.createElement('table', { className: 'cocookie-table widefat striped' });
			var thead = document.createElement('thead');
			var headRow = document.createElement('tr');
			['Cookie', 'Kategori', 'Hittad', 'Status'].forEach(function (t) {
				headRow.appendChild(ui.createElement('th', {}, t));
			});
			thead.appendChild(headRow);
			table.appendChild(thead);

			var tbody = document.createElement('tbody');
			data.registered.forEach(function (r) {
				var tr = document.createElement('tr');

				var tdName = document.createElement('td');
				var code = document.createElement('code');
				code.textContent = r.name;
				tdName.appendChild(code);
				tr.appendChild(tdName);

				tr.appendChild(ui.createElement('td', {}, r.category));
				tr.appendChild(ui.createElement('td', {}, r.found ? 'Ja' : 'Nej'));

				var tdStatus = document.createElement('td');
				var statusMap = {
					ok: { text: 'OK', mod: 'success' },
					violation: { text: 'Överträdelse', mod: 'error' },
					warning: { text: 'Varning', mod: 'warning' }
				};
				var s = statusMap[r.status] || { text: r.status, mod: 'muted' };
				tdStatus.appendChild(ui.createBadge(s.text, s.mod));
				tr.appendChild(tdStatus);

				tbody.appendChild(tr);
			});
			table.appendChild(tbody);
			section.appendChild(table);
			container.appendChild(section);
		}

		// Violations
		if (data.violations && data.violations.length > 0) {
			var violSection = ui.createElement('div', { className: 'cocookie-audit-section' });
			violSection.appendChild(ui.createElement('h3', {}, 'Överträdelser (' + data.violations.length + ')'));

			var violBox = ui.createElement('div', { className: 'cocookie-notice cocookie-notice--error' });
			violBox.appendChild(ui.createElement('p', {},
				'Dessa cookies sätts utan samtycke och bör blockeras eller tas bort:'));

			var ul = document.createElement('ul');
			data.violations.forEach(function (v) {
				var li = document.createElement('li');
				var code = document.createElement('code');
				code.textContent = v.name;
				li.appendChild(code);
				li.appendChild(document.createTextNode(' (' + v.category + ') '));

				var blockBtn = ui.createElement('button', {
					className: 'button button-small',
					'data-name': v.name
				}, 'Blockera');
				blockBtn.addEventListener('click', function () {
					blockCookie(v.name, blockBtn);
				});
				li.appendChild(blockBtn);
				ul.appendChild(li);
			});
			violBox.appendChild(ul);
			violSection.appendChild(violBox);
			container.appendChild(violSection);
		}

		// Unknown cookies
		if (data.unknown && data.unknown.length > 0) {
			var unkSection = ui.createElement('div', { className: 'cocookie-audit-section' });
			unkSection.appendChild(ui.createElement('h3', {}, 'Okända cookies (' + data.unknown.length + ')'));

			var unkBox = ui.createElement('div', { className: 'cocookie-notice cocookie-notice--warning' });
			unkBox.appendChild(ui.createElement('p', {},
				'Dessa cookies finns inte i ditt register. Registrera eller blockera dem.'));

			var unkUl = document.createElement('ul');
			data.unknown.forEach(function (u) {
				var li = document.createElement('li');
				var code = document.createElement('code');
				code.textContent = u.name;
				li.appendChild(code);
				if (u.domain) {
					li.appendChild(document.createTextNode(' (' + u.domain + ')'));
				}
				unkUl.appendChild(li);
			});
			unkBox.appendChild(unkUl);
			unkSection.appendChild(unkBox);
			container.appendChild(unkSection);
		}

		// No issues
		if ((!data.violations || data.violations.length === 0) &&
			(!data.unknown || data.unknown.length === 0)) {
			var okBox = ui.createElement('div', { className: 'cocookie-notice cocookie-notice--success' });
			okBox.appendChild(ui.createElement('p', {},
				'Inga överträdelser eller okända cookies hittade. Bra jobbat!'));
			container.appendChild(okBox);
		}
	}

	function blockCookie(name, btn) {
		btn.disabled = true;
		btn.textContent = '...';

		ui.api(config.restUrl + 'cocookie/v1/audit/block', {
			method: 'POST',
			headers: { 'X-WP-Nonce': config.nonce },
			body: JSON.stringify({ name: name })
		}).then(function () {
			btn.textContent = 'Blockerad';
			btn.className = 'button button-small disabled';
		});
	}

	function unblockCookie(name, btn) {
		btn.disabled = true;
		btn.textContent = '...';

		ui.api(config.restUrl + 'cocookie/v1/audit/unblock', {
			method: 'POST',
			headers: { 'X-WP-Nonce': config.nonce },
			body: JSON.stringify({ name: name })
		}).then(function () {
			var li = btn.parentNode;
			if (li) {
				li.parentNode.removeChild(li);
			}
		});
	}
})();
