# AITL — AI Transparent Language

**Van algoritme naar begrijpelijke taal.**

AITL is een open taal om algoritmes en AI-systemen uit te leggen aan iedereen. Je schrijft een
uitleg als gewone tekst; AITL controleert die en maakt er een rapport van dat iedereen kan lezen:
studenten, ouders, klanten, collega's, toezichthouders.

Elke uitleg heeft **twee lagen**: de *techniek* (voor wie het wil controleren) en de *duiding* in
gewone taal (voor iedereen). Vergelijkingen mogen, zolang erbij staat waar ze ophouden. Regels worden
echt doorgerekend, zodat bij elke uitkomst de route zichtbaar is.

AITL is ontwikkeld door [LearningTour](https://www.learningtour.nl) voor
[AI Transparent](https://aitransparent.eu). Je kunt de taal daar online gebruiken, met een editor en
digitaal ondertekende publicatie: [app.aitransparent.eu/uitleg](https://app.aitransparent.eu/uitleg).
Deze repository bevat de taal zelf, de specificatie en een referentie-implementatie in puur PHP.

## De vijf principes

AITL is een taal én een methode. Een uitleg is pas af als hij aan vijf principes voldoet:

1. **Geen techniek zonder duiding.** Bij elk technisch onderdeel staat wat het betekent, in gewone taal.
2. **Geen vergelijking zonder grens.** Een metafoor helpt, maar zeg ook waar hij niet meer klopt.
3. **Geen vakterm zonder uitleg.** Woorden als *taalmodel* of *embedding* krijgen een eigen uitleg.
4. **Geen uitkomst zonder route.** Laat met voorbeelden zien hoe het systeem tot een uitkomst komt.
5. **Geen uitleg zonder beperkingen.** Vertel wat het systeem niet kan en waar het misgaat.

AITL controleert deze principes automatisch en meet de leesbaarheid van de gewone-taalteksten.

## Een klein voorbeeld

```aitl
uitleg "Signaal studievoortgang"
  organisatie: Voorbeeld College
  doelgroep: studenten, ouders en mentoren

doel
  duiding: Mentoren helpen om op tijd te zien welke student extra aandacht nodig heeft.
  techniek: Wekelijkse berekening op basis van de presentie in het studentvolgsysteem.
  metafoor: Het werkt als het waarschuwingslampje in een auto.
    grens: Een lampje zegt dat je moet kijken, niet wat er kapot is.

gegeven aanwezigheid
  type: percentage
  duiding: Hoe vaak de student de afgelopen vier weken bij de lessen was.

regel aandacht_nodig: nee
  label: seintje voor de mentor
  tenzij aanwezigheid < 80% dan ja, want de aanwezigheid is lager dan 80%
  duiding: De mentor krijgt een seintje als de student vaak afwezig is.

casus "Student met lage aanwezigheid"
  aanwezigheid: 72%
  verwacht aandacht_nodig: ja

beperking "Geen verklaring voor afwezigheid"
  duiding: Het systeem weet niet waarom een student afwezig was.

toezicht
  duiding: De mentor beslist of er een gesprek komt.
```

AITL rekent de casus door en schrijft de route uit:

```text
■ Student met lage aanwezigheid
    aanwezigheid: 72%
  → De uitkomst is ja, want de aanwezigheid is lager dan 80% (hier: aanwezigheid 72%).
  ✔ verwacht seintje voor de mentor = ja
```

In het rapport wordt 72% ook vertaald naar *ongeveer 7 op de 10*, krijgt de metafoor een kader met
de grens erbij, en staat de techniek in een uitklapbaar blok *Technisch bekeken*.

Meer voorbeelden staan in [`voorbeelden/`](voorbeelden): een beeldgenerator, een chatbot, een
AI-tekstdetector, een regelgebaseerd signaal en een Engelstalige uitleg.

## Gebruik

Nodig: PHP 8.1 of nieuwer, met de standaardextensie `mbstring`. Geen andere afhankelijkheden.

```bash
bin/aitl controleer voorbeelden/studievoortgang.aitl
```

```bash
bin/aitl rapport voorbeelden/beeldgenerator.aitl -o rapport.html
```

| Opdracht | Wat het doet |
|---|---|
| `aitl controleer <bestand>` | controleert de vijf principes, de leesbaarheid, de regels en de casussen |
| `aitl rapport <bestand> [-o x.html]` | maakt het rapport voor iedereen (één HTML-bestand, zonder scripts, printbaar) |
| `aitl casus <bestand>` | rekent de casussen door en toont per uitkomst de route |
| `aitl json <bestand>` | geeft het rapportmodel als JSON, voor eigen weergaven |

Engelse varianten: `check`, `report`, `cases`, `json`. Met `-` als bestandsnaam leest `aitl` van
standaardinvoer. De exitcode is 0 bij geen fouten, 1 bij fouten en 2 bij verkeerd gebruik.

Meldingen hebben de vorm `bestand:regel: niveau CODE: melding`, met een tip eronder. Dat werkt in
de meeste editors als klikbare foutmelding.

### Als bibliotheek

```php
require 'src/render.php';

$r = aitl_check(file_get_contents('uitleg.aitl'));
if ($r['ok']) {
    echo aitl_render_html($r['model']);
}
// $r['fouten'], $r['waarschuwingen'], $r['principes'], $r['leesbaarheid']
```

## De taal

De volledige beschrijving staat in [SPEC.md](SPEC.md). In het kort:

- Een bestand bestaat uit **blokken**: `uitleg`, `doel`, `begrip`, `gegeven`, `stap`, `factor`,
  `regel`, `casus`, `uitkomst`, `beperking`, `toezicht` en `bron`.
- Onder een blok staan ingesprongen **velden**: `duiding:` (gewone taal), `techniek:`, `metafoor:`
  met daaronder `grens:`, en per blok nog een paar eigen velden.
- Een **regel** heeft een standaardwaarde en uitzonderingen:
  `tenzij <voorwaarde> dan <waarde>, want <reden>`. De laatste uitzondering die geldt, bepaalt de uitkomst.
- Een **casus** geeft voorbeeldwaarden en kan met `verwacht regel: waarde` toetsen of de regels
  doen wat je denkt. Zo zitten de tests in de uitleg zelf.
- AITL kan ook in het **Engels**: `explain`, `purpose`, `step`, `rule`, `plain:`, `technical:`,
  `metaphor:`, `limit:`, `unless … then … because …`, met `language: en` voor een Engels rapport.

## Tests

```bash
php tests/gauntlet.php
```

De gauntlet toetst de voorbeelden, alle foutmeldingen, de rekenregels, getallen in mensentaal,
leesbaarheid, veiligheid van het rapport (geen scripts, geen gevaarlijke links), determinisme,
grenzen en de opdrachtregel, en eindigt met fuzzing (`FUZZ_N=2000 FUZZ_SEED=7 php tests/gauntlet.php fuzz`).

## Inspiratie

De opzet van regels, casussen en uitlegroutes is geïnspireerd door [Lemma](https://lemmabase.com),
een taal om wet- en regelgeving leesbaar en uitvoerbaar vast te leggen. AITL richt zich op iets
anders: niet de regels van de wet, maar de werking van een algoritme of AI-systeem, uitgelegd voor iedereen.

## Licentie

MIT, zie [LICENSE](LICENSE). Je mag AITL vrij gebruiken, ook commercieel en in eigen software.

---

## English

**AITL (AI Transparent Language)** is an open language for explaining algorithms and AI systems to
everyone. Every explanation has two layers: the *technical* layer and a *plain-language* layer.
Metaphors are allowed as long as their *limit* is stated. Rules are actually evaluated, so every
outcome comes with its route. AITL checks five principles: no technology without plain language,
no metaphor without its limit, no jargon without explanation, no outcome without its route, and
no explanation without limitations.

Keywords are Dutch by default and have English aliases (`explain`, `purpose`, `term`, `input`,
`step`, `factor`, `rule`, `case`, `outcome`, `limitation`, `oversight`, `source`; fields `plain`,
`technical`, `metaphor`, `limit`, `also`, `unit`, `value`, `confidence`, `weight`, `direction`,
`expect`; rules use `unless … then … because …`). Set `language: en` for an English report. See
[`voorbeelden/ai-transparent-label.aitl`](voorbeelden/ai-transparent-label.aitl).

```bash
bin/aitl check voorbeelden/ai-transparent-label.aitl
bin/aitl report voorbeelden/ai-transparent-label.aitl -o report.html
```

Requires PHP 8.1+ with `mbstring`. MIT licensed. Built by [LearningTour](https://www.learningtour.nl)
for [AI Transparent](https://aitransparent.eu).
