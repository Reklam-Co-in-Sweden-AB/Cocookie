# Central Dashboard — Koncept

## Syfte

En central tjänst där alla kunder (webbplatser) som kör Cookie Consent Manager
rapporterar sin compliance-status. Dashboarden ger en samlad bild av:

- Vilka kunder som använder tjänsten
- Compliance-status per kund (godkänd / varningar / överträdelser)
- Audit-resultat (okända cookies, cookies utan samtycke)
- Konfigurationsstatus (har de consent mode, blockeringar, policyer?)
- Trender över tid

---

## GDPR-roller och ansvarsfördelning

### Vem är vad?

```
┌─────────────────────────────────────────────────┐
│  Slutanvändare (besökare)                       │
│  = Registrerad person (data subject)            │
└──────────────┬──────────────────────────────────┘
               │ besöker
┌──────────────▼──────────────────────────────────┐
│  Kunden (webbplatsägaren)                       │
│  = Personuppgiftsansvarig (data controller)     │
│  Ansvarar för: samtyckesinsamling, cookie-      │
│  deklaration, integritetspolicy, rättslig grund │
└──────────────┬──────────────────────────────────┘
               │ använder pluginet, rapporterar status
┌──────────────▼──────────────────────────────────┐
│  Vi (CCM Central)                               │
│  = Personuppgiftsbiträde (data processor)       │
│  Ansvarar för: säker behandling, DPA,           │
│  dataminimering, inga egna ändamål              │
└─────────────────────────────────────────────────┘
```

### Juridiska krav

**Personuppgiftsbiträdesavtal (DPA/PUB):**
- Krävs mellan oss och varje kund (Art. 28 GDPR)
- Specificerar: vilken data vi behandlar, ändamål, lagringstid, säkerhetsåtgärder
- Kunden förblir ansvarig — vi behandlar på deras instruktioner

**Dataminimering (Art. 5.1c):**
- Vi får INTE ta emot personuppgifter från kundernas besökare
- Ingen IP, inget samtyckes-UUID, inga cookies-värden, inget user-agent
- Enbart aggregerad/anonymiserad status-data

**Ändamålsbegränsning (Art. 5.1b):**
- Data används enbart för compliance-övervakning
- Inte för marknadsföring, profilering eller vidareförsäljning

---

## Vad som FÅR rapporteras (säkert)

Följande data innehåller inga personuppgifter och kan skickas utan risk:

```json
{
  "site_id": "sha256-hash-av-domän",
  "domain": "example.com",
  "report_date": "2026-01-29",
  "plugin_version": "1.0.0",
  "wp_version": "6.7",
  "php_version": "8.2",

  "configuration": {
    "categories_count": 3,
    "cookies_registered": 14,
    "has_privacy_policy": true,
    "has_cookie_policy": true,
    "gcm_enabled": true,
    "banner_position": "bottom",
    "cookie_lifetime_days": 365,
    "blocked_cookies_count": 2,
    "dnt_supported": true
  },

  "audit": {
    "last_audit_date": "2026-01-28",
    "registered_cookies": 14,
    "found_cookies": 18,
    "ok_count": 10,
    "warning_count": 2,
    "violation_count": 2,
    "unknown_count": 4,
    "violations": [
      {"cookie_name": "_fbp", "category": "Marknadsföring"},
      {"cookie_name": "_ga", "category": "Analys"}
    ],
    "unknown_cookies": ["custom_track", "ab_test_id", "src_ref", "vid"]
  },

  "consent_stats": {
    "total_consents": 4521,
    "last_30_days": 312,
    "acceptance_rates": {
      "analytics": 0.62,
      "marketing": 0.34
    }
  }
}
```

## Vad som INTE får rapporteras

| Data | Varför inte |
|------|-------------|
| Samtyckes-UUID | Identifierare kopplad till individ |
| IP-hash | Indirekt personuppgift (pseudonymiserad) |
| User-agent | Kan bidra till fingerprinting |
| Cookie-värden | Kan innehålla sessions-ID, tokens |
| Enskilda samtyckesloggar | Personuppgifter per besökare |
| Besökarantal | Kan vara känsligt för kunden |

---

## Arkitektur

### Översikt

```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Kund A      │    │  Kund B      │    │  Kund C      │
│  WP + CCM    │    │  WP + CCM    │    │  WP + CCM    │
└──────┬───────┘    └──────┬───────┘    └──────┬───────┘
       │                   │                   │
       │  HTTPS POST       │                   │
       │  (daglig rapport) │                   │
       ▼                   ▼                   ▼
┌─────────────────────────────────────────────────────┐
│                  CCM Central API                     │
│                                                      │
│  POST /api/v1/report    — Ta emot statusrapport      │
│  GET  /api/v1/sites     — Lista alla kunder          │
│  GET  /api/v1/site/{id} — Detalj per kund            │
│  GET  /api/v1/overview  — Samlad compliance-vy       │
│  GET  /api/v1/alerts    — Aktiva varningar            │
└──────────────────────────┬──────────────────────────┘
                           │
                           ▼
                ┌─────────────────────┐
                │  Central Dashboard   │
                │  (webbgränssnitt)    │
                └─────────────────────┘
```

### Komponenter

**1. Plugin-tillägg (i CCM-pluginet):**
- Ny klass: `CCM_Central_Reporter`
- WP Cron-jobb: daglig rapport (eller manuell "Skicka rapport"-knapp)
- API-nyckel per kund för autentisering
- Konfigureras i Inställningar-fliken (Central API URL + API-nyckel)
- Opt-in: kunden måste aktivt aktivera rapportering

**2. Central API (separat tjänst):**
- REST API som tar emot rapporter
- Datalagring (databas med site-rapporter)
- Autentisering via API-nyckel
- Rate limiting

**3. Dashboard (webbgränssnitt):**
- Inloggning för CCM-administratörer
- Översiktssida med alla kunder
- Detaljvy per kund
- Varningssystem

---

## Dashboard-vyer

### Översikt (alla kunder)

```
┌─────────────────────────────────────────────────────────┐
│  Cookie Consent Manager — Central Dashboard              │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐        │
│  │   12   │  │    8   │  │    3   │  │    1   │        │
│  │ Kunder │  │   OK   │  │Varning │  │Kritisk │        │
│  └────────┘  └────────┘  └────────┘  └────────┘        │
│                                                          │
│  Kund               Status    Övertr.  Okända  Senaste  │
│  ─────────────────────────────────────────────────────   │
│  ● example.com      ✅ OK       0       1    idag       │
│  ● shop.se          ⚠️ Varning  0       4    idag       │
│  ● blog.nu          🔴 Kritisk  2       3    igår       │
│  ● startup.io       ✅ OK       0       0    idag       │
│  ● butik.se         ⚠️ Varning  1       2    2 dgr     │
│  ○ inaktiv.se       ⏸️ Inaktiv  —       —    30 dgr    │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

**Statusnivåer:**
- **OK** — Inga överträdelser, inga eller få okända cookies
- **Varning** — Okända cookies hittade, eller saknar policy/GCM
- **Kritisk** — Överträdelser: cookies sätts utan samtycke
- **Inaktiv** — Ingen rapport på >14 dagar

### Detaljvy per kund

```
┌─────────────────────────────────────────────────────────┐
│  shop.se — Detaljvy                                      │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Grundinfo                                               │
│  ───────────────────────────────                         │
│  Domän:           shop.se                                │
│  Plugin-version:  1.0.0                                  │
│  WordPress:       6.7                                    │
│  Senaste rapport: 2026-01-29 08:00                       │
│                                                          │
│  Konfiguration                                           │
│  ───────────────────────────────                         │
│  Kategorier:         3          ✅                       │
│  Registrerade cookies: 14      ✅                       │
│  Integritetspolicy:  Ja        ✅                       │
│  Cookiepolicy:       Ja        ✅                       │
│  Consent Mode v2:    Ja        ✅                       │
│  Cookie-livslängd:   365 dagar ✅                       │
│  DNT-stöd:           Ja        ✅                       │
│  Blockerade cookies: 2         ✅                       │
│                                                          │
│  Audit-resultat                                          │
│  ───────────────────────────────                         │
│  Registrerade:  14                                       │
│  Hittade:       18                                       │
│  OK:            10  ✅                                   │
│  Saknas:         2  ⚠️                                   │
│  Överträdelser:  2  🔴 _fbp, _ga                        │
│  Okända:         4  ❓ custom_track, ab_test_id, ...     │
│                                                          │
│  Samtycken (30 dagar)                                    │
│  ───────────────────────────────                         │
│  Totalt:    312                                          │
│  Analys:    62% accepterar                               │
│  Marknad:   34% accepterar                               │
│                                                          │
│  Historik                                                │
│  ───────────────────────────────                         │
│  2026-01-29  ⚠️ Varning   4 okända cookies               │
│  2026-01-22  ✅ OK         0 överträdelser               │
│  2026-01-15  🔴 Kritisk   _fbp satt utan samtycke       │
│  2026-01-08  ✅ OK         0 överträdelser               │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### Varningar/Alerts

```
┌─────────────────────────────────────────────────────────┐
│  Aktiva varningar                                        │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  🔴 blog.nu — 2 överträdelser                            │
│     _fbp (Marknadsföring) sätts utan samtycke            │
│     _ga (Analys) sätts utan samtycke                     │
│     Sedan: 2026-01-28                                    │
│     [Visa detaljer]                                      │
│                                                          │
│  ⚠️ shop.se — Saknar uppdaterad cookiepolicy             │
│     Cookiepolicy-sida finns inte                         │
│     Sedan: 2026-01-15                                    │
│     [Visa detaljer]                                      │
│                                                          │
│  ⚠️ butik.se — 1 överträdelse                            │
│     _gcl_au (Marknadsföring) sätts utan samtycke         │
│     Sedan: 2026-01-27                                    │
│     [Visa detaljer]                                      │
│                                                          │
│  ⏸️ inaktiv.se — Ingen rapport på 30 dagar               │
│     Senaste rapport: 2025-12-30                          │
│     [Visa detaljer]                                      │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

---

## Compliance-regler (automatisk bedömning)

Dashboarden bedömer compliance automatiskt baserat på konfigurerbara regler:

### Kritiskt (röd) — Kräver omedelbar åtgärd

| Regel | Beskrivning |
|-------|-------------|
| Överträdelser finns | Cookies sätts utan samtycke för icke-nödvändig kategori |
| Ingen samtyckesbanner | Pluginet är installerat men bannern är inte aktiv |
| Samtycke sparas inte | Consent-endpoint svarar inte eller loggar inte |

### Varning (orange) — Bör åtgärdas

| Regel | Beskrivning |
|-------|-------------|
| Okända cookies > 3 | Många oregistrerade cookies — bör inventeras |
| Saknar integritetspolicy | Ingen policy-sida genererad |
| Saknar cookiepolicy | Ingen cookie-deklaration |
| Consent Mode v2 saknas | GCM inte aktiverat trots att Google-tjänster används |
| Cookie-livslängd > 390 dagar | Överstiger ICO:s rekommendation |
| Ingen audit senaste 30 dagarna | Audit bör köras regelbundet |
| Plugin-version föråldrad | Kör inte senaste versionen |

### OK (grön) — Uppfyller alla krav

| Krav | Kontroll |
|------|----------|
| 0 överträdelser | Inga cookies utan samtycke |
| ≤3 okända cookies | Registret är uppdaterat |
| Integritetspolicy finns | Sida genererad |
| Cookiepolicy finns | Sida genererad |
| GCM v2 aktivt | Google Consent Mode fungerar |
| Audit <14 dagar gammal | Regelbunden granskning |
| Aktuell plugin-version | Inga säkerhetsbrister |

---

## Notifieringar

### E-postnotifieringar (opt-in)

| Händelse | Mottagare | Frekvens |
|----------|-----------|----------|
| Ny överträdelse upptäckt | CCM-admin + kund | Omedelbart |
| Status ändrad (OK→Varning) | CCM-admin | Omedelbart |
| Veckosammanfattning | CCM-admin | Måndagar |
| Kund inaktiv >14 dagar | CCM-admin | En gång |
| Plugin-uppdatering tillgänglig | Kund | Vid release |

### Webhook-stöd (framtida)

```json
POST https://hooks.example.com/ccm-alert
{
  "event": "violation_detected",
  "site": "blog.nu",
  "details": {
    "cookie": "_fbp",
    "category": "marketing"
  },
  "timestamp": "2026-01-29T08:00:00Z"
}
```

---

## Plugin-sidan (i kundens WordPress)

### Ny sektion i Inställningar-fliken

```
Central rapportering
────────────────────
☑ Aktivera rapportering till CCM Central

API-URL:    [https://central.ccm-service.com/api/v1    ]
API-nyckel: [ccm_ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx   ]

Rapportfrekvens: [Dagligen ▼]

Status: ✅ Ansluten — Senaste rapport: 2026-01-29 08:00

[Skicka rapport nu]  [Testa anslutning]
```

### Vad kunden ser

I sin egen WordPress-admin kan kunden se:
- Att rapportering är aktiv
- Senaste rapportens tidpunkt
- Vilken data som skickas (transparent)
- Knapp för att inaktivera rapportering
- Länk till DPA/PUB-avtal

---

## GDPR-checklista för centrala dashboarden

### Innan lansering

- [ ] **Personuppgiftsbiträdesavtal (PUB/DPA)** — Mall klar, signeras av varje kund
- [ ] **Dataminimering verifierad** — Granska att ingen PII skickas i rapporter
- [ ] **Register över behandlingar (Art. 30)** — Dokumentera all databehandling
- [ ] **Säkerhetsåtgärder (Art. 32)** — Kryptering i transit (TLS), kryptering i vila, åtkomstkontroll
- [ ] **Lagringsperiod definierad** — T.ex. rapporter sparas 12 månader, sedan raderas
- [ ] **Radering vid avslut** — Process för att radera kunddata vid uppsägning
- [ ] **Incidenthantering** — Plan för personuppgiftsincident (Art. 33-34)
- [ ] **Opt-in, inte opt-out** — Rapportering ska vara aktivt val av kunden
- [ ] **Transparens** — Kunden ska se exakt vilken data som skickas
- [ ] **Databehandling inom EU/EES** — Server och lagring inom EU

### PUB/DPA ska innehålla

1. Ändamål med behandlingen (compliance-övervakning)
2. Typer av data (aggregerad status, inga personuppgifter)
3. Kategorier av registrerade (inga — enbart metadata om webbplatsen)
4. Lagringstid (12 månader efter senaste rapport)
5. Säkerhetsåtgärder (TLS, krypterad lagring, åtkomstkontroll)
6. Underbiträden (hosting-leverantör, specificera)
7. Radering vid avtalets upphörande
8. Rätt till granskning

---

## Datalagring (central databas)

### Tabeller

**sites:**
```
id              INT PRIMARY KEY
domain          VARCHAR(255) UNIQUE
api_key_hash    VARCHAR(64)
display_name    VARCHAR(255)
contact_email   VARCHAR(255)
status          ENUM('active','warning','critical','inactive')
created_at      DATETIME
last_report_at  DATETIME
```

**reports:**
```
id              INT PRIMARY KEY
site_id         INT FK → sites
report_date     DATE
plugin_version  VARCHAR(20)
wp_version      VARCHAR(20)

categories_count      INT
cookies_registered    INT
has_privacy_policy    BOOLEAN
has_cookie_policy     BOOLEAN
gcm_enabled           BOOLEAN
cookie_lifetime       INT
blocked_cookies_count INT

audit_ok_count        INT
audit_warning_count   INT
audit_violation_count INT
audit_unknown_count   INT

consent_total         INT
consent_last_30d      INT
acceptance_analytics  DECIMAL(3,2)
acceptance_marketing  DECIMAL(3,2)

created_at            DATETIME
```

**violations:**
```
id              INT PRIMARY KEY
report_id       INT FK → reports
cookie_name     VARCHAR(255)
category        VARCHAR(100)
first_seen_at   DATETIME
resolved_at     DATETIME NULL
```

**alerts:**
```
id              INT PRIMARY KEY
site_id         INT FK → sites
type            ENUM('violation','missing_policy','inactive','outdated','unknown_cookies')
severity        ENUM('critical','warning','info')
message         TEXT
created_at      DATETIME
resolved_at     DATETIME NULL
notified_at     DATETIME NULL
```

---

## Säkerhet (central tjänst)

### Autentisering

- API-nyckel per kund (genereras vid registrering)
- Nyckeln hashas i databasen — klartext visas bara en gång
- Alla anrop över HTTPS
- Rate limiting: max 10 rapporter per timme per nyckel

### Åtkomstkontroll (dashboard)

- Inloggning med e-post + lösenord + 2FA
- Roller: Admin (ser alla kunder), Kund (ser bara sin egen data)
- Sessionshantering med timeout

### Hosting

- EU-baserad hosting (GDPR Art. 44-49)
- Krypterad lagring
- Regelbundna säkerhetskopior
- Åtkomstlogg

---

## Möjliga utökningar

### Fas 2
- **Automatisk audit via headless browser** — Central tjänst kör Puppeteer/Playwright mot kundernas sajter för oberoende verifiering
- **Jämförelserapporter** — Visa kundens compliance jämfört med genomsnitt
- **PDF-rapporter** — Generera compliance-rapport för kunden att visa sin DPO/styrelse
- **Flerspråksstöd** — Dashboard på engelska, svenska, norska, danska, finska

### Fas 3
- **Automatiska åtgärder** — Push-konfiguration till plugin (t.ex. blockera cookie centralt)
- **Regelmotor** — Anpassningsbara compliance-regler per kund/bransch
- **Integrationer** — Slack, Teams, Jira för varningar
- **White-label** — Konsultbyråer kan erbjuda tjänsten under eget varumärke

### Fas 4
- **AI-klassificering** — Automatisk kategorisering av okända cookies
- **Regulatorisk bevakning** — Notifiera vid ändringar i GDPR-praxis eller IMY-beslut
- **Certifieringsprogram** — Compliance-badge som kunder kan visa på sin sajt
