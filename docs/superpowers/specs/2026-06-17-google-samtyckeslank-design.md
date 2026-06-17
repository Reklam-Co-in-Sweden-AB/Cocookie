# Design: Googles samtyckeslänk i CoCookie

**Datum:** 2026-06-17
**Status:** Godkänd design, redo för implementationsplan

## Bakgrund

Google har skickat ett policymeddelande (EU-policy för användarsamtycke, EU UCP) om att
sajter som kör Googles annonstjänster (Google Ads, AdSense, Analytics med annonsfunktioner)
måste informera användaren om hur Google hanterar personuppgifter, med en länk till
`https://business.safety.google/privacy/` i själva samtyckesmeddelandet eller på
integritetspolicy-sidan.

CoCookie äger samtyckesbannern och dess texter, så lösningen byggs in i pluginet som en
opt-in-funktion. Då kan alla sajter som kör CoCookie aktivera länken utan manuell
kodändring per sajt.

## Mål

- En **opt-in** kryssruta i banner-inställningarna, **av som standard** (alla sajter kör
  inte Google-annonsering).
- När den är på: en kort mening med inbäddad länk visas i samtyckesfliken.
- Inledande mening och länktext är **redigerbara** i admin, med svenska/engelska
  standardvärden via befintligt översättningsmönster.
- URL:en är **hårdkodad** och kan aldrig ändras av admin.

## Icke-mål (YAGNI)

- Ingen Google Consent Mode v2-integration (gtag-signalering) — endast den informativa länken.
- Ingen redigerbar URL.
- Ingen per-kategori- eller per-sida-logik. Länken visas/visas inte globalt.

## Datamodell

Tre nya nycklar i `cocookie_settings` (samma option som övriga bannerinställningar):

| Nyckel | Typ | Standard (sv) |
|---|---|---|
| `google_consent_enabled` | bool | `false` |
| `google_consent_intro` | text | `Vi använder Googles annonstjänster.` |
| `google_consent_link_text` | text | `Läs hur Google hanterar dina personuppgifter.` |

Engelska standardvärden (via översättningslogiken):
- `google_consent_intro`: `We use Google's advertising services.`
- `google_consent_link_text`: `Learn how Google uses your data.`

URL:en definieras som en konstant `COCOOKIE_GOOGLE_PRIVACY_URL =
'https://business.safety.google/privacy/'` i huvudfilen `cookie-consent-manager.php`,
tillsammans med övriga `COCOOKIE_*`-konstanter, och används direkt i mallen. Den ligger
aldrig i settings och kan inte redigeras.

## Komponenter och ändringsställen

Funktionen kräver ändringar på sju ställen eftersom inställningarna måste **sparas**,
**visas i admin**, **skickas till frontend**, **renderas** och **översättas**. Banner-
controllerns `handle_save()` bygger en helt ny settings-array — nycklar som inte finns i
formuläret tappas vid sparning. Därför måste varje nytt fält wiras in i hela kedjan.

### 1. `admin/controllers/class-cocookie-banner-controller.php` — `get_defaults()`
Lägg till de tre nya nycklarna med sina standardvärden (`google_consent_enabled => false`
och de två svenska texterna via `__()`).

### 2. `admin/controllers/class-cocookie-banner-controller.php` — `handle_save()`
Lägg till i den nya `$settings`-arrayen:
- `google_consent_enabled` → `! empty( $_POST['google_consent_enabled'] )` (checkbox → bool)
- `google_consent_intro` → `sanitize_text_field( $_POST['google_consent_intro'] ?? '' )`
- `google_consent_link_text` → `sanitize_text_field( $_POST['google_consent_link_text'] ?? '' )`

(Sparas i både `cocookie_settings` och legacy `ccm_settings`, som befintlig kod gör.)

### 3. `admin/views/new/banner.php` — "Texter"-sektionen
Ny rad i tabellen i Texter-sektionen:
- En kryssruta `google_consent_enabled` med tydlig label, t.ex. "Visa Googles
  samtyckeslänk (krävs om sajten använder Googles annonstjänster)".
- Två textfält `google_consent_intro` och `google_consent_link_text` med befintlig
  `regular-text`/`large-text`-stil.
- De två textfälten kan med fördel döljas/visas beroende på kryssrutans läge (liten
  vanilla-JS-toggle eller bara alltid synliga — implementationsdetalj, inget krav).
- En kort hjälptext under fältet som förklarar att länken pekar på
  `business.safety.google/privacy`.

### 4. `includes/api/class-cocookie-rest-config.php` — `build_config()`
Lägg till de tre nycklarna i `settings`-arrayen som skickas till frontend, med samma
`?? __( ... )`-fallback-mönster som övriga texter. `google_consent_enabled` castas till
bool.

### 5. `includes/api/class-cocookie-rest-config.php` — engelska defaults
I det engelska defaults-blocket (~rad 57–70) läggs `google_consent_intro` och
`google_consent_link_text` till med engelska värden.

### 6. `includes/api/class-cocookie-rest-config.php` — `swedish_defaults`
I `apply_translation()`/`swedish_defaults`-arrayen (~rad 278) läggs de två texterna till
med sina svenska värden, så att admin-anpassad text inte skrivs över när ett annat språk
är aktivt. (`google_consent_enabled` är en bool, ingår inte i översättningarna.)

### 7. `public/templates/banner.php` — rendering
Direkt efter den befintliga brödtext-/integritetspolicy-paragrafen
(`<p class="cocookie-banner__text">`, rad ~64–71) i samtyckesfliken, lägg till:

```php
<?php if ( ! empty( $s['google_consent_enabled'] ) ) : ?>
    <p class="cocookie-banner__google">
        <?php echo esc_html( $s['google_consent_intro'] ); ?>
        <a href="<?php echo esc_url( COCOOKIE_GOOGLE_PRIVACY_URL ); ?>"
           class="cocookie-banner__google-link" target="_blank" rel="noopener">
            <?php echo esc_html( $s['google_consent_link_text'] ); ?>
        </a>
    </p>
<?php endif; ?>
```

### CSS
Ny BEM-klass `.cocookie-banner__google` (och ev. `.cocookie-banner__google-link`) i
bannerns CSS-fil, i samma dämpade stil som den befintliga policy-länken. Liten textstorlek,
ärver färg via CSS-variablerna som redan finns.

## Säkerhet

- All output escapas: `esc_html()` på texter, `esc_url()` på den hårdkodade URL:en.
- Input saneras med `sanitize_text_field()`; kryssrutan tolkas som bool.
- Spara skyddas av befintlig `check_admin_referer()` + `current_user_can('manage_options')`.
- URL:en är en konstant och kan inte injiceras via settings.

## Testning / verifiering

Manuell verifiering på en WordPress-staging:
1. Kryssrutan av (default) → ingen Google-rad i bannern.
2. Kryssrutan på → raden visas i samtyckesfliken med korrekt länk till
   `business.safety.google/privacy`, öppnas i ny flik.
3. Redigera intro + länktext → ändringarna sparas och visas i bannern.
4. Byt sajtspråk till engelska → engelska standardtexter visas (om admin inte anpassat dem).
5. Spara bannerinställningar → övriga inställningar (färger, position m.m.) påverkas inte.
6. Kontroll att länken är tangentbordsnåbar och har tillräcklig kontrast (WCAG 2.1 AA).

## Bakåtkompatibilitet

- Befintliga sajter får `google_consent_enabled = false` (via default) → ingen synlig
  förändring förrän admin aktivt slår på funktionen.
- Värden sparas även i legacy `ccm_settings` enligt befintligt mönster.
