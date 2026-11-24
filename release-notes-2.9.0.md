## Nytt

* **Åtta stilar för cookie-knappen.** Under Banner → Utseende → Cookie-knappens stil väljer du hur den flytande knappen ser ut när besökaren har gjort sitt val: emoji i vit cirkel (som tidigare), ritad ikon, ritad ikon med statusprick, piller med text, en knapp som växer när man pekar, kantflik, sidoflik eller hörn. De ritade stilarna följer bannerns färger och knappform. En ruta under fältet visar knappen i verklig storlek.
* **Ritade ikoner och val av sida.** Välj mellan kaka, reglage och sköld, och om knappen ska sitta till vänster eller höger. En egen uppladdad cookie-ikon ersätter den ritade.
* **Statusprick.** Pricken visar besökarens val: fylld när allt är godkänt, halv vid eget val och en tom ring vid bara nödvändiga. Skärmläsare får läget uppläst efter knappens namn.
* **Uppställning för bannern.** Loggan kan stå på egen rad (som tidigare), bredvid texten eller i flikraden. Kompakt rad lägger logga, text och knappar på en rad och visar flikarna först när besökaren klickar på Inställningar. På mobil döljs loggan som förut.

Standardvärdena är oförändrade. Befintliga sajter ser likadana ut tills någon väljer en ny stil eller uppställning.

## Förbättringar

* **Knappens namn följer bannerns språk.** Den flytande knappens namn för skärmläsare översätts nu till engelska, danska och norska tillsammans med resten av bannern.
* **Rätt hörn i "Om cookies".** Standardtexten som beskriver var cookie-knappen sitter byter hörn när knappen sitter till höger.

## Fixar

* **Samtycke och sidcache.** På sajter med sidcache (LiteSpeed med flera) kunde en sparad sida visas med en annan besökares samtycke. Skript kunde då laddas för besökare som inte godkänt, eller blockeras för dem som godkänt. Servern skriver nu alltid ut sidan blockerad, och samtycket läses i webbläsaren. Töm sidcachen efter uppdateringen.
