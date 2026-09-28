# AITL 1.0 — specificatie

AITL (AI Transparent Language) is een taal om algoritmes en AI-systemen uit te leggen aan iedereen.
Dit document beschrijft versie 1.0 van de taal. De referentie-implementatie staat in `src/aitl.php`
(controle en uitvoering) en `src/render.php` (het rapport).

Uitgangspunt: een AITL-bestand is tegelijk **leesbare tekst** en iets wat een computer kan
**controleren en uitvoeren**. Wie het bestand leest zonder AITL te kennen, moet het grotendeels
kunnen volgen.

## 1. De methode: vijf principes

| Nr | Principe | Wordt gecontroleerd door |
|---|---|---|
| 1 | Geen techniek zonder duiding | E010 (techniek of verplicht blok zonder duiding), W108 (duiding lijkt letterlijk op techniek) |
| 2 | Geen vergelijking zonder grens | E011 (metafoor zonder grens) |
| 3 | Geen vakterm zonder uitleg | W101 (vakterm in gewone-taaltekst zonder `begrip`-blok) |
| 4 | Geen uitkomst zonder route | W106 (regels zonder casus), W112 (geen stappen, factoren of regels), E030–E032 |
| 5 | Geen uitleg zonder beperkingen | W104 (geen `beperking`) |

Een principe telt als voldaan als geen van de bijbehorende meldingen voorkomt. Een uitleg met
**fouten** (E-codes) kan niet worden weergegeven of gepubliceerd; **waarschuwingen** (W-codes)
mogen, maar het rapport toont eerlijk welke principes niet zijn gehaald.

## 2. Bestand

- Tekst in **UTF-8**, bij voorkeur met de extensie `.aitl`. Een BOM wordt genegeerd.
- Regeleinden `\n`, `\r\n` of `\r`.
- Maximaal 200.000 bytes en 5.000 regels (E005).
- Onzichtbare stuurtekens (behalve tab en regeleinde) zijn een fout (E006) en worden verwijderd.
- Een regel die met `#` begint (na eventuele spaties) is **commentaar**.
- **Inspringen** gebeurt met spaties. Een tab telt als twee spaties (W107).
- Lege regels scheiden alinea's in lange teksten; verder hebben ze geen betekenis.

## 3. Structuur: blokken en velden

Een bestand bestaat uit **blokken**. Een blok begint aan het begin van een regel met een
sleutelwoord, eventueel gevolgd door een naam. Alles wat daaronder inspringt, hoort bij het blok.

```aitl
stap "Beginnen met ruis"
  duiding: Het systeem begint met een plaatje vol willekeurige puntjes.
  techniek: Initialisatie met Gaussische ruis in de latente ruimte.
```

Onder een blok staan **velden** in de vorm `naam: waarde`. Een waarde mag doorlopen op volgende
regels die verder inspringen dan het veld:

```aitl
uitleg "Chatbot studentenservice"
  samenvatting: Vera beantwoordt vragen over roosters en regels.
    Ze zoekt eerst in de documenten van de school.

    Deze lege regel hierboven begint een nieuwe alinea.
```

Een naam na het sleutelwoord staat tussen aanhalingstekens (`"…"` of `“…”`) of zonder, als hij
uit één regel tekst bestaat. Voor `gegeven` is de naam een **identificatie**: letters, cijfers en
`_`, beginnend met een letter of `_` (bijvoorbeeld `gemiddeld_cijfer`).

Het eerste blok is altijd `uitleg` (E040). Onbekende blokken en velden zijn een fout (E001, E002),
met een suggestie bij een tikfout. Een veld mag per blok één keer voorkomen (E003).

### 3.1 Blokken

| Blok | Engels | Naam | Vaker? | Velden (vet = verplicht) |
|---|---|---|---|---|
| `uitleg` | `explain`, `explanation` | titel (verplicht) | nee | organisatie, systeem, versie, doelgroep, taal, samenvatting, contact, url |
| `doel` | `purpose` | geen | nee | **duiding**, techniek, metafoor |
| `begrip` | `term` | het woord | ja | **duiding**, techniek, metafoor, ook |
| `gegeven` | `input`, `data` | identificatie | ja | **type**, **duiding**, label, techniek, eenheid, bron |
| `stap` | `step` | titel | ja | **duiding**, techniek, metafoor, waarde, zekerheid |
| `factor` | `factor` | titel | ja | **gewicht**, **duiding**, richting, techniek, metafoor |
| `regel` | `rule` | zie §5 | ja | **duiding**, label, techniek (+ `tenzij`-regels) |
| `casus` | `case` | titel | ja | duiding (+ waarden en `verwacht`) |
| `uitkomst` | `outcome` | titel (optioneel) | ja | **duiding**, techniek, metafoor, zekerheid, waarde |
| `beperking` | `limitation` | titel | ja | **duiding**, techniek, metafoor |
| `toezicht` | `oversight` | geen | nee | **duiding**, techniek, contact |
| `bron` | `source` | titel | ja | url, duiding |

### 3.2 Velden

| Veld | Engels | Betekenis |
|---|---|---|
| `duiding` | `plain` | uitleg in gewone taal, voor iedereen |
| `techniek` | `technical` | technische uitleg; vereist een `duiding` in hetzelfde blok (E010) |
| `metafoor` | `metaphor` | vergelijking; vereist een ingesprongen `grens:` eronder (E011) |
| `grens` | `limit` | waar de vergelijking ophoudt; alleen onder `metafoor` |
| `ook` | `also` | synoniemen van een begrip, gescheiden door komma's |
| `type` | — | type van een gegeven: `getal`, `percentage`, `tekst`, `janee`, `datum` (Engels: `number`, `percent`, `text`, `boolean`/`yesno`, `date`) |
| `label` | — | leesbare naam van een gegeven of regel |
| `eenheid` | `unit` | eenheid bij een getal, bijvoorbeeld `woorden` |
| `waarde` | `value` | een instelling of vaste waarde, getoond onder *Technisch bekeken* |
| `zekerheid` | `confidence` | percentage van 0% tot 100%; het rapport vertaalt dit naar mensentaal |
| `gewicht` | `weight` | percentage, of `groot`, `middel`, `klein`; percentages samen hooguit 100% (W114) |
| `richting` | `direction` | `verhoogt`, `verlaagt` of `beide` |
| `versie` | `version` | datum in de vorm `JJJJ-MM-DD` |
| `taal` | `language` | `nl` (standaard) of `en`; bepaalt de taal van het rapport |
| `url` | — | begint met `http://` of `https://`, zonder spaties |
| `organisatie`, `systeem`, `doelgroep`, `samenvatting`, `contact`, `bron` | `organisation`, `system`, `audience`, `summary`, … | vrije tekst |

Sleutelwoorden zijn niet hoofdlettergevoelig. Nederlandse en Engelse sleutelwoorden mogen door
elkaar worden gebruikt.

### 3.3 Metafoor en grens

```aitl
  metafoor: Het werkt als het waarschuwingslampje in een auto.
    grens: Een lampje zegt dat je moet kijken, niet wat er kapot is.
```

Het rapport toont de metafoor in een kader *Vergelijk het met*, met de grens eronder als
*Waar de vergelijking ophoudt*.

## 4. Waarden

| Type | Schrijfwijze | Voorbeelden |
|---|---|---|
| `getal` | cijfers, decimaal met komma of punt | `6,8` `6.8` `1450` `-3` |
| `percentage` | getal met `%` | `72%` `5,5 %` |
| `janee` | `ja`/`nee` (ook `yes`/`no`, `waar`/`onwaar`, `true`/`false`) | `ja` |
| `tekst` | tussen aanhalingstekens | `"huur"` |
| `datum` | `JJJJ-MM-DD` | `2026-09-01` |

**Duizendtallen** schrijf je zonder scheidingsteken. Een getal als `1.450` is dubbelzinnig
(Nederlands: 1450, Engels: 1,45) en wordt daarom geweigerd, met een tip (E020 in een regel, E030 in
een casus). Het rapport zelf toont getallen wél met duizendtallen, volgens de taal van het rapport.

## 5. Regels

Een regel legt vast hoe het systeem tot een uitkomst komt. Hij heeft een **naam**, een
**standaardwaarde** en nul of meer **uitzonderingen**:

```aitl
regel aandacht_nodig: nee
  label: seintje voor de mentor
  tenzij aanwezigheid < 80% dan ja, want de aanwezigheid is lager dan 80%
  tenzij gemiddeld_cijfer < 5,5 dan ja, want het gemiddelde cijfer is onvoldoende
  duiding: De mentor krijgt een seintje als de student vaak afwezig is of onvoldoendes haalt.
```

- Vorm van de kopregel: `regel <identificatie>: <expressie>` (E020).
- Vorm van een uitzondering: `tenzij <voorwaarde> dan <expressie>[, want <reden>]`.
  Engels: `unless … then …, because …`. Een uitzondering mag doorlopen op een ingesprongen regel.
- **Semantiek:** eerst wordt de standaardwaarde bepaald. Daarna worden de uitzonderingen van boven
  naar beneden bekeken; **de laatste uitzondering waarvan de voorwaarde geldt, bepaalt de uitkomst.**
  Zet dus de belangrijkste uitzondering onderaan.
- De `reden` achter `want` is gewone taal en komt letterlijk in de route. Zonder `want` zet AITL de
  voorwaarde zelf om in een zin (*"de aanwezigheid is lager dan 80%"*).
- Een regel mag gegevens en **andere regels** gebruiken. Een onbekende naam is E021, een
  kringverwijzing E023.
- Alle takken van een regel moeten hetzelfde type opleveren, en een voorwaarde moet `janee`
  opleveren (E022).

### 5.1 Expressies

```text
expressie    = of
of           = en { ("of" | "or") en }
en           = niet { ("en" | "and") niet }
niet         = ("niet" | "not") niet | vergelijking
vergelijking = som [ ("<" | "<=" | ">" | ">=" | "=" | "!=" | "is" | "is niet") som ]
som          = product { ("+" | "-") product }
product      = unair { ("*" | "/") unair }
unair        = "-" unair | primair
primair      = getal | percentage | tekst | datum | ja/nee | naam | "(" expressie ")"
```

Typeregels:

- Vergelijken kan alleen tussen gelijke typen (*"je vergelijkt een getal met een percentage"* is E022).
  `tekst` en `janee` kun je alleen op gelijk of ongelijk vergelijken. `is` betekent `=`, `is niet` `!=`.
- Rekenen kan met getallen en percentages. Percentage ± percentage is een percentage; al het andere
  rekenwerk levert een getal op. Delen door nul is een fout tijdens het doorrekenen (E032).
- `en`, `of` en `niet` werken alleen op `janee`. `en` en `of` rekenen kortsluitend.
- Uitkomsten worden op tien decimalen afgerond, vergelijkingen op negen, zodat `0,1 + 0,2 = 0,3` klopt.
- Expressies mogen hooguit 40 niveaus diep genest zijn.

## 6. Casussen

Een casus is een voorbeeld: vaste waarden voor de gegevens, waarmee AITL alle regels doorrekent.

```aitl
casus "Student met lage aanwezigheid"
  aanwezigheid: 72%
  gemiddeld_cijfer: 6,8
  eigen_melding: nee
  verwacht aandacht_nodig: ja
  duiding: Deze student haalt goede cijfers, maar was vaak afwezig.
```

- Elk veld behalve `duiding` is `gegeven: waarde`. Het gegeven moet bestaan en de waarde moet bij
  het type passen (E030). Een casus moet een waarde geven voor elk gegeven dat de regels gebruiken (E030).
- `verwacht <regel>: <waarde>` is een **toets in de taal**: klopt de uitkomst niet, dan is dat fout
  E031. Zo blijft een uitleg kloppen als de regels veranderen.
- Bij elke uitkomst maakt AITL een **route**: welke uitzondering de doorslag gaf en waarom, met de
  waarden uit de casus (*"De uitkomst is ja, want de aanwezigheid is lager dan 80% (hier:
  aanwezigheid 72%)."*), plus een lijst van alle controles.
- Het doorrekenen is **deterministisch**: dezelfde bron geeft altijd hetzelfde resultaat.

## 7. Gewone taal

AITL behandelt de volgende teksten als gewone taal: `samenvatting`, `duiding`, `grens`, `metafoor`,
de reden achter `want` en de `duiding` van casussen. Daarop worden twee controles gedaan.

**Vaktermen (W101).** AITL kent een lijst van vaktermen (zoals *taalmodel*, *embedding*,
*trainingsdata*, *drempelwaarde*, *prompt*). Staat zo'n woord in gewone taal zonder dat er een
`begrip`-blok voor is (met de term zelf of als synoniem in `ook`), dan volgt een waarschuwing. In het
rapport wordt de eerste vermelding van een begrip per alinea een verwijzing naar de uitleg ervan.

**Leesbaarheid (W102, W103).** AITL meet de leesbaarheid met de Flesch-Douma-formule:

```text
score = 206,835 − 0,93 × (woorden per zin) − 77 × (lettergrepen per woord)
```

70 of hoger is *makkelijk*, 60 *redelijk makkelijk*, 50 *gemiddeld*, 30 *moeilijk*, lager *zeer
moeilijk*. Zinnen van meer dan 25 woorden geven W102; een totaalscore onder 45 (bij minstens 30
woorden) geeft W103.

**Getallen in mensentaal.** Het rapport vertaalt percentages tussen 0% en 100% naar een natuurlijke
frequentie (*72% → ongeveer 7 op de 10*) en een `zekerheid` naar een kanswoord (*zeer waarschijnlijk*)
met een uitleg als *"Van 10 vergelijkbare gevallen zou het er ongeveer 9 goed hebben, en 1 niet."*

## 8. Het rapport

Het rapport is één HTML-bestand zonder scripts, geschikt voor scherm, telefoon en print. Volgorde:

1. **In het kort** — samenvatting en doel
2. **Welke gegevens gebruikt het?**
3. **Hoe werkt het?** — de stappen
4. **Wat weegt mee?** — de factoren, met gewicht en richting
5. **Welke regels gelden?**
6. **Voorbeelden: zo pakt het uit** — de casussen, met route en controles
7. **De uitkomst**
8. **Wat kan het niet?** — de beperkingen
9. **Wie houdt toezicht?**
10. **Begrippen** en **Bronnen**
11. **Over deze uitleg** — methode, de vijf principes (✔/✗), leesbaarheid en een SHA-256-vingerafdruk van de bron

Bij elk onderdeel staat eerst de duiding; de techniek staat in een uitklapbaar blok *Technisch
bekeken*. Links in het rapport zijn alleen `http`/`https`.

## 9. Meldingen

Elke melding heeft een niveau, een code, een regelnummer, een tekst en meestal een tip.

| Code | Betekenis |
|---|---|
| E001 | onbekend blok (met suggestie bij tikfout) |
| E002 | onbekend veld, of een regel die geen veld is |
| E003 | dubbel veld, dubbel enkelvoudig blok of dubbele naam |
| E004 | ingesprongen regel die bij geen blok hoort |
| E005 | bestand te groot of te veel regels |
| E006 | ongeldige UTF-8 of onzichtbaar stuurteken |
| E010 | techniek zonder duiding, of verplichte duiding ontbreekt |
| E011 | metafoor zonder grens |
| E012 | verplichte naam of verplicht veld ontbreekt, of ongeldige naam |
| E020 | regel of uitzondering heeft de verkeerde vorm, of de expressie klopt niet |
| E021 | onbekende naam in een regel |
| E022 | typefout in een regel |
| E023 | kringverwijzing tussen regels |
| E030 | fout in een casus: onbekend gegeven, ontbrekende of ongeldige waarde |
| E031 | verwachting klopt niet, of verwachting voor een onbekende regel |
| E032 | fout tijdens het doorrekenen, zoals delen door nul |
| E040 | het bestand begint niet met `uitleg` |
| E041 | ongeldige versie, url, taal, zekerheid, gewicht, richting of type |
| W101 | vakterm zonder uitleg |
| W102 | lange zin |
| W103 | moeilijk leesbare gewone-taalteksten |
| W104 | geen beperkingen |
| W106 | regels zonder casus |
| W107 | tabs gebruikt |
| W108 | duiding lijkt bijna letterlijk op de techniek |
| W110 | geen doel |
| W111 | geen toezicht |
| W112 | niet beschreven hoe het systeem tot een uitkomst komt |
| W114 | gewichten van factoren samen meer dan 100% |
| W115 | naam achter een blok dat geen naam heeft (wordt genegeerd) |

## 10. Versies

Deze specificatie beschrijft AITL 1.0. Nieuwe versies voegen bij voorkeur alleen toe; een bestand
dat in 1.0 foutloos is, blijft dat zo veel mogelijk. Een wijziging die bestaande bestanden anders
laat uitpakken, krijgt een nieuw hoofdversienummer.
