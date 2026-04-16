# CoCookie 2.1.0 Implementationsplan — Okategoriserade cookies

> **För agentiska arbetare:** KRÄVD SKILL: Använd superpowers:subagent-driven-development (rekommenderat) eller superpowers:executing-plans för att implementera denna plan task-för-task. Stegen använder checkbox-syntax (`- [ ]`) för spårning.

**Mål:** Eliminera GDPR-bugg där okända cookies defaultar till `necessary`. Lägg till `unclassified`-kategori, nya patterns (Stripe, i18next), admin-only-filter, migrering och version-bump.

**Arkitektur:** Incrementella commits enligt git-strategin i specen. Varje task producerar en självständig commit som kan verifieras manuellt innan nästa påbörjas. Ingen automatiserad testsvit finns — verifiering sker via SQL-inspektion och WordPress-admin.

**Tech Stack:** WordPress 6.x, PHP 8.x, MySQL/MariaDB, vanilla JS. Plugin följer WordPress Coding Standards.

**Specen:** `cookie-consent-manager/docs/specs/2026-04-16-gdpr-unclassified-category.md`

**Förutsättningar:** Testmiljö med WordPress + CoCookie 2.0.7 installerat. SSH/WP-CLI eller phpMyAdmin för databas-verifiering. Arbeta på branch `CoCookie-2.0`.

---

### Task 1: Lägg till `unclassified`-kategori (seed + migrator)

**Filer:**
- Modify: `cookie-consent-manager/includes/database/class-cocookie-database.php`
- Modify: `cookie-consent-manager/includes/database/class-cocookie-migrator.php`

- [ ] **Steg 1: Uppdatera `seed_categories()` för nya installationer**

Öppna `cookie-consent-manager/includes/database/class-cocookie-database.php` och hitta `seed_categories()`-metoden. Lägg till en fjärde post i `$defaults`-arrayen efter `marketing`:

```php
$defaults = array(
    array(
        'slug'        => 'necessary',
        'title'       => 'Nödvändiga',
        'description' => 'Dessa cookies är nödvändiga för att webbplatsen ska fungera och kan inte stängas av.',
        'is_required' => 1,
        'sort_order'  => 1,
    ),
    array(
        'slug'        => 'analytics',
        'title'       => 'Analys',
        'description' => 'Dessa cookies hjälper oss att förstå hur besökare använder webbplatsen.',
        'is_required' => 0,
        'sort_order'  => 2,
    ),
    array(
        'slug'        => 'marketing',
        'title'       => 'Marknadsföring',
        'description' => 'Dessa cookies används för att visa relevanta annonser.',
        'is_required' => 0,
        'sort_order'  => 3,
    ),
    array(
        'slug'        => 'unclassified',
        'title'       => 'Okategoriserade',
        'description' => 'Cookies som ännu inte granskats och klassificerats. Kräver samtycke tills de flyttats till rätt kategori.',
        'is_required' => 0,
        'sort_order'  => 99,
    ),
);
```

- [ ] **Steg 2: Lägg till migration `migrate_to_2_1_0` i migrator**

Öppna `cookie-consent-manager/includes/database/class-cocookie-migrator.php`. Lägg till en ny version-compare-check i `run()`-metoden efter raden för 2.0.0 (rad ~37):

```php
// Migration: lägg till unclassified-kategori och flytta okända scan-resultat
if ( version_compare( $current, '2.1.0', '<' ) ) {
    self::migrate_to_2_1_0();
}
```

Lägg sedan till själva migreringsmetoden i slutet av klassen, precis före den stängande klammern:

```php
/**
 * v2.1.0: Lägg till kategorin "unclassified" och flytta okända cookies dit.
 *
 * Idempotent — insert körs endast om slug saknas.
 * Uppdaterar cc_scan_results men lämnar cc_cookies orört (admin-kurerad data).
 */
private static function migrate_to_2_1_0() {
    global $wpdb;
    $categories_table    = $wpdb->prefix . 'cc_categories';
    $scan_results_table  = $wpdb->prefix . 'cc_scan_results';

    // 1. Säkerställ att unclassified-kategorin finns
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$categories_table} WHERE slug = %s",
        'unclassified'
    ) );

    if ( ! $exists ) {
        $wpdb->insert( $categories_table, array(
            'slug'        => 'unclassified',
            'title'       => 'Okategoriserade',
            'description' => 'Cookies som ännu inte granskats och klassificerats. Kräver samtycke tills de flyttats till rätt kategori.',
            'is_required' => 0,
            'sort_order'  => 99,
        ) );
    }

    // 2. Flytta okända scan-resultat från necessary till unclassified
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$scan_results_table}
         SET suggested_category = %s
         WHERE suggested_category = %s
           AND suggested_provider = %s",
        'unclassified',
        'necessary',
        'Okänd'
    ) );

    // 3. Invalidera pattern-cache så nya regler träder i kraft direkt
    if ( class_exists( 'CoCookie_Cookie_Patterns' ) ) {
        CoCookie_Cookie_Patterns::clear_cache();
    }

    // 4. Flagga för admin-notis
    set_transient( 'cocookie_show_rescan_notice', 1, DAY_IN_SECONDS );
}
```

- [ ] **Steg 3: Verifiera syntaktisk korrekthet**

Kör:
```bash
php -l /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/includes/database/class-cocookie-database.php
php -l /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/includes/database/class-cocookie-migrator.php
```
Förväntat: `No syntax errors detected` för båda filerna.

- [ ] **Steg 4: Verifiera i testmiljö (när version-bump är gjord i senare task)**

Detta steg verifieras helt efter Task 6 (efter version-bump till 2.1.0), men notera redan här:
- Aktivera pluginet på testsajt → inspektera `wp_cc_categories`: ny rad med `slug=unclassified, sort_order=99` ska finnas.
- Uppgradera från 2.0.7 → migrator körs via `admin_init` → `cc_scan_results` ska uppdateras, transient `cocookie_show_rescan_notice` ska sättas.

- [ ] **Steg 5: Commit**

```bash
git add cookie-consent-manager/includes/database/class-cocookie-database.php \
        cookie-consent-manager/includes/database/class-cocookie-migrator.php
git commit -m "$(cat <<'EOF'
Lägger till unclassified-kategori + migrering för 2.1.0

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: Byt default-kategori i båda matchers + deprekera legacy

**Filer:**
- Modify: `cookie-consent-manager/includes/models/class-cocookie-cookie-patterns.php:62-83`
- Modify: `cookie-consent-manager/includes/class-cookie-scanner.php:229-247`

- [ ] **Steg 1: Uppdatera moderna matchern**

Öppna `cookie-consent-manager/includes/models/class-cocookie-cookie-patterns.php`. Hitta `match()`-metoden (rad ~62). Byt fallback-arrayen från `'necessary'` till `'unclassified'`:

```php
public static function match( $name ) {
    $patterns = self::get_all();

    foreach ( $patterns as $entry ) {
        $regex = '/' . $entry['pattern'] . '/i';
        if ( preg_match( $regex, $name ) ) {
            return array(
                'category_slug' => $entry['category'],
                'provider'      => $entry['service'],
                'purpose'       => $entry['purpose_sv'],
                'duration'      => $entry['duration'] ?? '',
            );
        }
    }

    return array(
        'category_slug' => 'unclassified',
        'provider'      => __( 'Okänd', 'cocookie' ),
        'purpose'       => __( 'Syftet med denna cookie är okänt. Granska manuellt och flytta till rätt kategori.', 'cocookie' ),
        'duration'      => '',
    );
}
```

- [ ] **Steg 2: Uppdatera legacy-matchern och markera som deprecated**

Öppna `cookie-consent-manager/includes/class-cookie-scanner.php`. Hitta `classify_cookie()` (rad ~229). Lägg `@deprecated`-annotation i docblock och byt fallback-kategori:

```php
/**
 * Classify a cookie by name.
 *
 * @deprecated 2.1.0 Använd CoCookie_Cookie_Patterns::match() istället. Tas bort i 2.2.0.
 * @param string $name Cookie name.
 * @return array Associative array with category_slug, provider, purpose.
 */
public static function classify_cookie( $name ) {
    $known = self::get_known_cookies();

    foreach ( $known as $entry ) {
        if ( preg_match( $entry['pattern'], $name ) ) {
            return array(
                'category_slug' => $entry['category'],
                'provider'      => $entry['provider'],
                'purpose'       => $entry['purpose'],
            );
        }
    }

    return array(
        'category_slug' => 'unclassified',
        'provider'      => 'Okänd',
        'purpose'       => 'Syftet med denna cookie är okänt. Granska manuellt och flytta till rätt kategori.',
    );
}
```

- [ ] **Steg 3: Verifiera syntaktisk korrekthet**

Kör:
```bash
php -l /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/includes/models/class-cocookie-cookie-patterns.php
php -l /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/includes/class-cookie-scanner.php
```
Förväntat: `No syntax errors detected` för båda.

- [ ] **Steg 4: Kontrollera att inga andra filer förväntar sig `'necessary'` som default**

Kör:
```bash
cd /Users/emelieoverstam/Developer/CoCookie
grep -rn "category_slug.*necessary" cookie-consent-manager/ --include="*.php"
```
Förväntat: inga träffar utöver de patterns-definitioner vi redan känner till och rader med `'necessary'` som kategori för faktiskt nödvändiga cookies. Ingen fallback-logik ska vara beroende av `'necessary'` som default-värde.

- [ ] **Steg 5: Commit**

```bash
git add cookie-consent-manager/includes/models/class-cocookie-cookie-patterns.php \
        cookie-consent-manager/includes/class-cookie-scanner.php
git commit -m "$(cat <<'EOF'
Byter default-kategori från necessary till unclassified — fixar GDPR-bugg

Deprekerar CCM_Cookie_Scanner::classify_cookie() till förmån för CoCookie_Cookie_Patterns::match().

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Lägg till Stripe- och i18next-patterns

**Filer:**
- Modify: `cookie-consent-manager/data/cookie-patterns.json`

- [ ] **Steg 1: Bumpa version och updated-fält i JSON**

Öppna `cookie-consent-manager/data/cookie-patterns.json`. Ändra de två första nycklarna:

```json
{
    "version": "2.1.0",
    "updated": "2026-04-16",
    "patterns": [
```

- [ ] **Steg 2: Lägg till nya patterns i patterns-arrayen**

Lägg till följande 5 entries i `"patterns"`-arrayen. Lämpligen precis efter avsnittet för WordPress/WooCommerce och innan marketing-patterns — exakt placering spelar ingen roll eftersom matchning sker linjärt:

```json
{
    "pattern": "^__stripe_mid$",
    "category": "necessary",
    "service": "Stripe",
    "purpose_sv": "Bedrägeribekämpning och säker betalningssession.",
    "purpose_en": "Fraud prevention and secure payment session.",
    "duration": "1 år"
},
{
    "pattern": "^__stripe_sid$",
    "category": "necessary",
    "service": "Stripe",
    "purpose_sv": "Aktiv betalningssession.",
    "purpose_en": "Active payment session.",
    "duration": "30 minuter"
},
{
    "pattern": "^stripe\\.csrf$",
    "category": "necessary",
    "service": "Stripe",
    "purpose_sv": "CSRF-skydd för Stripe-betalning.",
    "purpose_en": "CSRF protection for Stripe payments.",
    "duration": "Session"
},
{
    "pattern": "^i18nextLng$",
    "category": "necessary",
    "service": "i18next",
    "purpose_sv": "Sparar besökarens språkval.",
    "purpose_en": "Stores visitor's language preference.",
    "duration": "Permanent"
},
{
    "pattern": "^i18next$",
    "category": "necessary",
    "service": "i18next",
    "purpose_sv": "i18next översättningsdata.",
    "purpose_en": "i18next translation data.",
    "duration": "Permanent"
},
```

Kom ihåg komma efter varje entry utom den sista i arrayen.

- [ ] **Steg 3: Validera JSON-syntax**

Kör:
```bash
python3 -m json.tool /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/data/cookie-patterns.json > /dev/null && echo "JSON OK"
```
Förväntat: `JSON OK`. Om du ser ett fel: leta efter extra komma, saknade citattecken eller felaktig escape av backslash.

- [ ] **Steg 4: Verifiera antalet patterns ökade med 5**

Kör:
```bash
python3 -c "import json; d=json.load(open('/Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/data/cookie-patterns.json')); print(f'version={d[\"version\"]}, patterns={len(d[\"patterns\"])}')"
```
Förväntat: `version=2.1.0, patterns=<tidigare_antal + 5>`.

- [ ] **Steg 5: Commit**

```bash
git add cookie-consent-manager/data/cookie-patterns.json
git commit -m "$(cat <<'EOF'
Lägger till patterns för Stripe och i18next

Bumpar pattern-filens version till 2.1.0.

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: Admin-only cookie-filter i REST-scannern

**Filer:**
- Modify: `cookie-consent-manager/includes/api/class-cocookie-rest-scanner.php`

- [ ] **Steg 1: Lägg till `is_admin_only_cookie()`-metod i klassen**

Öppna `cookie-consent-manager/includes/api/class-cocookie-rest-scanner.php`. Lägg till följande privata metod i klassen, förslagsvis precis före `handle_scan()`-metoden (rad ~78):

```php
/**
 * Kontrollerar om en cookie är admin-only och inte ska rapporteras för anonyma besökare.
 *
 * Admin-only-cookies (t.ex. Beaver Builders fl-* debugcookies) sätts endast när
 * admin är inloggad och läcker inte till publika besökare. Dessa filtreras bort
 * från scanresultat så de inte felaktigt listas som "cookies sajten sätter".
 *
 * Filtret cocookie_admin_only_cookies låter tredjepartskod utöka listan.
 *
 * @param string $name Cookie-namn.
 * @return bool True om cookien är admin-only.
 */
private static function is_admin_only_cookie( $name ) {
    $default = array(
        'fl-builder-settings',
        'fl-cache-updater',
        'fl-assistant',
        'fl-asset-cache',
    );

    /**
     * Filter: lista av prefix för admin-only cookies som aldrig ska listas i publika scans.
     *
     * @param array $list Lista med cookie-namnprefix.
     */
    $admin_only = apply_filters( 'cocookie_admin_only_cookies', $default );

    foreach ( (array) $admin_only as $needle ) {
        if ( ! is_string( $needle ) || '' === $needle ) {
            continue;
        }
        if ( 0 === stripos( $name, $needle ) ) {
            return true;
        }
    }

    return false;
}
```

- [ ] **Steg 2: Kalla filtret i scan-loopen**

I samma fil, leta upp `handle_scan()`-metoden. I `foreach ( $cookies as $cookie )`-loopen, lägg till filterkontrollen precis efter `$name = sanitize_text_field(...)` och `if ( empty( $name ) ) { continue; }`. Slutligt resultat runt rad ~91-100:

```php
foreach ( $cookies as $cookie ) {
    $name         = sanitize_text_field( $cookie['name'] ?? '' );
    $value_sample = sanitize_text_field( substr( $cookie['value'] ?? '', 0, 50 ) );
    $domain       = sanitize_text_field( $cookie['domain'] ?? '' );
    $storage_type = sanitize_text_field( $cookie['storage_type'] ?? 'cookie' );

    if ( empty( $name ) ) {
        continue;
    }

    // Hoppa över admin-only cookies — de läcker inte till publika besökare.
    if ( self::is_admin_only_cookie( $name ) ) {
        continue;
    }

    // Use JSON pattern matcher instead of hardcoded patterns
    $match = CoCookie_Cookie_Patterns::match( $name );

    // ... resten oförändrad ...
}
```

- [ ] **Steg 3: Verifiera syntaktisk korrekthet**

Kör:
```bash
php -l /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/includes/api/class-cocookie-rest-scanner.php
```
Förväntat: `No syntax errors detected`.

- [ ] **Steg 4: Commit**

```bash
git add cookie-consent-manager/includes/api/class-cocookie-rest-scanner.php
git commit -m "$(cat <<'EOF'
Filtrerar bort admin-only cookies från publika scans

Beaver Builders fl-*-cookies sätts bara när admin är inloggad och läcker inte till anonyma besökare. Lägger till filtret cocookie_admin_only_cookies för utökning.

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: Admin-notis efter migrering

**Filer:**
- Modify: `cookie-consent-manager/admin/class-cocookie-admin.php`

- [ ] **Steg 1: Registrera action för admin_notices i `init()`-metoden**

Öppna `cookie-consent-manager/admin/class-cocookie-admin.php`. I `init()`-metoden (rad ~20-26) lägg till en ny action-registrering:

```php
public static function init() {
    add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_global_assets' ) );
    add_action( 'admin_init', array( __CLASS__, 'maybe_redirect_to_wizard' ) );
    add_action( 'admin_init', array( __CLASS__, 'handle_run_wizard' ) );
    add_action( 'admin_init', array( __CLASS__, 'handle_check_update' ) );
    add_action( 'admin_notices', array( __CLASS__, 'maybe_show_rescan_notice' ) );
}
```

- [ ] **Steg 2: Lägg till `maybe_show_rescan_notice()`-metoden**

Lägg till följande metod i klassen, förslagsvis precis efter `init()`:

```php
/**
 * Visar en engångs-dismissibel admin-notis efter 2.1.0-migrering
 * som uppmanar till ny cookie-scan.
 */
public static function maybe_show_rescan_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! get_transient( 'cocookie_show_rescan_notice' ) ) {
        return;
    }

    $scan_url = admin_url( 'admin.php?page=cocookie-cookies' );

    printf(
        '<div class="notice notice-info is-dismissible"><p>%s <a href="%s">%s</a></p></div>',
        esc_html__( 'CoCookie 2.1.0: En ny kategori "Okategoriserade" har lagts till. Tidigare okända cookies har flyttats dit. Vi rekommenderar en ny scan och granskning.', 'cocookie' ),
        esc_url( $scan_url ),
        esc_html__( 'Granska cookies →', 'cocookie' )
    );

    // Ta bort transienten efter första visningen
    delete_transient( 'cocookie_show_rescan_notice' );
}
```

- [ ] **Steg 3: Verifiera syntaktisk korrekthet**

Kör:
```bash
php -l /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/admin/class-cocookie-admin.php
```
Förväntat: `No syntax errors detected`.

- [ ] **Steg 4: Commit**

```bash
git add cookie-consent-manager/admin/class-cocookie-admin.php
git commit -m "$(cat <<'EOF'
Visar admin-notis efter 2.1.0-migrering med uppmaning om ny cookie-scan

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: Version-bump till 2.1.0 och readme-uppdatering

**Filer:**
- Modify: `cookie-consent-manager/cookie-consent-manager.php:1-25`
- Modify: `cookie-consent-manager/readme.txt`

- [ ] **Steg 1: Bumpa plugin-header och konstant**

Öppna `cookie-consent-manager/cookie-consent-manager.php`. Ändra två rader (plugin-header ~rad 5 och `COCOOKIE_VERSION` ~rad 18):

Från:
```php
 * Version: 2.0.7
```
till:
```php
 * Version: 2.1.0
```

Från:
```php
define( 'COCOOKIE_VERSION', '2.0.7' );
```
till:
```php
define( 'COCOOKIE_VERSION', '2.1.0' );
```

- [ ] **Steg 2: Uppdatera `readme.txt` metadata**

Öppna `cookie-consent-manager/readme.txt`. Hitta raderna:

```
Tested up to: 6.4
...
Stable tag: 1.0.0
```

Ändra till:

```
Tested up to: 6.7
...
Stable tag: 2.1.0
```

- [ ] **Steg 3: Lägg till changelog-sektion i `readme.txt`**

Leta upp `== Changelog ==`-sektionen i samma fil. Lägg till en ny sektion högst upp (direkt efter `== Changelog ==`-rubriken):

```
= 2.1.0 =
* GDPR-fix: Okända cookies klassificeras nu som "Okategoriserade" istället för "Nödvändiga".
* Ny kategori "Okategoriserade" läggs till automatiskt via migrering.
* Befintliga okända cookies i scan-resultat flyttas till rätt kategori.
* Nya patterns: Stripe (__stripe_mid, __stripe_sid, stripe.csrf), i18next (i18next, i18nextLng).
* Admin-only cookies från Beaver Builder filtreras bort från publika scans.
* Nytt filter: cocookie_admin_only_cookies för utökning av admin-only-listan.
* Deprekerar CCM_Cookie_Scanner::classify_cookie() — använd CoCookie_Cookie_Patterns::match().
```

- [ ] **Steg 4: Verifiera version-bump**

Kör:
```bash
grep -n "Version:\|COCOOKIE_VERSION\|Stable tag" /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/cookie-consent-manager.php /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/readme.txt
```
Förväntat: alla tre rader visar `2.1.0`.

- [ ] **Steg 5: Verifiera PHP-syntax**

Kör:
```bash
php -l /Users/emelieoverstam/Developer/CoCookie/cookie-consent-manager/cookie-consent-manager.php
```
Förväntat: `No syntax errors detected`.

- [ ] **Steg 6: Commit**

```bash
git add cookie-consent-manager/cookie-consent-manager.php cookie-consent-manager/readme.txt
git commit -m "$(cat <<'EOF'
Bumpar till 2.1.0 + uppdaterar readme

Co-Authored-By: Claude Opus 4.6 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

### Task 7: Integrationsverifiering i testmiljö

**Filer:** inga kodändringar — endast manuell verifiering. Hoppa inte över detta steg.

- [ ] **Steg 1: Förbered testmiljö**

Installera CoCookie 2.0.7 (den redan publicerade versionen) på en testsajt. Kör en initial scan så att `cc_scan_results` innehåller minst en rad med `suggested_category = 'necessary'` och `suggested_provider = 'Okänd'` (skapa t.ex. en testcookie med ett unikt namn som inte matchar några patterns).

- [ ] **Steg 2: Ladda upp plugin-filer från aktuell branch**

Ersätt plugin-filerna på testsajten med innehållet från `cookie-consent-manager/`-mappen i denna branch (`CoCookie-2.0` efter alla ovanstående commits). Använd FTP/SSH/wp-admin-uppladdning — vilken väg som passar testmiljön.

- [ ] **Steg 3: Verifiera migreringskörning**

Ladda valfri adminsida i WordPress. Migrator ska köra via `admin_init` när den upptäcker versionsmismatch. Verifiera i databasen:

```sql
SELECT * FROM wp_cc_categories WHERE slug = 'unclassified';
SELECT option_value FROM wp_options WHERE option_name = 'cocookie_db_version';
SELECT * FROM wp_cc_scan_results WHERE suggested_category = 'unclassified';
```

Förväntat:
- Rad med `slug=unclassified`, `title=Okategoriserade`, `is_required=0`, `sort_order=99` existerar
- `cocookie_db_version` = `2.1.0`
- Tidigare okända cookies har `suggested_category = 'unclassified'`

- [ ] **Steg 4: Verifiera admin-notis**

Ladda om dashboarden i WordPress admin. En blå info-notis ska visas:
> "CoCookie 2.1.0: En ny kategori 'Okategoriserade' har lagts till..."

Klicka bort notisen (dismiss). Ladda om — notisen ska inte visas igen (transienten är borttagen).

- [ ] **Steg 5: Verifiera ny scan**

Kör en ny cookie-scan från CoCookie-admin. Säkerställ att:
- Okänd cookie som inte matchar någon pattern hamnar i `unclassified` (inte `necessary`).
- Stripe-cookies (testa genom att manuellt sätta `document.cookie = '__stripe_mid=test'` i browserns konsol innan scan) klassificeras som `necessary` med provider `Stripe`.
- Cookies som börjar med `fl-builder-settings`, `fl-cache-updater`, `fl-assistant` eller `fl-asset-cache` är helt borta från scan-resultatet (filtret körs).

- [ ] **Steg 6: Verifiera publikt cookie-banner**

Ladda en anonym besökarsession (inkognito-fönster) på testsajten. I cookie-bannern under "Detaljer"-fliken:
- "Okategoriserade" visas som en egen kategori med toggle.
- Togglen är AV som default (opt-in).
- Kategorin har beskrivningstexten från seed-raden.

- [ ] **Steg 7: Verifiera Google Consent Mode v2**

Om GA/GTM finns på testsajten: öppna browsers DevTools Console, acceptera "Okategoriserade" i bannern, och kontrollera att `gtag('consent', 'update', ...)`-anrop inte inkluderar någon ny okänd consent-nyckel. `unclassified` ska inte mappas mot `ad_storage`/`analytics_storage` (bekräftar att vi inte bryter GCM-integrationen).

- [ ] **Steg 8: Om något steg ovan failar**

Dokumentera fel, återställ (git revert eller reset), åtgärda och kör om hela testen. Inga oväntade fel ska accepteras innan release.

- [ ] **Steg 9: Push efter användargodkännande**

Om alla verifieringssteg passerat — fråga användaren innan push. När godkänt:

```bash
git push origin CoCookie-2.0
git tag v2.1.0
git push origin v2.1.0
```

Därefter bygg ny zip och skapa GitHub-release enligt tidigare flöde (se tidigare commits för exempel på zip-byggning).
