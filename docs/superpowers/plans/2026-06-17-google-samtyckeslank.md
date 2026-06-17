# Googles samtyckeslänk — Implementationsplan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lägga till en opt-in samtyckeslänk till `business.safety.google/privacy` i CoCookie-bannern, så sajter som kör Googles annonstjänster uppfyller Googles EU-policy för användarsamtycke.

**Architecture:** En ny bool-inställning (`google_consent_enabled`, av som standard) plus två redigerbara texter (`google_consent_intro`, `google_consent_link_text`) lagras i `cocookie_settings`. URL:en är en hårdkodad konstant. Inställningen wiras genom hela kedjan: persistens (banner-controller) → frontend-config (rest-config) → admin-UI (banner-vy) → rendering (banner-mall + CSS).

**Tech Stack:** PHP 8.x (WordPress plugin), vanilla JS, BEM CSS. Ingen byggprocess.

## Global Constraints

- **Språk:** Kommentarer/dokumentation på svenska med korrekt å/ä/ö. Funktions- och variabelnamn på engelska. All användarvänd text på svenska, översättbar via `__()` / `esc_html_e()` med textdomän `'cocookie'`.
- **Hårdkodad URL:** `https://business.safety.google/privacy/` — får ALDRIG vara redigerbar; ligger i konstant `COCOOKIE_GOOGLE_PRIVACY_URL`.
- **Standardtexter (låsta, ändra inte):**
  - sv intro: `Vi använder Googles annonstjänster.`
  - sv länktext: `Läs hur Google hanterar dina personuppgifter.`
  - en intro: `We use Google's advertising services.`
  - en länktext: `Learn how Google uses your data.`
- **Säkerhet:** Output escapas (`esc_html()`, `esc_attr()`, `esc_url()`). Input saneras (`sanitize_text_field()`, checkbox → bool via `! empty()`). Spara skyddas av befintlig `check_admin_referer()` + `current_user_can('manage_options')` — rör inte den logiken.
- **Bakåtkompatibilitet:** Settings sparas i både `cocookie_settings` och legacy `ccm_settings` (befintligt mönster — behåll det).
- **Standard av:** `google_consent_enabled` defaultar till `false`; befintliga sajter ser ingen förändring förrän admin slår på den.
- **Verifiering utan testharness:** Pluginet saknar PHPUnit/WP-testmiljö. Per ändrad PHP-fil körs `php -l <fil>` (förväntat: `No syntax errors detected`). Om `php` inte finns lokalt, hoppa lint-steget och förlita dig på den manuella staging-checklistan i slutet. Bygg INTE en testharness — det är utanför scope.
- **Alla sökvägar är relativa repo-roten** `/Users/emelieoverstam/Developer/CoCookie/`. Plugin-koden ligger under `cookie-consent-manager/`.

---

### Task 1: Persistens av de nya inställningarna

Lägger till de tre nycklarna i banner-controllerns defaults och spara-logik, så att värdena består vid sparning. (Controllern bygger en helt ny settings-array vid spara — nycklar som saknas i formuläret tappas.)

**Files:**
- Modify: `cookie-consent-manager/admin/controllers/class-cocookie-banner-controller.php` (`get_defaults()` ~rad 36-39, `handle_save()` ~rad 87)

**Interfaces:**
- Produces: tre nycklar i `cocookie_settings` — `google_consent_enabled` (bool), `google_consent_intro` (string), `google_consent_link_text` (string). Övriga tasks läser dessa.

- [ ] **Step 1: Lägg till de tre nycklarna i `get_defaults()`**

Ersätt raden `'cookie_icon'        => '',` (sista raden i defaults-arrayen, ~rad 38) med:

```php
			'cookie_icon'              => '',
			'google_consent_enabled'   => false,
			'google_consent_intro'     => __( 'Vi använder Googles annonstjänster.', 'cocookie' ),
			'google_consent_link_text' => __( 'Läs hur Google hanterar dina personuppgifter.', 'cocookie' ),
```

- [ ] **Step 2: Lägg till sanering i `handle_save()`**

Ersätt raden `'cookie_icon'        => esc_url_raw( $_POST['cookie_icon'] ?? '' ),` (sista raden i `$settings`-arrayen, ~rad 87) med:

```php
			'cookie_icon'              => esc_url_raw( $_POST['cookie_icon'] ?? '' ),
			'google_consent_enabled'   => ! empty( $_POST['google_consent_enabled'] ),
			'google_consent_intro'     => sanitize_text_field( $_POST['google_consent_intro'] ?? '' ),
			'google_consent_link_text' => sanitize_text_field( $_POST['google_consent_link_text'] ?? '' ),
```

- [ ] **Step 3: Syntaxkontroll**

Run: `php -l cookie-consent-manager/admin/controllers/class-cocookie-banner-controller.php`
Expected: `No syntax errors detected in ...`

- [ ] **Step 4: Commit**

```bash
git add cookie-consent-manager/admin/controllers/class-cocookie-banner-controller.php
git commit -m "Lägger till persistens för Googles samtyckeslänk i banner-inställningar"
```

---

### Task 2: Skicka inställningarna till frontend-config

Exponerar de tre nycklarna i `build_config()` och lägger till de redigerbara texternas standardvärden i engelsk översättning och svenska defaults (så admin-anpassad text inte skrivs över vid språkbyte).

**Files:**
- Modify: `cookie-consent-manager/includes/api/class-cocookie-rest-config.php` (`build_config()` ~rad 191, `get_translations()` 'en' ~rad 70, `apply_translation()` `$swedish_defaults` ~rad 293)

**Interfaces:**
- Consumes: nycklarna från `cocookie_settings` (Task 1).
- Produces: `$config['settings']['google_consent_enabled'|'google_consent_intro'|'google_consent_link_text']` som banner-mallen (Task 4) läser via `$s`.

- [ ] **Step 1: Lägg till nycklarna i `build_config()`**

Efter raden `'policy_link_text'   => $settings['policy_link_text'] ?? __( 'Läs vår integritetspolicy.', 'cocookie' ),` (~rad 191), lägg till:

```php
				'google_consent_enabled'   => ! empty( $settings['google_consent_enabled'] ),
				'google_consent_intro'     => $settings['google_consent_intro'] ?? __( 'Vi använder Googles annonstjänster.', 'cocookie' ),
				'google_consent_link_text' => $settings['google_consent_link_text'] ?? __( 'Läs hur Google hanterar dina personuppgifter.', 'cocookie' ),
```

- [ ] **Step 2: Lägg till engelska texter i `get_translations()`**

I `'en' => array( ... )`, efter raden `'policy_link_text'   => 'Read our privacy policy.',` (~rad 70), lägg till (dubbla citattecken pga apostrofen):

```php
					'google_consent_intro'     => "We use Google's advertising services.",
					'google_consent_link_text' => 'Learn how Google uses your data.',
```

- [ ] **Step 3: Lägg till svenska defaults i `apply_translation()`**

I `$swedish_defaults = array( ... )`, efter raden `'policy_link_text'   => 'Läs vår integritetspolicy.',` (~rad 293), lägg till:

```php
				'google_consent_intro'     => 'Vi använder Googles annonstjänster.',
				'google_consent_link_text' => 'Läs hur Google hanterar dina personuppgifter.',
```

- [ ] **Step 4: Syntaxkontroll**

Run: `php -l cookie-consent-manager/includes/api/class-cocookie-rest-config.php`
Expected: `No syntax errors detected in ...`

- [ ] **Step 5: Commit**

```bash
git add cookie-consent-manager/includes/api/class-cocookie-rest-config.php
git commit -m "Exponerar Googles samtyckeslänk i frontend-config och översättningar"
```

---

### Task 3: Admin-UI för inställningen

Lägger till kryssruta + två textfält i "Texter"-sektionen i banner-inställningarna, med en liten JS-toggle som visar/döljer textfälten beroende på kryssrutans läge.

**Files:**
- Modify: `cookie-consent-manager/admin/views/new/banner.php` (Texter-sektionen ~rad 80, samt litet `<script>` före formulärets slut ~rad 200)

**Interfaces:**
- Consumes: `$s['google_consent_enabled'|'google_consent_intro'|'google_consent_link_text']` (finns via `wp_parse_args($settings, get_defaults())` i controllern — Task 1). Postar fältnamnen `google_consent_enabled`, `google_consent_intro`, `google_consent_link_text` som Task 1:s `handle_save()` läser.

- [ ] **Step 1: Lägg till de tre fälten i Texter-sektionen**

Före raden `</table>` som avslutar Texter-sektionens tabell (direkt efter `settings_text`-raden, ~rad 80), lägg till:

```php
					<tr>
						<th><label for="google_consent_enabled"><?php esc_html_e( 'Googles samtyckeslänk', 'cocookie' ); ?></label></th>
						<td>
							<label>
								<input type="checkbox" id="google_consent_enabled" name="google_consent_enabled" value="1" <?php checked( ! empty( $s['google_consent_enabled'] ) ); ?>>
								<?php esc_html_e( 'Visa länk till Googles hantering av personuppgifter i bannern', 'cocookie' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Aktivera om sajten använder Googles annonstjänster (Google Ads, AdSense). Länken pekar på business.safety.google/privacy och krävs av Googles EU-policy för användarsamtycke.', 'cocookie' ); ?></p>
						</td>
					</tr>
					<tr class="cocookie-google-field">
						<th><label for="google_consent_intro"><?php esc_html_e( 'Google – inledande text', 'cocookie' ); ?></label></th>
						<td>
							<input type="text" id="google_consent_intro" name="google_consent_intro" value="<?php echo esc_attr( $s['google_consent_intro'] ); ?>" class="regular-text">
						</td>
					</tr>
					<tr class="cocookie-google-field">
						<th><label for="google_consent_link_text"><?php esc_html_e( 'Google – länktext', 'cocookie' ); ?></label></th>
						<td>
							<input type="text" id="google_consent_link_text" name="google_consent_link_text" value="<?php echo esc_attr( $s['google_consent_link_text'] ); ?>" class="regular-text">
						</td>
					</tr>
```

- [ ] **Step 2: Lägg till JS-toggle för textfälten**

Direkt före raden `<?php submit_button( ... 'cocookie_save_banner' ); ?>` (~rad 200), lägg till ett litet skript som visar/döljer de två textfälten beroende på kryssrutan:

```php
	<script>
		( function () {
			// Visa/dölj Google-textfälten beroende på kryssrutans läge
			var toggle = document.getElementById( 'google_consent_enabled' );
			var rows   = document.querySelectorAll( '.cocookie-google-field' );
			if ( ! toggle ) {
				return;
			}
			function sync() {
				rows.forEach( function ( row ) {
					row.style.display = toggle.checked ? '' : 'none';
				} );
			}
			toggle.addEventListener( 'change', sync );
			sync();
		} )();
	</script>
```

- [ ] **Step 3: Syntaxkontroll**

Run: `php -l cookie-consent-manager/admin/views/new/banner.php`
Expected: `No syntax errors detected in ...`

- [ ] **Step 4: Commit**

```bash
git add cookie-consent-manager/admin/views/new/banner.php
git commit -m "Lägger till admin-fält för Googles samtyckeslänk i bannerinställningar"
```

---

### Task 4: Konstant, rendering och CSS

Definierar den hårdkodade URL-konstanten, renderar Google-raden i samtyckesfliken när inställningen är på, och lägger till dämpad BEM-stil.

**Files:**
- Modify: `cookie-consent-manager/cookie-consent-manager.php` (konstanter ~rad 20)
- Modify: `cookie-consent-manager/public/templates/banner.php` (samtyckesfliken ~rad 71)
- Modify: `cookie-consent-manager/public/css/cocookie-banner.css` (~rad 105)

**Interfaces:**
- Consumes: `$s['google_consent_enabled'|'google_consent_intro'|'google_consent_link_text']` från config (Task 2); konstanten `COCOOKIE_GOOGLE_PRIVACY_URL`.

- [ ] **Step 1: Definiera URL-konstanten**

I huvudfilen, efter raden `define( 'COCOOKIE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );` (~rad 20), lägg till:

```php
define( 'COCOOKIE_GOOGLE_PRIVACY_URL', 'https://business.safety.google/privacy/' );
```

- [ ] **Step 2: Rendera Google-raden i bannern**

I samtyckesfliken, direkt efter den `</p>` som avslutar `<p class="cocookie-banner__text" ...>` (~rad 71, efter integritetspolicy-länken), lägg till:

```php
				<?php if ( ! empty( $s['google_consent_enabled'] ) ) : ?>
					<p class="cocookie-banner__google">
						<?php echo esc_html( $s['google_consent_intro'] ); ?>
						<a href="<?php echo esc_url( COCOOKIE_GOOGLE_PRIVACY_URL ); ?>" class="cocookie-banner__google-link" target="_blank" rel="noopener">
							<?php echo esc_html( $s['google_consent_link_text'] ); ?>
						</a>
					</p>
				<?php endif; ?>
```

- [ ] **Step 3: Lägg till CSS**

I `cocookie-banner.css`, efter regeln `.cocookie-banner__policy-link:hover { ... }` (~rad 105), lägg till:

```css
.cocookie-banner__google {
	margin: -12px 0 20px;
	font-size: 13px;
	color: #888;
	line-height: 1.5;
}

.cocookie-banner__google-link {
	color: var(--cocookie-accent);
	text-decoration: none;
}

.cocookie-banner__google-link:hover {
	text-decoration: underline;
}
```

- [ ] **Step 4: Syntaxkontroll**

Run: `php -l cookie-consent-manager/cookie-consent-manager.php && php -l cookie-consent-manager/public/templates/banner.php`
Expected: `No syntax errors detected` för båda filerna.

- [ ] **Step 5: Commit**

```bash
git add cookie-consent-manager/cookie-consent-manager.php cookie-consent-manager/public/templates/banner.php cookie-consent-manager/public/css/cocookie-banner.css
git commit -m "Renderar Googles samtyckeslänk i bannern med hårdkodad URL och stil"
```

---

## Manuell verifiering (staging)

Efter Task 4, verifiera på en WordPress-installation där pluginet är aktivt (kräver WP-miljö — kan inte köras lokalt utan staging):

- [ ] Kryssrutan av (default) → ingen Google-rad i bannern på frontend.
- [ ] Slå på kryssrutan i CoCookie → Banner, spara → de två textfälten visas i admin (JS-toggle); Google-raden visas i samtyckesfliken på frontend.
- [ ] Länken pekar på `https://business.safety.google/privacy/` och öppnas i ny flik (`target="_blank"`, `rel="noopener"`).
- [ ] Ändra inledande text + länktext, spara → ändringarna syns i bannern.
- [ ] Övriga bannerinställningar (färger, position, logotyp) påverkas inte av sparningen.
- [ ] Byt sajtspråk till engelska (Polylang/WPML eller WP-locale) → engelska standardtexter visas om admin inte anpassat dem.
- [ ] Länken är tangentbordsnåbar (tabba dit, Enter) och har tillräcklig kontrast mot bakgrunden (WCAG 2.1 AA, minst 4.5:1).
