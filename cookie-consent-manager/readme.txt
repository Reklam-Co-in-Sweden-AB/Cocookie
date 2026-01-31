=== Cookie Consent Manager ===
Contributors: cookie-consent-manager
Tags: cookie, consent, gdpr, privacy, banner
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+

Server-side cookie consent management for WordPress. GDPR-compliant banner with category-based consent.

== Description ==

Cookie Consent Manager handles cookie consent entirely within WordPress — no external services required. It provides a customizable consent banner, category-based cookie management, and a full consent log.

Features:

* Category-based consent (necessary, analytics, marketing, custom)
* Automatic script blocking via `data-cc-category` attributes
* REST API for consent storage
* Server-side consent logging with IP hashing
* Customizable banner text, colors, and position
* Admin UI for managing categories, cookies, and viewing consent logs
* GDPR-compliant: necessary cookies cannot be disabled

== Installation ==

1. Upload the `cookie-consent-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Settings → Cookie Consent to configure

== Usage ==

To block a third-party script until consent is given, change its type and add a category attribute:

`<script type="text/plain" data-cc-category="analytics" src="https://example.com/analytics.js"></script>`

The plugin will automatically activate the script when the visitor consents to the matching category.

== Changelog ==

= 1.0.0 =
* Initial release
