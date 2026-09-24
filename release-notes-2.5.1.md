## Fixar

* **Skannern hittar Google Analytics på sajter med Site Kit.** Site Kit undantar inloggade användare som standard men laddar ändå gtag.js med opt-out-flaggan satt, och skriver en Consent Mode-default med "denied". Under ren skanning slår CoCookie av undantaget via Site Kits filter, byter defaulten till "granted" och skickar en samtyckesuppdatering med allt tillåtet, så att sidan beter sig som för en besökare som accepterat. Det ersätter det tidigare beroendet av att bannern råkade köra inne i skannern.
* **Felaktig varning "Google Analytics laddades men satte inga cookies".** Den visades när ett consent mode-snutt definierat funktionen gtag utan att GA laddats. Detektionen kräver nu en riktig skript-tagg och känner igen opt-out-flaggan ga-disable-*.

## Förbättringar

* **Tydligare varningstexter.** Nämner fördröjd JavaScript från cache-plugins (WP Rocket, Perfmatters, FlyingPress) och GTM-containrar med egen consent-mall, inte bara adblocker.
