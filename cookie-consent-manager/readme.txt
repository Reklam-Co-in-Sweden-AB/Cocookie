=== Cookie Consent Manager ===
Contributors: cookie-consent-manager
Tags: cookie, consent, gdpr, privacy, banner
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.2.0
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

= 2.2.0 =
* Ny funktion: opt-in samtyckeslänk till Google i cookiebannern. Aktiveras per sajt under CoCookie → Banner och visar en länk till Googles hantering av personuppgifter (business.safety.google/privacy) i samtyckesfliken.
* Uppfyller Googles EU-policy för användarsamtycke (EU UCP) för sajter som använder Googles annonstjänster (Google Ads, AdSense).
* Inledande text och länktext är redigerbara och översättningsbara — svenska och engelska standardtexter ingår.
* Länkens URL är hårdkodad och kan inte ändras av misstag.

= 2.1.0 =
* GDPR-fix: Okända cookies klassificeras nu som "Okategoriserade" istället för "Nödvändiga".
* Ny kategori "Okategoriserade" läggs till automatiskt via migrering.
* Befintliga okända cookies i scan-resultat flyttas till rätt kategori.
* Underhållsverktyg i Cookies-vyn: "Flytta okända till Okategoriserade" och "Städa bort försvunna cookies".
* Bulk-radera markerade cookies med "Markera alla"-checkbox.
* Heuristik-baserade rekommendationer för cookies — flaggar troliga inloggnings-, admin- och utvecklingscookies.
* Nytt filter: cocookie_cookie_heuristics för anpassning av heuristikreglerna.
* Nya patterns: Stripe (__stripe_mid, __stripe_sid, stripe.csrf), i18next (i18next, i18nextLng).
* Admin-only cookies från Beaver Builder filtreras bort från publika scans.
* Nytt filter: cocookie_admin_only_cookies för utökning av admin-only-listan.
* Högre kontrast på inaktiva tabbar i cookiebannern — WCAG AA-kompatibelt.
* Deprekerar CCM_Cookie_Scanner::classify_cookie() — använd CoCookie_Cookie_Patterns::match().

= 1.0.0 =
* Initial release
