=== Cookie Consent Manager ===
Contributors: cookie-consent-manager
Tags: cookie, consent, gdpr, privacy, banner
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.5.1
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

= 2.5.1 =
* Fix: skannern hittar nu Google Analytics på sajter med Site Kit. Site Kit undantar inloggade användare som standard men laddar ändå gtag.js med opt-out-flaggan satt, och skriver en Consent Mode-default med "denied". Under ren skanning slår CoCookie av undantaget via Site Kits filter, byter defaulten till "granted" och skickar en samtyckesuppdatering med allt tillåtet, så att sidan beter sig som för en besökare som accepterat. Det ersätter det tidigare beroendet av att bannern råkade köra inne i skannern.
* Fix: varningen "Google Analytics laddades men satte inga cookies" visades felaktigt när ett consent mode-snutt definierat funktionen gtag utan att GA laddats. Detektionen kräver nu en riktig skript-tagg, och känner igen opt-out-flaggan ga-disable-*.
* Förbättring: varningstexterna nämner nu fördröjd JavaScript från cache-plugins och GTM-containrar med egen consent-mall, inte bara adblocker.

= 2.5.0 =
* Nytt: cookies kan ignoreras direkt i skannern och i installationsguiden. Ignorerade cookies tas bort ur registret och visas inte i kommande skanningar. Listan finns under Compliance → Scanner och varje cookie kan återställas. Praktiskt för inloggnings- och adminverktygscookies som bara du får eftersom skanningen körs i din inloggade webbläsare.
* Nytt: installationsguidens granskning har kryssrutor. Cookies som troligen bara sätts för inloggade admins är avmarkerade som standard, och "Importera markerade" tar bara med de markerade.
* Nytt: skannern förklarar varför Google Analytics saknas. Om GA finns på sajten för anonyma besökare men inte laddades i din inloggade skanning (Site Kit, MonsterInsights m.fl. undantar administratörer) visas en varning med råd. Likaså om GA laddades men inte satte några cookies (adblocker eller tidigare avvisat samtycke), och om skannern inte kunde läsa sajten på grund av olika domän eller protokoll mellan webbplats- och adminadress.
* Fix: bannern laddades även i skannerns "ren skanning"-läge. Hade du tidigare avvisat analytics på sajten skickade bannern det valet vidare till Google Analytics inne i skannern, som då aldrig satte sina cookies. Bannern och dess skript hoppas nu över helt vid ren skanning.
* Förbättring: bakgrundsskannern respekterar ignoreringslistan.

= 2.4.0 =
* Nytt: kortkoden `[cocookie_cookie_list]` visar nu även besökarens eget val — vilka kategorier som är tillåtna, när samtycket sparades och samtyckes-ID, med knappar för att ändra eller dra tillbaka. Rutan fylls i med JavaScript, så den fungerar även när sidan är cachad. Stäng av den med `consent="no"`.
* Nytt: antal cookies visas per kategori i listan, och datum för senaste skanningen visas under listan.
* Nytt: attributet `heading` styr rubriknivån i listan (h2–h6, standard h3), så att den kan läggas under en befintlig rubrik utan att hoppa över nivåer.
* Fix (tillgänglighet): gråa texter i bannern hade för låg kontrast mot vit bakgrund (3,5:1 eller lägre). Knappen "Inställningar", Google-noteringen, kategoribeskrivningar, "Krävs"-etiketten, expandera-knappen och DNT-rutan är nu mörkare och klarar WCAG AA (4,5:1).
* Förbättring: policygeneratorn lägger in kortkoden i stället för en fast tabell i cookiepolicyn. Listan blir därmed aldrig inaktuell. Redan skapade sidor ändras inte — generera om sidan för att få kortkoden.

= 2.3.2 =
* Förbättring: kompakt mobil-layout för bannern (under 480px). Loggan döljs, ytorna krymps och Acceptera/Avvisa ligger sida vid sida. Bannern täckte tidigare nästan halva mobilskärmen — den skymde sajtens egna CTA-knappar och blev sidans LCP-element i Lighthouse/PageSpeed.
* Förbättring (SEO): bannern innehåller inte längre några rubriktaggar. Bannerns rubrik och rubrikerna i fliken "Om cookies" var h2/h3, och eftersom bannern skrivs ut direkt efter body hamnade de överst i sidans rubrikstruktur, före sajtens egen h1. De är nu vanliga element med samma klasser, så utseendet är oförändrat.

= 2.3.1 =
* Fix: inställningen för den flytande cookie-knappen nådde aldrig frontend, så knappen visades även när den var avstängd. Nu döljs den korrekt när kryssrutan är urkryssad.

= 2.3.0 =
* Nytt: kortkoden `[cocookie_settings]` renderar en vanlig knapp som öppnar samtyckespanelen, till exempel på cookie-policy-sidan. Attributen `text` och `class` styr etikett och extra CSS-klasser.
* Nytt: egna knappar och länkar kan öppna panelen — sätt klassen `cocookie-open-settings`, eller låt en meny-/knapplänk peka på `#cookie-installningar`.
* Nytt: den flytande cookie-knappen kan stängas av under Banner-inställningar. Den är påslagen som standard, så befintliga sajter påverkas inte.

= 2.2.1 =
* Säkerhet: cookie- och localStorage-värden samlas inte längre in vid scanning. Endast namnet sparas. Tidigare lagrade värden nollställs automatiskt via migrering.
* Säkerhet: TLS-verifiering är alltid på i bakgrundsscannern (sslverify var avstängd).
* Säkerhet: API-nyckeln för central rapportering visas inte längre i klartext i admin-formuläret. Lämna fältet tomt för att behålla sparad nyckel.
* Säkerhet: API-URL för central rapportering måste vara https — nyckeln skickas som header och får inte gå i klartext.
* Fix: JSON-import av cookies kraschade på PHP 8 (felstavad PATHINFO-konstant). Importen fungerar nu igen i både nya och gamla adminvyn.
* Fix: samtycken tappades tyst när sidan serverades från full page cache med utgången nonce. Bannern hämtar nu en färsk nonce och gör om anropet, och loggar en varning om det ändå misslyckas.
* Fix: policygenerering skriver bara över befintlig sida om ID:t faktiskt pekar på en sida.
* Fix: avinstallation städar nu även bort optioner med cocookie_-prefix.

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
