(function () {
    'use strict';

    var btnStart    = document.getElementById('ccm-start-scan');
    var btnImportAll = document.getElementById('ccm-import-all');
    var statusDiv   = document.getElementById('ccm-scan-status');
    var statusText  = document.querySelector('.ccm-scan-status-text');
    var resultsDiv  = document.getElementById('ccm-scan-results');
    var tbody       = document.getElementById('ccm-scan-tbody');
    var iframe      = document.getElementById('ccm-scan-iframe');

    var categoryLabels = {
        necessary: 'Nödvändig',
        analytics: 'Analys',
        marketing: 'Marknadsföring'
    };

    if (btnStart) {
        btnStart.addEventListener('click', startScan);
    }

    if (btnImportAll) {
        btnImportAll.addEventListener('click', importAll);
    }

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('ccm-import-cookie')) {
            importCookie(e.target);
        }
    });

    function startScan() {
        btnStart.disabled = true;
        statusDiv.style.display = 'flex';
        statusText.textContent = 'Laddar webbplatsen i bakgrunden...';

        iframe.src = ccmScanner.siteUrl + '?ccm_clean_scan=' + encodeURIComponent(ccmScanner.cleanScanNonce);

        iframe.onload = function () {
            var rounds = 0;
            var maxRounds = 5;
            var interval = 2000;
            var allCookies = {};

            function collectRound() {
                rounds++;
                statusText.textContent = 'Samlar cookies... (omgång ' + rounds + '/' + maxRounds + ')';

                var cookies = readCookies();
                for (var i = 0; i < cookies.length; i++) {
                    allCookies[cookies[i].name] = cookies[i];
                }

                if (rounds < maxRounds) {
                    setTimeout(collectRound, interval);
                } else {
                    var result = [];
                    for (var name in allCookies) {
                        if (allCookies.hasOwnProperty(name)) {
                            result.push(allCookies[name]);
                        }
                    }
                    statusText.textContent = 'Klassificerar ' + result.length + ' cookies...';
                    sendToServer(result);
                }
            }

            setTimeout(collectRound, interval);
        };

        iframe.onerror = function () {
            statusText.textContent = 'Kunde inte ladda webbplatsen.';
            btnStart.disabled = false;
        };
    }

    function readCookies() {
        var items = [];
        var seen = {};

        // 1. Read document.cookie
        var raw = '';
        try {
            raw = iframe.contentDocument.cookie;
        } catch (e) {
            raw = document.cookie;
        }
        if (!raw) {
            raw = document.cookie;
        }

        var pairs = raw.split(';');
        for (var i = 0; i < pairs.length; i++) {
            var pair = pairs[i].trim();
            if (!pair) continue;

            var eqIndex = pair.indexOf('=');
            var name, value;

            if (eqIndex > -1) {
                name = pair.substring(0, eqIndex).trim();
                value = pair.substring(eqIndex + 1).trim();
            } else {
                name = pair.trim();
                value = '';
            }

            if (name && !seen['cookie:' + name]) {
                seen['cookie:' + name] = true;
                items.push({
                    name: name,
                    value: value.substring(0, 50),
                    domain: window.location.hostname,
                    storage_type: 'cookie'
                });
            }
        }

        // 2. Read localStorage
        try {
            var storage = iframe.contentWindow ? iframe.contentWindow.localStorage : localStorage;
            for (var j = 0; j < storage.length; j++) {
                var key = storage.key(j);
                if (key && !seen['ls:' + key]) {
                    seen['ls:' + key] = true;
                    var val = '';
                    try { val = (storage.getItem(key) || '').substring(0, 50); } catch (e) {}
                    items.push({
                        name: key,
                        value: val,
                        domain: window.location.hostname,
                        storage_type: 'localStorage'
                    });
                }
            }
        } catch (e) {
            // Same-origin policy or localStorage unavailable
        }

        // 3. Read sessionStorage
        try {
            var sess = iframe.contentWindow ? iframe.contentWindow.sessionStorage : sessionStorage;
            for (var k = 0; k < sess.length; k++) {
                var sKey = sess.key(k);
                if (sKey && !seen['ss:' + sKey]) {
                    seen['ss:' + sKey] = true;
                    var sVal = '';
                    try { sVal = (sess.getItem(sKey) || '').substring(0, 50); } catch (e) {}
                    items.push({
                        name: sKey,
                        value: sVal,
                        domain: window.location.hostname,
                        storage_type: 'sessionStorage'
                    });
                }
            }
        } catch (e) {
            // Same-origin policy or sessionStorage unavailable
        }

        return items;
    }

    function sendToServer(cookies) {
        fetch(ccmScanner.restUrl + 'cc/v1/scan', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ccmScanner.nonce
            },
            body: JSON.stringify({ cookies: cookies })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            statusDiv.style.display = 'none';
            btnStart.disabled = false;

            if (data.success) {
                renderResults(data.results);
            } else {
                statusText.textContent = 'Fel: ' + (data.message || 'Okänt fel');
                statusDiv.style.display = 'flex';
            }
        })
        .catch(function (err) {
            statusDiv.style.display = 'none';
            btnStart.disabled = false;
            alert('Skanningen misslyckades: ' + err.message);
        });
    }

    function renderResults(results) {
        resultsDiv.style.display = '';
        tbody.innerHTML = '';

        // Update toolbar
        var toolbar = resultsDiv.querySelector('.ccm-scan-toolbar');
        if (!toolbar) {
            toolbar = document.createElement('div');
            toolbar.className = 'ccm-scan-toolbar';
            resultsDiv.insertBefore(toolbar, resultsDiv.querySelector('table'));
        }
        toolbar.innerHTML = '<span class="ccm-count">' + results.length + ' cookies hittade</span>' +
            '<button type="button" id="ccm-import-all" class="button">Importera alla</button>';

        var newImportAll = document.getElementById('ccm-import-all');
        if (newImportAll) {
            newImportAll.addEventListener('click', importAll);
        }

        // Update last scan text
        var lastScan = document.querySelector('.ccm-last-scan');
        var now = new Date().toLocaleString('sv-SE');
        if (lastScan) {
            lastScan.textContent = 'Senaste skanning: ' + now;
        } else {
            var span = document.createElement('span');
            span.className = 'ccm-last-scan';
            span.textContent = 'Senaste skanning: ' + now;
            document.querySelector('.ccm-scanner-actions').appendChild(span);
        }

        for (var i = 0; i < results.length; i++) {
            var r = results[i];
            var tr = document.createElement('tr');
            tr.setAttribute('data-id', r.id);

            var label = categoryLabels[r.suggested_category] || r.suggested_category;

            var storageLabel = r.storage_type || 'cookie';

            tr.innerHTML =
                '<td class="column-name"><strong>' + escHtml(r.name) + '</strong>' +
                    (r.domain ? '<br><small class="ccm-domain">' + escHtml(r.domain) + '</small>' : '') +
                '</td>' +
                '<td class="column-type">' + escHtml(storageLabel) + '</td>' +
                '<td class="column-category"><span class="ccm-category-badge ccm-cat-' + escHtml(r.suggested_category) + '">' + escHtml(label) + '</span></td>' +
                '<td class="column-provider">' + escHtml(r.suggested_provider) + '</td>' +
                '<td class="column-purpose">' + escHtml(r.suggested_purpose) + '</td>' +
                '<td class="column-status"><span class="ccm-import-status ccm-not-imported">Ej importerad</span></td>' +
                '<td class="column-actions"><button type="button" class="button button-small ccm-import-cookie" data-id="' + r.id + '">Importera</button></td>';

            tbody.appendChild(tr);
        }
    }

    function importCookie(btn) {
        var id = btn.getAttribute('data-id');
        btn.disabled = true;
        btn.textContent = 'Importerar...';

        fetch(ccmScanner.restUrl + 'cc/v1/scan/import', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ccmScanner.nonce
            },
            body: JSON.stringify({ scan_id: parseInt(id, 10) })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var row = btn.closest('tr');
            if (data.success) {
                markAsImported(row);
            } else {
                if (data.code === 'already_exists' || data.code === 'already_imported') {
                    markAsImported(row);
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Importera';
                    alert('Fel: ' + (data.message || 'Okänt fel'));
                }
            }
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.textContent = 'Importera';
            alert('Import misslyckades: ' + err.message);
        });
    }

    function importAll() {
        var btn = document.getElementById('ccm-import-all');
        if (!btn) return;
        btn.disabled = true;
        btn.textContent = 'Importerar...';

        fetch(ccmScanner.restUrl + 'cc/v1/scan/import-all', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ccmScanner.nonce
            },
            body: JSON.stringify({})
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            btn.disabled = false;
            btn.textContent = 'Importera alla';

            if (data.imported > 0 || data.skipped > 0) {
                var rows = tbody.querySelectorAll('tr');
                for (var i = 0; i < rows.length; i++) {
                    markAsImported(rows[i]);
                }
            }

            var msg = data.imported + ' importerade';
            if (data.skipped) {
                msg += ', ' + data.skipped + ' överhoppade';
            }
            if (data.errors && data.errors.length > 0) {
                msg += '\n\nFel:\n' + data.errors.join('\n');
                alert(msg);
            }
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.textContent = 'Importera alla';
            alert('Import misslyckades: ' + err.message);
        });
    }

    function markAsImported(row) {
        var statusCell = row.querySelector('.column-status');
        var actionCell = row.querySelector('.column-actions');

        if (statusCell) {
            statusCell.innerHTML = '<span class="ccm-import-status ccm-imported">Importerad</span>';
        }
        if (actionCell) {
            actionCell.innerHTML = '<span class="dashicons dashicons-yes-alt ccm-imported-icon"></span>';
        }
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
})();
