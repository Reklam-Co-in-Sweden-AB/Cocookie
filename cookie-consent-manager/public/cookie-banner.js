(function () {
  'use strict';

  var config = window.ccmConfig;
  if (!config) return;

  var COOKIE_NAME = 'cc_consent';
  var isConsentChange = false;

  // --- Google Consent Mode v2 ---
  // Note: consent 'default' is set inline in <head> via PHP (before GTM/gtag.js loads).
  // This function handles runtime updates when the user interacts with the banner.
  function gtagConsentUpdate(consentData) {
    if (typeof gtag !== 'function') {
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () { dataLayer.push(arguments); };
    }
    var hasMarketing = !!consentData.marketing;
    var hasAnalytics = !!consentData.analytics;
    gtag('consent', 'update', {
      ad_storage:           hasMarketing ? 'granted' : 'denied',
      ad_user_data:         hasMarketing ? 'granted' : 'denied',
      ad_personalization:   hasMarketing ? 'granted' : 'denied',
      analytics_storage:    hasAnalytics ? 'granted' : 'denied',
      functionality_storage: 'granted',
      personalization_storage: hasAnalytics ? 'granted' : 'denied',
      security_storage:     'granted'
    });
    gtag('set', 'ads_data_redaction', !hasMarketing);
  }

  // Note: Cookie blocking (interceptor + deletion) is handled in the <head> inline script
  // output by output_gcm_default() in class-public.php. It must run before any other
  // scripts to intercept document.cookie writes.

  // --- DNT support ---
  function isDNTEnabled() {
    return navigator.doNotTrack === '1' || window.doNotTrack === '1';
  }

  // --- Cookie helpers ---
  function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
  }

  function setCookie(name, value, days) {
    // Cap at 395 days (EDPB max ~13 months)
    if (days > 395) days = 395;
    var d = new Date();
    d.setTime(d.getTime() + days * 86400000);
    var cookie = name +
      '=' +
      encodeURIComponent(value) +
      ';expires=' +
      d.toUTCString() +
      ';path=/;SameSite=Lax';
    if (location.protocol === 'https:') {
      cookie += ';Secure';
    }
    document.cookie = cookie;
  }

  function getConsent() {
    var raw = getCookie(COOKIE_NAME);
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  // --- Consent expiry check ---
  function isConsentExpired(consent) {
    if (!consent || !consent.timestamp) return false;
    var lifetime = (config.settings.cookie_lifetime || 365) * 86400000;
    return (Date.now() - consent.timestamp) > lifetime;
  }

  function activateIframes(categories) {
    var placeholders = document.querySelectorAll('.ccm-iframe-placeholder[data-cc-category]');
    for (var i = 0; i < placeholders.length; i++) {
      var cat = placeholders[i].getAttribute('data-cc-category');
      if (categories[cat]) {
        var src = placeholders[i].getAttribute('data-cc-src');
        if (!src) continue;
        var iframe = document.createElement('iframe');
        iframe.src = src;
        // Copy dimensions from placeholder style
        var phStyle = placeholders[i].style;
        if (phStyle.width) iframe.style.width = phStyle.width;
        if (phStyle.height) iframe.style.height = phStyle.height;
        // Copy extra classes (minus our placeholder class)
        var cls = placeholders[i].className.replace('ccm-iframe-placeholder', '').trim();
        if (cls) iframe.className = cls;
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allowfullscreen', '');
        placeholders[i].parentNode.replaceChild(iframe, placeholders[i]);
      }
    }
  }

  function activateScripts(categories) {
    var scripts = document.querySelectorAll('script[type="text/plain"][data-cc-category]');
    for (var i = 0; i < scripts.length; i++) {
      var cat = scripts[i].getAttribute('data-cc-category');
      if (categories[cat]) {
        var newScript = document.createElement('script');
        for (var j = 0; j < scripts[i].attributes.length; j++) {
          var attr = scripts[i].attributes[j];
          if (attr.name === 'type') continue;
          newScript.setAttribute(attr.name, attr.value);
        }
        newScript.type = 'text/javascript';
        if (!scripts[i].src) {
          newScript.textContent = scripts[i].textContent;
        }
        scripts[i].parentNode.replaceChild(newScript, scripts[i]);
      }
    }
  }

  function saveConsent(categories) {
    var consentData = {};
    config.categories.forEach(function (cat) {
      consentData[cat.slug] = cat.is_required ? true : !!categories[cat.slug];
    });

    // DNT: force non-necessary to false
    if (isDNTEnabled()) {
      config.categories.forEach(function (cat) {
        if (!cat.is_required) {
          consentData[cat.slug] = false;
        }
      });
    }

    // Google Consent Mode v2 update
    gtagConsentUpdate(consentData);

    // Update the cookie interceptor so consented cookies are unblocked
    if (typeof window.__ccmUpdateConsent === 'function') {
      window.__ccmUpdateConsent(consentData);
    }

    fetch(config.restUrl + '/consent', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce,
      },
      body: JSON.stringify({ categories: consentData }),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.uuid) {
          var cookieValue = JSON.stringify(
            Object.assign({}, consentData, { uuid: data.uuid, timestamp: Date.now() })
          );
          setCookie(COOKIE_NAME, cookieValue, config.settings.cookie_lifetime);
        }
        // Reload page if consent was changed (not first-time)
        if (isConsentChange) {
          window.location.reload();
        }
      });

    if (!isConsentChange) {
      activateScripts(consentData);
      activateIframes(consentData);
    }
    removeBanner();
  }

  function removeBanner() {
    var el = document.getElementById('ccm-banner-wrapper');
    if (el) el.parentNode.removeChild(el);
    var overlay = document.getElementById('ccm-overlay');
    if (overlay) overlay.parentNode.removeChild(overlay);
    showSettingsButton();
  }

  function buildBanner(existingConsent) {
    var s = config.settings;
    var pos = s.position || 'bottom';
    var isReopen = !!existingConsent;
    isConsentChange = isReopen;

    if (pos === 'center') {
      var overlay = document.createElement('div');
      overlay.id = 'ccm-overlay';
      document.body.appendChild(overlay);
    }

    var wrapper = document.createElement('div');
    wrapper.id = 'ccm-banner-wrapper';
    wrapper.className = 'ccm-position-' + pos;

    var inner = document.createElement('div');
    inner.className = 'ccm-banner-inner';
    inner.style.backgroundColor = s.banner_bg_color;
    inner.style.color = s.banner_text_color;

    if (s.logo_url) {
      var logo = document.createElement('img');
      logo.className = 'ccm-logo';
      logo.src = s.logo_url;
      logo.alt = '';
      inner.appendChild(logo);
    }

    var title = document.createElement('h3');
    title.className = 'ccm-title';
    title.style.color = s.banner_text_color;
    title.textContent = isReopen ? (s.manage_title || 'Manage cookie settings') : s.banner_title;
    inner.appendChild(title);

    var text = document.createElement('p');
    text.className = 'ccm-text';
    text.textContent = isReopen
      ? (s.manage_text || 'Here you can change or withdraw your consent.')
      : s.banner_text;
    if (!isReopen && s.privacy_policy_url) {
      var policyLink = document.createElement('a');
      policyLink.href = s.privacy_policy_url;
      policyLink.className = 'ccm-policy-link';
      policyLink.textContent = ' ' + (s.policy_link_text || 'Read our privacy policy.');
      policyLink.target = '_blank';
      policyLink.rel = 'noopener';
      text.appendChild(policyLink);
    }
    inner.appendChild(text);

    // Consent info (date + ID) when reopening
    if (isReopen && existingConsent) {
      var consentInfo = document.createElement('div');
      consentInfo.className = 'ccm-consent-info';
      if (existingConsent.timestamp) {
        var d = new Date(existingConsent.timestamp);
        var locale = document.documentElement.lang || 'en';
        var dateStr = d.toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' })
          + ' - ' + d.toLocaleTimeString(locale);
        var dateLine = document.createElement('p');
        dateLine.innerHTML = '<strong>' + (s.consent_date_label || 'Consent date:') + '</strong> ' + dateStr;
        consentInfo.appendChild(dateLine);
      }
      if (existingConsent.uuid) {
        var idLine = document.createElement('p');
        idLine.innerHTML = '<strong>' + (s.consent_id_label || 'Your consent ID:') + '</strong> ' + existingConsent.uuid;
        consentInfo.appendChild(idLine);
      }
      inner.appendChild(consentInfo);
    }

    // DNT notice
    if (isDNTEnabled() && !isReopen) {
      var dntNotice = document.createElement('p');
      dntNotice.className = 'ccm-dnt-notice';
      dntNotice.textContent = s.dnt_notice || 'Your browser has Do Not Track enabled. Analytics and marketing cookies are automatically disabled.';
      inner.appendChild(dntNotice);
    }

    var details = document.createElement('div');
    details.className = 'ccm-details';
    details.style.display = isReopen ? 'block' : 'none';

    var checkboxes = {};

    config.categories.forEach(function (cat) {
      var row = document.createElement('div');
      row.className = 'ccm-category-row';

      var header = document.createElement('div');
      header.className = 'ccm-category-header';

      var label = document.createElement('label');
      label.className = 'ccm-category-label';

      var cb = document.createElement('input');
      cb.type = 'checkbox';
      if (existingConsent && existingConsent[cat.slug] !== undefined) {
        cb.checked = cat.is_required || !!existingConsent[cat.slug];
      } else {
        cb.checked = cat.is_required;
      }
      cb.disabled = cat.is_required;
      cb.setAttribute('data-slug', cat.slug);
      checkboxes[cat.slug] = cb;

      var span = document.createElement('span');
      span.textContent = cat.title;
      if (cat.is_required) {
        span.textContent += ' ' + (s.required_label || '(always required)');
      }

      label.appendChild(cb);
      label.appendChild(span);

      // Prevent checkbox clicks from toggling accordion
      cb.addEventListener('click', function (e) { e.stopPropagation(); });
      label.addEventListener('click', function (e) { e.stopPropagation(); });

      header.appendChild(label);

      var cookieCount = (cat.cookies && cat.cookies.length) || 0;
      var expandBody = document.createElement('div');
      expandBody.className = 'ccm-category-body';
      expandBody.style.display = 'none';

      if (cookieCount > 0) {
        var arrow = document.createElement('span');
        arrow.className = 'ccm-category-arrow';
        arrow.innerHTML = '&#9654;';
        header.appendChild(arrow);

        var countBadge = document.createElement('span');
        countBadge.className = 'ccm-cookie-count';
        countBadge.textContent = cookieCount + ' cookie' + (cookieCount !== 1 ? 's' : '');
        header.appendChild(countBadge);

        header.style.cursor = 'pointer';
        header.addEventListener('click', function () {
          var open = expandBody.style.display !== 'none';
          expandBody.style.display = open ? 'none' : 'block';
          arrow.innerHTML = open ? '&#9654;' : '&#9660;';
          row.classList.toggle('ccm-category-open', !open);
        });
      }

      row.appendChild(header);

      var desc = document.createElement('p');
      desc.className = 'ccm-category-desc';
      desc.textContent = cat.description;
      expandBody.appendChild(desc);

      if (cookieCount > 0) {
        var table = document.createElement('table');
        table.className = 'ccm-cookie-table';

        var thead = document.createElement('thead');
        var headRow = document.createElement('tr');
        ['Cookie', 'Leverantör', 'Syfte', 'Utgår'].forEach(function (t) {
          var th = document.createElement('th');
          th.textContent = t;
          headRow.appendChild(th);
        });
        thead.appendChild(headRow);
        table.appendChild(thead);

        var tbody = document.createElement('tbody');
        cat.cookies.forEach(function (cookie) {
          var tr = document.createElement('tr');
          [cookie.name, cookie.provider, cookie.purpose, cookie.expiry].forEach(function (val) {
            var td = document.createElement('td');
            td.textContent = val || '—';
            tr.appendChild(td);
          });
          tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        expandBody.appendChild(table);
      }

      row.appendChild(expandBody);
      details.appendChild(row);
    });

    inner.appendChild(details);

    var buttons = document.createElement('div');
    buttons.className = 'ccm-buttons';

    var acceptAll = document.createElement('button');
    acceptAll.className = 'ccm-btn ccm-btn-accept';
    acceptAll.textContent = s.accept_all_text;
    acceptAll.style.backgroundColor = s.primary_color;
    acceptAll.style.color = s.primary_text_color;
    acceptAll.addEventListener('click', function () {
      var all = {};
      config.categories.forEach(function (cat) {
        all[cat.slug] = true;
      });
      saveConsent(all);
    });

    var rejectAll = document.createElement('button');
    rejectAll.className = 'ccm-btn ccm-btn-reject';
    rejectAll.textContent = s.reject_all_text;
    rejectAll.style.backgroundColor = s.reject_bg_color;
    rejectAll.style.color = s.reject_text_color;
    rejectAll.addEventListener('click', function () {
      var required = {};
      config.categories.forEach(function (cat) {
        required[cat.slug] = cat.is_required;
      });
      saveConsent(required);
    });

    var settingsBtn = document.createElement('button');
    settingsBtn.className = 'ccm-btn ccm-btn-settings';
    settingsBtn.style.color = s.banner_text_color;
    settingsBtn.textContent = s.settings_text;
    settingsBtn.addEventListener('click', function () {
      var isVisible = details.style.display !== 'none';
      details.style.display = isVisible ? 'none' : 'block';
      saveBtn.style.display = isVisible ? 'none' : 'inline-block';
      settingsBtn.textContent = isVisible ? s.settings_text : 'Dölj inställningar';
    });

    var saveBtn = document.createElement('button');
    saveBtn.className = 'ccm-btn ccm-btn-save';
    saveBtn.textContent = s.save_text;
    saveBtn.style.backgroundColor = s.primary_color;
    saveBtn.style.color = s.primary_text_color;
    saveBtn.style.display = isReopen ? 'inline-block' : 'none';
    saveBtn.addEventListener('click', function () {
      var selected = {};
      config.categories.forEach(function (cat) {
        selected[cat.slug] = checkboxes[cat.slug].checked;
      });
      saveConsent(selected);
    });

    buttons.appendChild(acceptAll);
    buttons.appendChild(rejectAll);

    if (isReopen) {
      var withdrawBtn = document.createElement('button');
      withdrawBtn.className = 'ccm-btn ccm-btn-reject';
      withdrawBtn.textContent = s.withdraw_text || 'Withdraw consent';
      withdrawBtn.style.backgroundColor = s.reject_bg_color;
      withdrawBtn.style.color = s.reject_text_color;
      withdrawBtn.addEventListener('click', function () {
        var required = {};
        config.categories.forEach(function (cat) {
          required[cat.slug] = cat.is_required;
        });
        saveConsent(required);
      });
      buttons.appendChild(withdrawBtn);

      // "Göm detaljer" toggle for reopen mode
      var toggleDetails = document.createElement('button');
      toggleDetails.className = 'ccm-btn ccm-btn-settings';
      toggleDetails.style.color = s.banner_text_color;
      toggleDetails.textContent = s.hide_details_text || 'Hide details';
      toggleDetails.addEventListener('click', function () {
        var isVisible = details.style.display !== 'none';
        details.style.display = isVisible ? 'none' : 'block';
        saveBtn.style.display = isVisible ? 'none' : 'inline-block';
        toggleDetails.textContent = isVisible ? (s.show_details_text || 'Show details') : (s.hide_details_text || 'Hide details');
      });
      buttons.appendChild(toggleDetails);
    } else {
      buttons.appendChild(settingsBtn);
    }

    buttons.appendChild(saveBtn);
    inner.appendChild(buttons);
    wrapper.appendChild(inner);

    document.body.appendChild(wrapper);
  }

  function showSettingsButton() {
    var existing = document.getElementById('ccm-settings-float');
    if (existing) return;

    var btn = document.createElement('button');
    btn.id = 'ccm-settings-float';
    btn.setAttribute('aria-label', 'Cookie-inställningar');
    if (config.settings.cookie_icon) {
      var img = document.createElement('img');
      img.src = config.settings.cookie_icon;
      img.alt = 'Cookie-inställningar';
      img.style.width = '24px';
      img.style.height = '24px';
      btn.appendChild(img);
    } else {
      btn.textContent = '\u{1F36A}';
    }
    btn.addEventListener('click', function () {
      var banner = document.getElementById('ccm-banner-wrapper');
      if (banner) return;
      var consent = getConsent();
      buildBanner(consent);
    });
    document.body.appendChild(btn);
  }

  // Bind placeholder accept buttons to open the consent banner
  function bindIframePlaceholders() {
    document.addEventListener('click', function (e) {
      if (!e.target.classList.contains('ccm-iframe-accept')) return;
      e.preventDefault();
      // Open the consent banner (same as the floating settings button)
      var banner = document.getElementById('ccm-banner-wrapper');
      if (banner) return;
      var consent = getConsent();
      buildBanner(consent || undefined);
    });
  }

  // Initialize
  function init() {
    var consent = getConsent();

    // DNT: auto-reject on first visit
    if (!consent && isDNTEnabled()) {
      var dntConsent = {};
      config.categories.forEach(function (cat) {
        dntConsent[cat.slug] = cat.is_required;
      });
      // Still show banner but with DNT notice
    }

    if (consent && !isConsentExpired(consent)) {
      // DNT: enforce on every page load, not just first visit
      if (isDNTEnabled()) {
        config.categories.forEach(function (cat) {
          if (!cat.is_required) {
            consent[cat.slug] = false;
          }
        });
      }
      activateScripts(consent);
      gtagConsentUpdate(consent);
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
          activateIframes(consent);
          showSettingsButton();
        });
      } else {
        activateIframes(consent);
        showSettingsButton();
      }
      return;
    }

    // Consent expired or doesn't exist — show banner
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () {
        buildBanner();
      });
    } else {
      buildBanner();
    }

    bindIframePlaceholders();
  }

  init();
})();
