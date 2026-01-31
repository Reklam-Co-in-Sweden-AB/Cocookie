# Cookie Consent Manager

**GDPR-kompatibel samtyckes- och cookiehantering for WordPress**

Version 1.0.0 | GPL-2.0+

---

## Sammanfattning

Cookie Consent Manager ar ett WordPress-tillagg som ger fullstandig kontroll over cookies och samtycken pa din webbplats. Pluginet hanterar allt fran samtyckesbanner och skriptblockering till cookie-skanning, compliance-audit och automatisk policygenerering — utan beroende av tredjepartstjanster.

---

## Funktionsoversikt

### 1. Samtyckesbanner

En anpassningsbar banner som visas for besokare och later dem valja vilka cookie-kategorier de samtycker till.

**Funktioner:**
- Tre positioner: botten, topp eller center (modal med overlay)
- Anpassningsbara farger for bakgrund, text, knappar (primar och avvisa)
- Valfri logotyp ovanfor titeln
- Alla texter andringsbara (titel, brodtext, knapptexter)
- Expanderbara kategorier med lista over enskilda cookies
- Visa cookie-namn, leverantor, syfte och livslangd per kategori

**Samtyckesflode:**
- "Acceptera alla" — godkanner samtliga kategorier
- "Avvisa alla" — godkanner enbart nodvandiga cookies
- "Installningar" — oppnar detaljvy dar besokaren valjer per kategori
- "Spara installningar" — sparar det individuella valet
- Flytande cookie-knapp (🍪) for att ateromma installningar efter forsta valet
- Vid ateromme visas samtyckesdatum, samtyckes-ID och "Dra tillbaka samtycke"-knapp

**Tekniskt:**
- Samtycket sparas i cookien `cc_consent` som JSON med UUID, tidsstampel och kategori-status
- Konfigurerbar livslangd (1–730 dagar)
- Automatisk utgangskontroll — bannern visas igen nar samtycket lopt ut
- Fullt responsiv design for mobila enheter

---

### 2. Kategorihantering

Organisera cookies i kategorier som besokaren kan godkanna eller avvisa individuellt.

**Standardkategorier (skapas automatiskt):**
- **Nodvandiga** — markerad som obligatorisk, kan inte avvaljas
- **Analys** — for statistik och besoksanalys
- **Marknadsforing** — for annonsering och konverteringssprarning

**Anpassning:**
- Skapa egna kategorier med valfritt slug, titel och beskrivning
- Markera kategorier som obligatoriska
- Sorteringsordning for visning i bannern
- Redigera och ta bort kategorier (kaskadradering av tillhorande cookies)

---

### 3. Cookie-register

Registrera alla cookies som webbplatsen anvander, med detaljerad information.

**Per cookie:**
- Namn (identifierare)
- Kategori (koppling till samtyckeskategori)
- Leverantor (t.ex. Google Analytics, Facebook)
- Syfte (beskrivning)
- Livslangd (t.ex. "2 ar", "Session")

**Import/export:**
- Exportera hela registret som JSON
- Importera fran JSON-fil — matchar kategorier via slug
- Format: `[{name, category_slug, provider, purpose, expiry}]`

---

### 4. Automatisk skriptblockering

Skript som kraver samtycke blockeras automatiskt tills besokaren godkanner raatt kategori.

**Sa har fungerar det:**
1. Markera skript med attributet `data-cc-category`:
   ```html
   <script data-cc-category="analytics" src="https://www.googletagmanager.com/gtag/js"></script>
   ```
2. Pluginet andrar automatiskt `type` till `text/plain` via output buffering — skriptet laddas inte
3. Nar besokaren ger samtycke for kategorin andras `type` tillbaka till `text/javascript` — skriptet aktiveras

**Stoder bade:**
- Externa skript (via `src`)
- Inline-skript

---

### 5. Google Consent Mode v2

Fullstandig integration med Googles Consent Mode v2 for korrekt signalering till Google Analytics, Google Ads och Google Tag Manager.

**Implementering:**
- `gtag('consent', 'default', ...)` sker i `<head>` **fore** GTM/gtag.js laddas
- Aterbesokare far uppdaterade signaler direkt i `<head>` via server-side lasning av samtyckescookien — inget flimmer
- `gtag('consent', 'update', ...)` anropas nar besokaren andrar sitt val i bannern

**Signalmappning:**

| Kategori | Google-signaler |
|---|---|
| Nodvandig | `functionality_storage: granted`, `security_storage: granted` |
| Analys | `analytics_storage`, `personalization_storage` |
| Marknadsforing | `ad_storage`, `ad_user_data`, `ad_personalization` |

**Ytterligare GCM v2-funktioner:**
- `ads_data_redaction: true` — redigerar annonsklic-ID:n (t.ex. `gclid`) nar `ad_storage` ar nekat
- `url_passthrough: true` — mojliggor cookieless konverteringsmatning
- `wait_for_update: 500` — ger CMP tid att ladda fore signalering

---

### 6. Cookie-skanner

Skanna webbplatsen automatiskt for att hitta alla cookies som satts, och fa automatiska forslag pa kategori, leverantor och syfte.

**Process:**
1. Klicka "Starta skanning" i admin
2. Webbplatsen laddas i en dold iframe
3. Efter 3 sekunder lasas alla cookies
4. Varje cookie matchas mot 200+ kanda monster

**Kanda monster inkluderar:**

| Tjanst | Cookies |
|---|---|
| WordPress | `wordpress_*`, `wp-settings-*`, `PHPSESSID` |
| WooCommerce | `woocommerce_cart_hash`, `wc_cart_*`, `wc_fragments_*` |
| Google Analytics | `_ga`, `_ga_*`, `_gid`, `_gat`, `__utm*` |
| Google Ads | `_gcl_au`, `_gcl_aw`, `_gac_*` |
| Meta/Facebook | `_fbp`, `_fbc`, `fr`, `tr`, `datr` |
| Hotjar | `_hjid`, `_hjSession_*`, `_hjAbsoluteSessionInProgress` |
| LinkedIn | `li_sugr`, `lidc`, `bcookie`, `UserMatchHistory` |
| Cloudflare | `__cf_bm`, `cf_clearance`, `_cfruid` |
| Microsoft Clarity | `_clck`, `_clsk`, `CLID` |
| Matomo | `_pk_id.*`, `_pk_ses.*` |
| TikTok | `_ttp`, `tt_*` |
| Pinterest | `_pinterest_*`, `_epik` |
| ...och manga fler | 200+ monster totalt |

**Efter skanning:**
- Resultattabell med foreslagen kategori, leverantor och syfte
- Importera enskilda cookies eller alla pa en gang till cookie-registret
- Status visas (Importerad / Ej importerad)

---

### 7. Cookie-audit

Jamfor cookies som faktiskt satts pa webbplatsen mot ditt cookie-register for att identifiera compliance-problem.

**Tre resultatavsnitt:**

**Cookie-status (tabell):**
- Varje registrerad cookie visas med:
  - Forvantat beteende (ska sattas / kraver samtycke)
  - Om cookien hittades eller inte
  - Statusikon: gron (OK), orange (saknas), rod (overtradelse)

**Overtradelser:**
- Listar cookies som satts utan samtycke fran besokaren
- Dessa tillhor kategorier som inte ar markerade som obligatoriska
- Varje overtradelse kan blockeras direkt

**Okanda cookies:**
- Cookies som hittades men inte finns i registret
- Dessa bor undersokas och antingen registreras eller blockeras

---

### 8. Cookie-blockering

Blockera specifika cookies fran att sattas pa webbplatsen. Blockeringen ar aktiv pa tva niver:

**Niva 1 — Interceptor (forhindrar):**
- Overskriver `document.cookie`-settern via `Object.defineProperty`
- Nar ett skript forsoker satta en blockerad cookie nekas skrivningen helt
- Kors i `<head>` fore alla andra skript — inget slipper igenom

**Niva 2 — Radering (sanerar):**
- Raderar server-satta cookies (fran `Set-Cookie`-headers) som JavaScript inte kan forhindra
- Kors vid tre tillfallen: direkt i `<head>`, vid `DOMContentLoaded`, och periodiskt i 10 sekunder
- Testar flera sokvagar och domanvarianter for sakert borttagande

**Administrering:**
- Blockera cookies direkt fran audit-resultat (klicka "Blockera")
- Blockerade cookies listas i en egen sektion med "Avblockera"-knappar
- Blocklistan lagras i databasen och passas bade till `<head>`-snippet och frontend-konfigurationen

---

### 9. Samtyckes-logg

Sparar varje samtyckesbeslut for dokumentation och compliance.

**Loggar foljande:**
- Unikt samtyckes-ID (UUID v4)
- IP-hash (SHA256, envags — kan inte aterstellas)
- Webblasarinfo (forsta 500 tecken)
- Kategori-status per samtycke (JSON: `{necessary: true, analytics: false, ...}`)
- Tidsstampel

**I admin:**
- Paginerad tabell (20 per sida)
- Visar samtyckes-ID, IP-hash (forsta 12 tecken), kategoristatus med farger, webblasare och datum

**Integritet:**
- Ingen personligt identifierbar information sparas
- IP-adresser hashas med SHA256 + WordPress-salt — kan inte aterstellas till klartext
- Uppfyller GDPR:s krav pa dokumentation av samtycken (Art. 7.1)

---

### 10. Statistik

Oversikt over samtyckesbeslut pa webbplatsen.

**Overblickskort:**
- Totalt antal registrerade samtycken

**Kategoritabell:**
- Acceptansgrad per kategori (procent)
- Antal godkanda vs avvisade
- Visuell stapel for varje kategori
- Obligatoriska kategorier markerade

**30-dagars diagram:**
- Stapeldiagram som visar antal samtycken per dag de senaste 30 dagarna
- Ger oversikt over trender (t.ex. okningar efter kampanjer)

---

### 11. Policygenerator

Genererar utkast till integritetspolicy och cookiepolicy pa svenska, baserat pa webbplatsens faktiska cookie-data.

**Foretagsinformation:**
- Foretagsnamn, organisationsnummer, adress, e-post, telefon
- Dataskyddsombud (DPO) — valfritt: namn och e-post

**Integritetspolicy innehaller:**
1. Personuppgiftsansvarig
2. Dataskyddsombud (om angivet)
3. Typer av uppgifter som samlas in
4. Rattslig grund (Art. 6 GDPR — berattigat intresse och samtycke)
5. Cookies och sparningstekniker
6. Tredjeparter (automatiskt genererad lista fran registrerade leverantorer)
7. Dina rattigheter (atakomst, ratelse, radering, begransning, dataportabilitet, invandning, atertagande av samtycke)
8. Tillsynsmyndighet (IMY)
9. Andringar i policyn

**Cookiepolicy innehaller:**
1. Vad ar cookies?
2. Hur vi anvander cookies
3. Hantera dina samtycken
4. Cookies vi anvander (grupperat per kategori med tabell)
5. Tredjepartscookies
6. Dina rattigheter
7. Kontakt

**Sidgenerering:**
- Skapar WordPress-sidor som utkast
- Uppdaterar befintlig sida om den redan finns
- Forhandsvisning direkt i admin med visa/dolj-toggle

---

### 12. Do Not Track (DNT)

Respekterar webblasarens Do Not Track-installning.

- Detekterar `navigator.doNotTrack === '1'`
- Tvingar icke-nodvandiga kategorier till `false`
- Visar meddelande i bannern: "Din webblasare har Do Not Track aktiverat..."
- Bannern visas fortfarande men med begransade val

---

### 13. Shortcode

**`[ccm_cookie_list]`** — Visar en tabell med alla registrerade cookies, grupperade per kategori.

Anvand pa din cookiepolicy-sida for att automatiskt halla cookie-deklarationen uppdaterad:

```
Namn        | Leverantor       | Syfte                        | Livslangd
_ga         | Google Analytics | Unik besokaridentifierare    | 2 ar
_fbp        | Meta (Facebook)  | Sparar besok for annonsering | 3 manader
```

---

### 14. Import och export

**Cookies:**
- Exportera alla cookies som JSON-fil (`ccm-cookies-YYYY-MM-DD.json`)
- Importera fran JSON — matchar kategorier via slug
- Mojliggor overflyttning mellan webbplatser

---

## REST API

Alla admin-endpoints kraver `manage_options`-behorighet och WordPress REST-nonce.

| Metod | Endpoint | Atkomst | Beskrivning |
|-------|----------|---------|-------------|
| GET | `/cc/v1/config` | Publik | Hamta banner-konfiguration |
| POST | `/cc/v1/consent` | Publik | Spara besokares samtycke |
| POST | `/cc/v1/scan` | Admin | Skanna cookies |
| POST | `/cc/v1/scan/import` | Admin | Importera enskild cookie fran skanning |
| POST | `/cc/v1/scan/import-all` | Admin | Importera alla skannade cookies |
| POST | `/cc/v1/audit` | Admin | Kor cookie-audit |
| POST | `/cc/v1/audit/block` | Admin | Blockera en cookie |
| POST | `/cc/v1/audit/unblock` | Admin | Avblockera en cookie |

---

## Sakerhet

- **Nonce-verifiering** pa alla formularsandningar och REST-anrop
- **Behorighets-check** (`manage_options`) pa alla admin-funktioner
- **Input-sanering** med `sanitize_text_field()`, `sanitize_textarea_field()`, `sanitize_hex_color()`, `esc_url_raw()`
- **Output-escapning** med `esc_html()`, `esc_attr()`, `esc_url()`
- **IP-hashning** med SHA256 + salt — envagshasning utan aterstallarbarhet
- **Ingen PII** lagras i klartext
- **CSRF-skydd** via WordPress-nonces

---

## Tekniska krav

- WordPress 5.0+
- PHP 7.4+
- MySQL / MariaDB
- Inga externa beroenden — allt ar vanilla JavaScript och WordPress-API:er

---

## Administrationsmeny

Pluginet registreras under **Installningar > Cookie Consent** med foljande flikar:

| Flik | Funktion |
|------|----------|
| Kategorier | Skapa/redigera/ta bort cookie-kategorier |
| Cookies | Registrera cookies, import/export |
| Samtyckes-logg | Se alla samtycken med paginering |
| Statistik | Acceptansgrader och 30-dagars trend |
| Scanner | Automatisk cookie-skanning |
| Cookie-audit | Compliance-granskning med blockering |
| Policyer | Generera integritetspolicy och cookiepolicy |
| Installningar | Banner-utseende, farger, texter, logotyp |
