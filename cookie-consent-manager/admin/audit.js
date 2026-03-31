(function () {
    'use strict';

    var btnStart   = document.getElementById('ccm-start-audit');
    var statusDiv  = document.getElementById('ccm-audit-status');
    var statusText = document.querySelector('.ccm-audit-status-text');
    var iframe     = document.getElementById('ccm-audit-iframe');

    var sectionRegistered  = document.getElementById('ccm-audit-registered');
    var sectionViolations  = document.getElementById('ccm-audit-violations');
    var sectionUnknown     = document.getElementById('ccm-audit-unknown');
    var tbody              = document.getElementById('ccm-audit-tbody');
    var violationList      = document.getElementById('ccm-audit-violation-list');
    var unknownList        = document.getElementById('ccm-audit-unknown-list');
    var blockedList        = document.getElementById('ccm-blocked-list');
    var noBlockedMsg       = document.getElementById('ccm-no-blocked');

    var currentBlocked = [];

    if (btnStart) {
        btnStart.addEventListener('click', startAudit);
    }

    // Delegate click events for block/unblock buttons
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('ccm-block-cookie')) {
            blockCookie(e.target.getAttribute('data-name'), e.target);
        }
        if (e.target.classList.contains('ccm-unblock-cookie')) {
            unblockCookie(e.target.getAttribute('data-name'), e.target);
        }
    });

    function startAudit() {
        btnStart.disabled = true;
        statusDiv.style.display = 'flex';
        statusText.textContent = 'Laddar webbplatsen i bakgrunden...';

        sectionRegistered.style.display = 'none';
        sectionViolations.style.display = 'none';
        sectionUnknown.style.display = 'none';

        iframe.src = ccmAudit.siteUrl;

        iframe.onload = function () {
            statusText.textContent = 'Väntar på att cookies sätts...';

            setTimeout(function () {
                var cookies = readCookies();
                statusText.textContent = 'Analyserar ' + cookies.length + ' cookies...';
                sendToServer(cookies);
            }, 3000);
        };

        iframe.onerror = function () {
            statusText.textContent = 'Kunde inte ladda webbplatsen.';
            btnStart.disabled = false;
        };
    }

    function readCookies() {
        var cookies = [];
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
        var seen = {};

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

            if (name && !seen[name]) {
                seen[name] = true;
                cookies.push({
                    name: name,
                    value: value.substring(0, 50),
                    domain: window.location.hostname
                });
            }
        }

        return cookies;
    }

    function sendToServer(cookies) {
        fetch(ccmAudit.restUrl + 'cc/v1/audit', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ccmAudit.nonce
            },
            body: JSON.stringify({ cookies: cookies })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            statusDiv.style.display = 'none';
            btnStart.disabled = false;
            currentBlocked = data.blocked || [];
            renderResults(data);
        })
        .catch(function (err) {
            statusDiv.style.display = 'none';
            btnStart.disabled = false;
            alert('Audit misslyckades: ' + err.message);
        });
    }

    function isBlocked(name) {
        return currentBlocked.indexOf(name) !== -1;
    }

    function renderResults(data) {
        // Registered cookies table
        tbody.innerHTML = '';
        if (data.registered && data.registered.length > 0) {
            sectionRegistered.style.display = '';

            for (var i = 0; i < data.registered.length; i++) {
                var r = data.registered[i];
                var tr = document.createElement('tr');

                var expected = r.is_required ? 'Ska sättas (nödvändig)' : 'Kräver samtycke';
                var found = r.found ? 'Ja' : 'Nej';

                var iconClass, iconColor;
                if (r.status === 'ok') {
                    iconClass = 'dashicons-yes-alt';
                    iconColor = 'ccm-status-ok';
                } else if (r.status === 'violation') {
                    iconClass = 'dashicons-dismiss';
                    iconColor = 'ccm-status-violation';
                } else {
                    iconClass = 'dashicons-warning';
                    iconColor = 'ccm-status-warning';
                }

                var actionHtml = '';
                if (r.status === 'violation') {
                    if (isBlocked(r.name)) {
                        actionHtml = '<span class="ccm-blocked-label">Blockerad</span>';
                    } else {
                        actionHtml = '<button type="button" class="button button-small ccm-block-cookie" data-name="' + escAttr(r.name) + '">Blockera</button>';
                    }
                }

                tr.innerHTML =
                    '<td><strong>' + escHtml(r.name) + '</strong></td>' +
                    '<td>' + escHtml(r.category) + '</td>' +
                    '<td>' + escHtml(expected) + '</td>' +
                    '<td>' + escHtml(found) + '</td>' +
                    '<td><span class="dashicons ' + iconClass + ' ' + iconColor + '"></span></td>' +
                    '<td>' + actionHtml + '</td>';

                tbody.appendChild(tr);
            }
        }

        // Violations
        violationList.innerHTML = '';
        if (data.violations && data.violations.length > 0) {
            sectionViolations.style.display = '';

            for (var j = 0; j < data.violations.length; j++) {
                var v = data.violations[j];
                var li = document.createElement('li');
                var blocked = isBlocked(v.name);
                li.innerHTML = '<span class="dashicons dashicons-dismiss ccm-status-violation"></span> ' +
                    '<strong>' + escHtml(v.name) + '</strong> (' + escHtml(v.category) + ')' +
                    (blocked
                        ? ' <span class="ccm-blocked-label">Blockerad</span>'
                        : ' <button type="button" class="button button-small ccm-block-cookie" data-name="' + escAttr(v.name) + '">Blockera</button>');
                violationList.appendChild(li);
            }
        }

        // Unknown cookies
        unknownList.innerHTML = '';
        if (data.unknown && data.unknown.length > 0) {
            sectionUnknown.style.display = '';

            for (var k = 0; k < data.unknown.length; k++) {
                var u = data.unknown[k];
                var uli = document.createElement('li');
                var uBlocked = isBlocked(u.name);
                uli.innerHTML = '<span class="dashicons dashicons-editor-help ccm-status-unknown"></span> ' +
                    '<strong>' + escHtml(u.name) + '</strong>' +
                    (u.domain ? ' <small>(' + escHtml(u.domain) + ')</small>' : '') +
                    (uBlocked
                        ? ' <span class="ccm-blocked-label">Blockerad</span>'
                        : ' <button type="button" class="button button-small ccm-block-cookie" data-name="' + escAttr(u.name) + '">Blockera</button>');
                unknownList.appendChild(uli);
            }
        }

        // Update blocked list
        renderBlockedList();
    }

    function blockCookie(name, btn) {
        if (!name) return;
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Blockerar...';
        }

        fetch(ccmAudit.restUrl + 'cc/v1/audit/block', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ccmAudit.nonce
            },
            body: JSON.stringify({ name: name })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                currentBlocked = data.blocked;
                if (btn) {
                    var parent = btn.parentNode;
                    btn.remove();
                    var label = document.createElement('span');
                    label.className = 'ccm-blocked-label';
                    label.textContent = 'Blockerad';
                    parent.appendChild(label);
                }
                renderBlockedList();
            }
        })
        .catch(function () {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Blockera';
            }
        });
    }

    function unblockCookie(name, btn) {
        if (!name) return;
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Avblockerar...';
        }

        fetch(ccmAudit.restUrl + 'cc/v1/audit/unblock', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': ccmAudit.nonce
            },
            body: JSON.stringify({ name: name })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.success) {
                currentBlocked = data.blocked;
                renderBlockedList();
            }
        })
        .catch(function () {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Avblockera';
            }
        });
    }

    function renderBlockedList() {
        blockedList.innerHTML = '';
        if (currentBlocked.length === 0) {
            if (noBlockedMsg) {
                noBlockedMsg.style.display = '';
            } else {
                var p = document.createElement('p');
                p.id = 'ccm-no-blocked';
                p.textContent = 'Inga cookies är blockerade.';
                blockedList.parentNode.insertBefore(p, blockedList);
                noBlockedMsg = p;
            }
            return;
        }

        if (noBlockedMsg) {
            noBlockedMsg.style.display = 'none';
        }

        for (var i = 0; i < currentBlocked.length; i++) {
            var li = document.createElement('li');
            li.setAttribute('data-name', currentBlocked[i]);
            li.innerHTML = '<span class="dashicons dashicons-no ccm-status-violation"></span> ' +
                '<strong>' + escHtml(currentBlocked[i]) + '</strong> ' +
                '<button type="button" class="button button-small ccm-unblock-cookie" data-name="' + escAttr(currentBlocked[i]) + '">Avblockera</button>';
            blockedList.appendChild(li);
        }
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        return escHtml(str).replace(/"/g, '&quot;');
    }
})();
