# CoCookie — projektanteckningar

WordPress-plugin för cookie-samtycke. Koden ligger i `cookie-consent-manager/`. Filer med prefixet `CCM_` är legacy och behålls för bakåtkompatibilitet; ny kod använder `CoCookie_`.

## Lärdomar

- **Skanningen körs i adminens inloggade webbläsare.** Iframe-skannern delar session med admin. Allt som beter sig annorlunda för inloggade (GA-plugins som undantar administratörer, sidbyggarcookies, inloggningscookies) påverkar resultatet. Ren-skanningsläget (`?ccm_clean_scan=`) måste stänga av *allt* CoCookie gör på sidan: blockering, Consent Mode-default **och** bannern. Bannerns JS skickade tidigare ett lagrat "avvisa"-val till gtag inne i skannerns iframe, så GA satte aldrig `_ga` (fixat i 2.5.0).
- **Analytics saknas "ibland" beror nästan alltid på sajtens GA-plugin**, inte på mönstermatchningen. Kontrollera undantag för inloggade användare i Site Kit/MonsterInsights innan mönsterfilen ändras.
- **Site Kit (verifierat i källkoden, sept 2026):** undantar inloggade som standard (`trackingDisabled = ['loggedinUsers']`) men laddar ändå gtag.js och sätter `window["ga-disable-G-XXXX"] = true`. Consent Mode-snutten skrivs i `wp_head` prioritet 1 och definierar `function gtag()`, så `typeof gtag === 'function'` säger inget om GA laddats. Filter som CoCookie använder vid ren skanning: `googlesitekit_analytics_tracking_disabled` och `googlesitekit_consent_defaults` (sedan 2.5.1).
- **Ren skanning ska simulera en besökare som accepterat allt**, inte bara "ingen banner". Annars stoppar consent mode-defaults från andra plugins cookie-sättningen.
