# CoCookie 2.1.0 — GDPR-fix: Okategoriserade cookies

**Datum:** 2026-04-16
**Version:** 2.1.0
**Författare:** Emelie Överstam
**Status:** Designspec — godkänd för implementering

## Bakgrund

En extern audit av en produktionssajt som använder CoCookie rapporterade att 31 cookies listas som "nödvändiga", varav 20+ har okänt syfte. Bland dem återfinns Beaver Builder-debugcookies (`fl-builder-settings`, `fl-cache-updater`, `fl-assistant`), Stripe-cookies och `i18next`-språkcookies. Detta är en GDPR-risk eftersom kategorin "nödvändiga" är den enda som får sättas utan besökarens samtycke.

## Rotorsak

Kodbasens kategoriseringslogik defaultar okända cookies till `necessary`. Buggen finns på två ställen:

1. `includes/models/class-cocookie-cookie-patterns.php:77-82` (modern, används aktivt)
2. `includes/class-cookie-scanner.php:242-246` (legacy, men fortfarande exekverbar)

```php
// Dagens felaktiga fallback:
return array(
    'category_slug' => 'necessary',
    'provider'      => 'Okänd',
    'purpose'       => 'Syftet med denna cookie är okänt. Granska manuellt.',
);
```

Dessutom:
- `data/cookie-patterns.json` saknar regler för Stripe och i18next.
- Scannern exekverar i admin-sessionen och fångar därför Beaver Builder-debugcookies som aldrig läcker till anonyma besökare.
- `seed_categories()` skapar endast `necessary`, `analytics` och `marketing` — ingen fallback-kategori finns.

## Mål

1. Eliminera GDPR-risken: okända cookies ska inte längre hamna i `necessary`.
2. Öka pattern-täckning för kända tredjepartsleverantörer (Stripe, i18next).
3. Filtrera bort admin-only-cookies från publika scanrapporter.
4. Modernisera metadata (`readme.txt`, versioner) och deprekera legacy-kod.

## Icke-mål (framtida releases)

- Scanner som körs headless/anonymt istället för i admin-sessionen.
- Ny kolumn `is_admin_only` i scan-resultat-tabellen.
- Fullständig radering av `class-cookie-scanner.php` (planeras till 2.2.0).

## Beslutade val

| # | Beslut | Motivering |
|---|--------|------------|
| 1 | Befintliga installationer: migrera endast `cc_scan_results`, lämna `cc_cookies` orört | `cc_scan_results` är automatgenererat; `cc_cookies` kan innehålla medvetna admin-beslut |
| 2 | `unclassified` är opt-in-kategori (`is_required: 0`) | GDPR-korrekt utan att bryta sajter där admin inte hunnit granska |
| 3 | Synligt namn: "Okategoriserade" | Beskrivande, neutralt, inte alarmerande för besökare |
| 4 | Admin-only-listan: hårdkodad default + WP-filter | Flexibel för utvecklare utan overhead av separat JSON |
| 5 | Legacy-kod: deprekerad nu, raderas i 2.2.0 | Säker utfasning om tredjepartskod anropar den |
| 6 | Versionsbump: 2.0.7 → 2.1.0 | Minor bump enligt semver (nya features, inte endast bugfix) |

## Design

### Datamodell

Ny kategori i `seed_categories()` (`includes/database/class-cocookie-database.php`):

```php
array(
    'slug'        => 'unclassified',
    'title'       => 'Okategoriserade',
    'description' => 'Cookies som ännu inte granskats och klassificerats. Kräver samtycke tills de flyttats till rätt kategori.',
    'is_required' => 0,
    'sort_order'  => 99,
),
```

### Migrering

Ny metod `migrate_to_2_1_0()` i `includes/database/class-cocookie-migrator.php`:

1. Idempotent insert av `unclassified`-kategorin i `cc_categories` (endast om slug saknas).
2. Uppdatera `cc_scan_results` där `suggested_category = 'necessary'` AND `suggested_provider = 'Okänd'` → `suggested_category = 'unclassified'`.
3. Lämna `cc_cookies` orört.
4. Anropa `CoCookie_Cookie_Patterns::clear_cache()` för att invalidera patterns-transienten.
5. Sätt `set_transient( 'cocookie_show_rescan_notice', 1, DAY_IN_SECONDS )` → admin-notis visas nästa gång adminsida laddas.

Triggern finns redan i `ensure_up_to_date()` — ingen ändring behövs där utöver att version `2.1.0` läggs till i jämförelselistan.

### Kategoriserings-fallback

Både `CoCookie_Cookie_Patterns::match()` och `CCM_Cookie_Scanner::classify_cookie()` ändrar fallback:

```php
return array(
    'category_slug' => 'unclassified',  // ← tidigare 'necessary'
    'provider'      => __( 'Okänd', 'cocookie' ),
    'purpose'       => __( 'Syftet med denna cookie är okänt. Granska manuellt och flytta till rätt kategori.', 'cocookie' ),
    'duration'      => '',
);
```

Legacy-metoden markeras `@deprecated 2.1.0` med hänvisning till modern klass.

### Nya patterns

Läggs till i `data/cookie-patterns.json`, `"version"` bumpas `2.0.0` → `2.1.0`:

- `^__stripe_mid$` — Stripe, necessary, 1 år
- `^__stripe_sid$` — Stripe, necessary, 30 minuter
- `^stripe\.csrf$` — Stripe, necessary, session
- `^i18nextLng$` — i18next, necessary, permanent
- `^i18next$` — i18next, necessary, permanent

### Admin-only-filter

Ny privat metod i `includes/api/class-cocookie-rest-scanner.php`:

```php
private static function is_admin_only_cookie( $name ) {
    $default = array(
        'fl-builder-settings',
        'fl-cache-updater',
        'fl-assistant',
        'fl-asset-cache',
    );
    $admin_only = apply_filters( 'cocookie_admin_only_cookies', $default );
    foreach ( $admin_only as $needle ) {
        if ( stripos( $name, $needle ) === 0 ) {
            return true;
        }
    }
    return false;
}
```

Anropas i scanningens `foreach`-loop innan kategorisering. Matchande cookies hoppas över helt — skrivs inte till `cc_scan_results` och syns aldrig i admin-UI.

### Admin-notis

I admin-boot (lämpligen `class-cocookie-admin.php`):

```php
if ( get_transient( 'cocookie_show_rescan_notice' ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p>' . esc_html__( 'CoCookie 2.1.0: En ny kategori "Okategoriserade" har lagts till. Tidigare okända cookies har flyttats dit. Vi rekommenderar en ny scan och granskning.', 'cocookie' ) . '</p>';
        echo '</div>';
    } );
    // Tas bort när användaren dismissar eller efter TTL
}
```

### Versions- och metadatauppdateringar

- `cookie-consent-manager.php`: plugin-header `Version: 2.1.0` + konstant `COCOOKIE_VERSION = '2.1.0'`
- `data/cookie-patterns.json`: `"version": "2.1.0"`
- `readme.txt`:
  - `Stable tag: 2.1.0` (tidigare `1.0.0`)
  - `Tested up to: 6.7` (tidigare `6.4`)
  - Ny changelog-sektion `= 2.1.0 =`

## Tester och verifiering

Manuell verifiering på testsajt innan release:

1. **Ren installation:** `unclassified` finns i `cc_categories` efter aktivering.
2. **Uppgradering från 2.0.7:** migrator körs, `unclassified` läggs till, `cc_scan_results` uppdateras, admin-notis visas.
3. **Scanresultat:**
   - Okänd cookie → `unclassified` (ej `necessary`).
   - Stripe-cookie → `necessary` med korrekt provider.
   - `fl-builder-settings` → filtreras bort helt.
4. **Banner:** "Okategoriserade" visas som egen toggle, av som default.
5. **Google Consent Mode v2:** samtycke för `unclassified` orsakar inga GCM-uppdateringar.

## Risker

| # | Risk | Mitigering |
|---|------|------------|
| R1 | `cc_cookies` behåller manuella `necessary+Okänd`-rader | Admin-notis uppmanar till granskning |
| R2 | Bannerns GCM v2-mappning kanske inte hanterar ny kategori | Verifieras i `cocookie-banner.js` innan implementation |
| R3 | Legacy `CCM_Cookie_Scanner` kan anropas från andra filer | Grep innan deprekering, behåll fungerande fallback |

## Git-strategi

Commits direkt på `CoCookie-2.0`-branchen, i ordning:

1. Lägg till `unclassified`-kategori (seed + migrator + cache-invalidation)
2. Byt kategoriserings-default i båda matchers, deprekera legacy
3. Lägg till Stripe- och i18next-patterns
4. Admin-only-filter i REST-scanner
5. Admin-notis efter migrering
6. Bumpa till 2.1.0, uppdatera readme
