<?php
// AITL-gauntlet: toetst taal, controle, rekenregels, weergave en robuustheid.
//   php tests/gauntlet.php            alles
//   php tests/gauntlet.php fuzz       alleen onderdelen waarvan de naam 'fuzz' bevat
//   FUZZ_N=2000 FUZZ_SEED=7 php tests/gauntlet.php fuzz

declare(strict_types=1);
error_reporting(E_ALL);
set_error_handler(function ($no, $str, $file, $line) {
    if (!(error_reporting() & $no)) return false;
    throw new ErrorException($str, 0, $no, $file, $line);
});

require __DIR__ . '/../src/render.php';

$filter = $argv[1] ?? '';
$results = []; $failures = 0;
function ok(string $case, bool $pass, string $detail = ''): void {
    global $results, $failures, $filter;
    $results[] = [$case, $pass];
    if (!$pass) $failures++;
    printf("%s %-70s %s\n", $pass ? "\033[32m✔\033[0m" : "\033[31m✘\033[0m", $case, $pass ? '' : $detail);
}
function part(string $name): bool {
    global $filter;
    if ($filter && !str_contains(mb_strtolower($name), mb_strtolower($filter))) return false;
    echo "\n== $name ==\n";
    return true;
}
function codes(array $r): array { return array_column(array_merge($r['fouten'], $r['waarschuwingen']), 'code'); }

/** HTML-controle: geen script, geen event-handlers, links alleen naar ankers die bestaan of naar http(s). */
function html_ok(string $html): array {
    $problems = [];
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NONET);
    foreach (libxml_get_errors() as $e) {
        if (!preg_match('~Tag (main|section|header|footer|nav|details|summary) invalid~', $e->message)) $problems[] = 'libxml: ' . trim($e->message);
    }
    libxml_clear_errors();
    $ids = [];
    foreach ((new DOMXPath($dom))->query('//*[@id]') as $el) $ids[$el->getAttribute('id')] = true;
    foreach ($dom->getElementsByTagName('script') as $s) $problems[] = 'script-element';
    foreach ((new DOMXPath($dom))->query('//@*') as $attr) {
        if (str_starts_with(strtolower($attr->nodeName), 'on')) $problems[] = 'event-attribuut ' . $attr->nodeName;
        if (in_array(strtolower($attr->nodeName), ['href', 'src'], true)) {
            $v = $attr->nodeValue;
            if (str_starts_with($v, '#')) { if (!isset($ids[substr($v, 1)])) $problems[] = "anker $v bestaat niet"; }
            elseif (!preg_match('~^https?://~i', $v)) $problems[] = "link $v";
        }
    }
    if ($dom->getElementsByTagName('h1')->length !== 1) $problems[] = 'niet precies één h1';
    return $problems;
}

$examples = glob(__DIR__ . '/../voorbeelden/*.aitl');

// ---------------------------------------------------------------------------
if (part('A. Voorbeelden')) {
    ok('A er zijn minstens 5 voorbeelden', count($examples) >= 5, (string)count($examples));
    foreach ($examples as $f) {
        $name = basename($f);
        $src = file_get_contents($f);
        $r = aitl_check($src);
        ok("A $name: geen fouten en geen waarschuwingen", $r['ok'] && !$r['waarschuwingen'],
            implode(' | ', array_map(fn($d) => "{$d['code']} r{$d['regel']} {$d['melding']}", array_merge($r['fouten'], $r['waarschuwingen']))));
        ok("A $name: alle vijf principes ✔", count(array_filter($r['principes'], fn($p) => $p['ok'])) === 5);
        ok("A $name: goed leesbaar (score ≥ 60)", ($r['leesbaarheid']['score'] ?? 0) >= 60, (string)$r['leesbaarheid']['score']);
        $h1 = aitl_render_html($r['model']); $h2 = aitl_render_html(aitl_check($src)['model']);
        ok("A $name: weergave is deterministisch", $h1 === $h2);
        $p = html_ok($h1);
        ok("A $name: HTML veilig en geldig", !$p, implode('; ', array_slice($p, 0, 4)));
        foreach ($r['model']['casussen'] as $c) {
            foreach ($c['verwacht'] as $v) ok("A $name: casus '{$c['titel']}' klopt met verwachting", $v['klopt']);
        }
    }
}

// ---------------------------------------------------------------------------
if (part('B. Foutmeldingen en waarschuwingen')) {
    $base = "uitleg \"T\"\ndoel\n  duiding: Een doel.\ntoezicht\n  duiding: Iemand kijkt mee.\nbeperking \"B\"\n  duiding: Het kan niet alles.\nstap \"S\"\n  duiding: Het doet iets.\n";
    $cases = [
        ['leeg bestand', '', ['E040']],
        ['geen uitleg bovenaan', "doel\n  duiding: x.\n", ['E040']],
        ['onbekend blok', $base . "stapp \"X\"\n  duiding: y.\n", ['E001']],
        ['onbekend blok krijgt suggestie', $base . "begrippp x\n  duiding: y.\n", ['E001']],
        ['onbekend veld', $base . "stap \"X\"\n  duidng: y.\n", ['E002', 'E010']],
        ['dubbel veld', $base . "stap \"X\"\n  duiding: a.\n  duiding: b.\n", ['E003']],
        ['dubbel doel', $base . "doel\n  duiding: nog een.\n", ['E003']],
        ['inspringing zonder blok', "  stap \"X\"\n" . $base, ['E004']],
        ['techniek zonder duiding (bron)', $base . "bron \"B\"\n  url: https://example.org\n", []],
        ['techniek zonder duiding', $base . "stap \"X\"\n  techniek: Iets met vectoren.\n", ['E010']],
        ['metafoor zonder grens', $base . "stap \"X\"\n  duiding: Iets.\n  metafoor: Als een brein.\n", ['E011']],
        ['gegeven zonder type', $base . "gegeven x\n  duiding: iets.\n", ['E012']],
        ['gegeven met ongeldige naam', $base . "gegeven mijn cijfer\n  type: getal\n  duiding: iets.\n", ['E012']],
        ['blok zonder naam', $base . "stap\n  duiding: iets.\n", ['E012']],
        ['regel zonder standaardwaarde', $base . "regel r\n  duiding: iets.\n", ['E020']],
        ['uitzondering zonder dan', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: nee\n  tenzij x > 1 ja\n  duiding: r.\ncasus \"c\"\n  x: 2\n", ['E020']],
        ['syntaxfout in expressie', $base . "regel r: (1 + 2\n  duiding: r.\n", ['E020']],
        ['onbekende naam', $base . "regel r: y > 1\n  duiding: r.\ncasus \"c\"\n  duiding: c.\n", ['E021']],
        ['typefout', $base . "gegeven x\n  type: tekst\n  duiding: x.\nregel r: x > 1\n  duiding: r.\ncasus \"c\"\n  x: a\n", ['E022']],
        ['kringverwijzing', $base . "regel a: b\n  duiding: a.\nregel b: a\n  duiding: b.\ncasus \"c\"\n  duiding: c.\n", ['E023']],
        ['casus: onbekend gegeven', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: x > 1\n  duiding: r.\ncasus \"c\"\n  x: 2\n  y: 3\n", ['E030']],
        ['casus: ontbrekende waarde', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: x > 1\n  duiding: r.\ncasus \"c\"\n  duiding: leeg.\n", ['E030']],
        ['casus: verkeerd type waarde', $base . "gegeven x\n  type: percentage\n  duiding: x.\nregel r: x > 50%\n  duiding: r.\ncasus \"c\"\n  x: veel\n", ['E030']],
        ['verwachting klopt niet', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: x > 1\n  duiding: r.\ncasus \"c\"\n  x: 2\n  verwacht r: nee\n", ['E031']],
        ['verwachting onbekende regel', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: x > 1\n  duiding: r.\ncasus \"c\"\n  x: 2\n  verwacht q: ja\n", ['E031']],
        ['delen door nul', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: 10 / x\n  duiding: r.\ncasus \"c\"\n  x: 0\n", ['E032']],
        ['ongeldige versie', "uitleg \"T\"\n  versie: september\n" . substr($base, 11), ['E041']],
        ['ongeldige taal', "uitleg \"T\"\n  taal: fr\n" . substr($base, 11), ['E041']],
        ['ongeldige url', $base . "bron \"B\"\n  url: javascript:alert(1)\n", ['E041']],
        ['ongeldige zekerheid', $base . "uitkomst\n  duiding: x.\n  zekerheid: heel zeker\n", ['E041']],
        ['zekerheid boven 100%', $base . "uitkomst\n  duiding: x.\n  zekerheid: 140%\n", ['E041']],
        ['ongeldig gewicht', $base . "factor \"F\"\n  gewicht: zwaar\n  duiding: f.\n", ['E041']],
        ['ongeldige richting', $base . "factor \"F\"\n  gewicht: 20%\n  richting: omhoog\n  duiding: f.\n", ['E041']],
        ['onbekend type', $base . "gegeven x\n  type: bedrag\n  duiding: x.\n", ['E041']],
        ['vakterm zonder uitleg', $base . "stap \"X\"\n  duiding: Het systeem gebruikt embeddings.\n", ['W101']],
        ['vakterm met begrip is goed', $base . "begrip embedding\n  duiding: Een reeks getallen voor betekenis.\nstap \"X\"\n  duiding: Het systeem gebruikt embeddings.\n", []],
        ['lange zin', $base . "stap \"X\"\n  duiding: " . str_repeat('woord ', 30) . "einde.\n", ['W102']],
        ['moeilijke tekst', $base . "stap \"X\"\n  duiding: " . str_repeat('Onvoorwaardelijke informatieverwerkingsprocedures veronderstellen interdisciplinaire verantwoordelijkheidsverdeling. ', 6) . "\n", ['W103']],
        ['geen beperking', str_replace("beperking \"B\"\n  duiding: Het kan niet alles.\n", '', $base), ['W104']],
        ['regels zonder casus', $base . "regel r: ja\n  duiding: r.\n", ['W106']],
        ['tabs', str_replace('  duiding: Een doel.', "\tduiding: Een doel.", $base), ['W107']],
        ['duiding gelijk aan techniek', $base . "stap \"X\"\n  duiding: Het model rekent een score uit.\n  techniek: Het model rekent een score uit.\n", ['W108']],
        ['geen doel', str_replace("doel\n  duiding: Een doel.\n", '', $base), ['W110']],
        ['geen toezicht', str_replace("toezicht\n  duiding: Iemand kijkt mee.\n", '', $base), ['W111']],
        ['geen route', str_replace("stap \"S\"\n  duiding: Het doet iets.\n", '', $base), ['W112']],
        ['gewichten boven 100%', $base . "factor \"A\"\n  gewicht: 70%\n  duiding: a.\nfactor \"B\"\n  gewicht: 50%\n  duiding: b.\n", ['W114']],
        ['naam achter doel', str_replace("doel\n", "doel \"Iets\"\n", $base), ['W115']],
        ['stuurteken', $base . "stap \"X\"\n  duiding: iets\x00 met een nul.\n", ['E006']],
        ['ongeldige UTF-8', $base . "stap \"X\"\n  duiding: iets \xC3\x28 kapot.\n", ['E006']],
        ['te groot bestand', "uitleg \"T\"\n" . str_repeat("# commentaar\n", 20000), ['E005']],
        ['dubbelzinnig getal in regel', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: x > 1.000\n  duiding: r.\ncasus \"c\"\n  x: 2\n", ['E020']],
        ['dubbelzinnig getal in casus', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: x > 1000\n  duiding: r.\ncasus \"c\"\n  x: 1.450\n", ['E030']],
        ['dubbelzinnig percentage in casus', $base . "gegeven x\n  type: percentage\n  duiding: x.\nregel r: x > 50%\n  duiding: r.\ncasus \"c\"\n  x: 1.250%\n", ['E030']],
        ['duidelijke getallen zijn goed', $base . "gegeven x\n  type: getal\n  duiding: x.\nregel r: x > 1000 en x < 0.125 + 2500,75\n  duiding: r.\ncasus \"c\"\n  x: 1450\n  verwacht r: ja\ncasus \"d\"\n  x: 1,450\n  verwacht r: nee\n", []],
    ];
    foreach ($cases as [$name, $src, $want]) {
        $r = aitl_check($src);
        $got = codes($r);
        $missing = array_diff($want, $got);
        $unexpected = $want === [] ? array_filter($got, fn($c) => $c[0] === 'E' || $c === 'W101') : [];
        ok("B $name → " . ($want ? implode('+', $want) : 'geen fout'), !$missing && !$unexpected, 'kreeg: ' . implode(',', $got));
    }
    // Elke melding heeft een geldig regelnummer.
    $r = aitl_check("uitleg \"T\"\nstap \"X\"\n  techniek: t.\n  metafoor: m.\nregel r: (1\n");
    ok('B regelnummers wijzen naar echte regels', !array_filter(array_merge($r['fouten'], $r['waarschuwingen']), fn($d) => $d['regel'] < 1 || $d['regel'] > 5));
    ok('B E010 op de juiste regel (2)', in_array(2, array_column(array_filter($r['fouten'], fn($d) => $d['code'] === 'E010'), 'regel'), true));
    ok('B E011 op de juiste regel (4)', in_array(4, array_column(array_filter($r['fouten'], fn($d) => $d['code'] === 'E011'), 'regel'), true));
    $r = aitl_check("uitleg \"T\"\nstapp \"X\"\n");
    ok("B suggestie bij tikfout ('stap')", str_contains($r['fouten'][0]['tip'] ?? '', "'stap'"), $r['fouten'][0]['tip'] ?? '');
}

// ---------------------------------------------------------------------------
if (part('C. Rekenregels')) {
    $env = ['p' => ['t' => 'percentage', 'v' => 0.72], 'g' => ['t' => 'getal', 'v' => 6.8], 'b' => ['t' => 'janee', 'v' => true],
        't' => ['t' => 'tekst', 'v' => 'huur'], 'd' => ['t' => 'datum', 'v' => '2026-09-01']];
    $typeOf = fn($n) => $env[$n]['t'] ?? throw new RuntimeException("onbekende naam '$n'");
    $valOf = fn($n) => $env[$n];
    $run = function (string $src) use ($typeOf, $valOf) {
        $e = aitl_parse_expr($src);
        aitl_type_of($e, $typeOf);
        return aitl_eval($e, $valOf);
    };
    $expect = [
        ['ja of nee en nee', true], ['niet ja of ja', true], ['niet (ja of ja)', false], ['1 + 2 * 3 = 7', true],
        ['(1 + 2) * 3 = 9', true], ['10 / 4 = 2,5', true], ['10 / 4 = 2.5', true], ['0,1 + 0,2 = 0,3', true],
        ['p < 80%', true], ['p >= 72%', true], ['p > 72%', false], ['g < 5,5', false], ['g >= 6,8', true],
        ['b', true], ['niet b', false], ['b en g > 6', true], ['t is "huur"', true], ['t is niet "koop"', true],
        ['t = "huur"', true], ['t != "huur"', false], ['d < 2026-10-01', true], ['d = 2026-09-01', true],
        ['-g < 0', true], ['- -g = g', true], ['p + 10% = 82%', true], ['p * 100 = 72', true], ['2 * p = 1,44', true],
        ['1 < 2 en 2 < 3 of nee', true], ['nee of nee of ja', true], ['ja en ja en nee', false],
        ['yes and not no', true], ['1 is 1', true], ['1 is not 2', true],
    ];
    foreach ($expect as [$src, $want]) {
        try { $v = $run($src); $pass = $v['v'] === $want; $got = var_export($v['v'], true); }
        catch (Throwable $e) { $pass = false; $got = $e->getMessage(); }
        ok("C $src → " . ($want ? 'ja' : 'nee'), $pass, $got);
    }
    $errors = [
        ['"a" < "b"', 'vergelijken'], ['ja + 1', 'rekenen'], ['p < 0,8', 'vergelijk'], ['1 / 0', 'nul'], ['(1', 'haakje'],
        ['1 2', 'onverwacht'], ['onbekend > 1', 'onbekende naam'], ['niet 1', "'niet'"], ['1 en ja', "'en'"], ['"open', 'aanhalingsteken'],
        ['1 $ 2', 'onverwacht teken'], [str_repeat('(', 60) . '1' . str_repeat(')', 60), 'te diep'], [str_repeat('niet ', 60) . 'ja', 'te diep'], ['', 'onvolledig'],
    ];
    foreach ($errors as [$src, $frag]) {
        try { $run($src); $pass = false; $got = 'geen fout'; }
        catch (Throwable $e) { $got = $e->getMessage(); $pass = str_contains($got, $frag); }
        ok("C fout: " . mb_substr($src, 0, 30) . " → '$frag'", $pass, $got);
    }
    // Laatste passende uitzondering wint; regels die regels gebruiken.
    $src = "uitleg \"T\"\ngegeven x\n  type: getal\n  duiding: x.\nregel a: \"laag\"\n  tenzij x > 5 dan \"midden\"\n  tenzij x > 8 dan \"hoog\"\n  duiding: a.\n"
        . "regel b: a is \"hoog\"\n  duiding: b.\nregel c: nee\n  tenzij b en x < 10 dan ja\n  duiding: c.\n"
        . "casus \"9\"\n  x: 9\n  verwacht a: hoog\n  verwacht b: ja\n  verwacht c: ja\ncasus \"6\"\n  x: 6\n  verwacht a: midden\n  verwacht b: nee\n  verwacht c: nee\n"
        . "casus \"2\"\n  x: 2\n  verwacht a: laag\n";
    $r = aitl_check($src);
    ok('C laatste passende uitzondering wint, en regels gebruiken regels', !array_filter($r['fouten'], fn($d) => $d['code'] === 'E031'),
        implode(' | ', array_column($r['fouten'], 'melding')));
    $routes = array_merge(...array_map(fn($c) => array_column($c['uitkomsten'], 'route'), $r['model']['casussen']));
    ok('C route noemt de gebruikte waarde (hier: x 9)', (bool)array_filter($routes, fn($s) => str_contains($s, '(hier: x 9)')), implode(' / ', $routes));
    ok('C route zonder uitzondering noemt de standaardwaarde', (bool)array_filter($routes, fn($s) => str_contains($s, 'de standaardwaarde')));
    $v = aitl_verbalize(aitl_parse_expr('x > 5 en niet (x < 2 of x = 3)'), ['x' => ['label' => 'leeftijd']], [], 'nl');
    ok('C voorwaarde in woorden', $v === 'leeftijd is hoger dan 5 en het is niet zo dat (leeftijd is lager dan 2 of leeftijd is 3)', $v);
    $v = aitl_verbalize(aitl_parse_expr('x >= 18 and y'), ['x' => ['label' => 'age'], 'y' => ['label' => 'member']], [], 'en');
    ok('C voorwaarde in woorden (Engels)', $v === 'age is at least 18 and member', $v);
}

// ---------------------------------------------------------------------------
if (part('D. Getallen in mensentaal')) {
    $freq = [[0.72, 'ongeveer 7 op de 10'], [0.87, 'ongeveer 9 op de 10'], [0.1, 'ongeveer 1 op de 10'], [0.5, 'ongeveer 5 op de 10'],
        [0.94, 'ongeveer 94 op de 100'], [0.999, 'ongeveer 999 op de 1.000'], [0.04, 'ongeveer 4 op de 100'], [0.003, 'ongeveer 3 op de 1.000'],
        [0.0001, 'minder dan 1 op de 1.000'], [0.0, 'nooit'], [1.0, 'altijd']];
    foreach ($freq as [$p, $want]) ok("D frequentie $p → $want", aitl_frequency($p) === $want, aitl_frequency($p));
    ok('D frequentie Engels', aitl_frequency(0.72, 'en') === 'about 7 in 10', aitl_frequency(0.72, 'en'));
    $like = [[0.995, 'vrijwel zeker'], [0.9, 'zeer waarschijnlijk'], [0.75, 'waarschijnlijk'], [0.6, 'eerder wel dan niet'],
        [0.5, 'even waarschijnlijk wel als niet'], [0.35, 'eerder niet'], [0.15, 'onwaarschijnlijk'], [0.02, 'zeer onwaarschijnlijk']];
    foreach ($like as [$p, $want]) ok("D kans $p → $want", aitl_likelihood($p) === $want, aitl_likelihood($p));
    ok('D getal nl', aitl_num(1250.5) === '1.250,5' && aitl_num(6.80) === '6,8' && aitl_num(72.0) === '72', aitl_num(1250.5) . ' ' . aitl_num(6.8));
    ok('D getal en', aitl_num(1250.5, 'en') === '1,250.5', aitl_num(1250.5, 'en'));
    ok('D zekerheidstekst', str_contains(aitl_confidence_text(0.87), 'ongeveer 9 goed hebben, en 1 niet'), aitl_confidence_text(0.87));
    $html = aitl_rich('De kans is 72% en 100% is altijd.', [], 'nl');
    $sv = aitl_check(file_get_contents(__DIR__ . '/../voorbeelden/studievoortgang.aitl'));
    $svh = aitl_render_html($sv['model']);
    ok('D percentage in casus krijgt frequentie', str_contains($svh, '<td>72% <span class="freq">(ongeveer 7 op de 10)</span></td>') && str_contains($svh, '<td>nee</td>'), '');
    ok('D percentage in tekst krijgt frequentie', str_contains($html, '72% <span class="freq">(ongeveer 7 op de 10)</span>') && !str_contains($html, '100% <span'), $html);
}

// ---------------------------------------------------------------------------
if (part('E. Leesbaarheid')) {
    $syl = ['leesbaarheid' => 3, 'aanwezigheid' => 4, 'vrijwel' => 2, 'ruis' => 1, 'computer' => 3, 'uitleg' => 2, 'eeuw' => 1, 'algoritme' => 4];
    foreach ($syl as $w => $n) ok("E lettergrepen $w = $n", aitl_syllables($w) === $n, (string)aitl_syllables($w));
    ok('E zinnen: afkortingen breken niet', count(aitl_sentences('Dit is bijv. een test. Nog een zin.')) === 2);
    ok('E zinnen: decimaal breekt niet', count(aitl_sentences('Het cijfer is 5,5. Dat is genoeg.')) === 2);
    $easy = aitl_readability('De kat zit op de mat. Het is warm. Hij slaapt.')['score'];
    $hard = aitl_readability('Onvoorwaardelijke informatieverwerkingsprocedures veronderstellen interdisciplinaire verantwoordelijkheidsverdeling binnen organisaties.')['score'];
    ok("E eenvoudige tekst scoort hoger ($easy > $hard)", $easy > $hard && $easy >= 80 && $hard <= 30);
}

// ---------------------------------------------------------------------------
if (part('F. Veiligheid van de weergave')) {
    $x = '<script>alert(1)</script><img src=x onerror=alert(2)>"\'&';
    $src = "uitleg \"$x\"\n  organisatie: $x\n  systeem: $x\n  doelgroep: $x\n  samenvatting: $x\ndoel\n  duiding: $x\n  techniek: $x\n  metafoor: $x\n    grens: $x\n"
        . "begrip \"$x\"\n  duiding: $x\n  ook: $x\nstap \"$x\"\n  duiding: $x 50%\n  techniek: $x\n  waarde: $x\n  zekerheid: 50%\n"
        . "factor \"$x\"\n  gewicht: 10%\n  duiding: $x\ngegeven g\n  type: tekst\n  label: $x\n  duiding: $x\n  eenheid: $x\n"
        . "regel r: g is \"a\"\n  label: $x\n  tenzij g is \"b\" dan nee, want $x\n  duiding: $x\ncasus \"$x\"\n  g: $x\n  duiding: $x\n"
        . "beperking \"$x\"\n  duiding: $x\ntoezicht\n  duiding: $x\n  contact: $x\nbron \"$x\"\n  url: https://example.org/?q=\"><script>x</script>\n  duiding: $x\n";
    $r = aitl_check($src);
    $html = $r['model'] ? aitl_render_html($r['model'], ['publicatie' => ['org' => $x, 'datum' => $x, 'id' => $x, 'bronUrl' => 'https://example.org/x.aitl', 'ondertekend' => true]]) : '';
    ok('F kwaadaardige tekst geeft toch een rapport', $html !== '', implode(',', codes($r)));
    $p = html_ok($html);
    ok('F geen script, geen event-handlers, alleen veilige links', !$p, implode('; ', array_slice($p, 0, 5)));
    ok('F geen onge-escapete tags', !preg_match('~<(script|img)\b~i', $html));
    $r2 = aitl_check("uitleg \"T\"\nbron \"B\"\n  url: javascript:alert(1)\n");
    $h2 = $r2['model'] ? aitl_render_html($r2['model']) : '';
    ok('F javascript-url wordt geweigerd en niet gelinkt', in_array('E041', codes($r2), true) && !str_contains($h2, 'href="javascript'));
    $sv = aitl_check(file_get_contents(__DIR__ . '/../voorbeelden/studievoortgang.aitl'));
    $fh = aitl_render_html($sv['model'], ['lettertypeCss' => '@font-face{font-family:"Inter"}</style><script>alert(1)</script>']);
    $probF = html_ok($fh);
    ok('F lettertypeCss kan de stijl niet verlaten', !$probF && !str_contains($fh, '<script') && substr_count($fh, '</style>') === 1 && str_contains($fh, '@font-face'));
    ok('F rapport zonder schreeflettertype', !preg_match('~serif"|Georgia|Palatino|Iowan~', aitl_render_html($sv['model'])));
}

// ---------------------------------------------------------------------------
if (part('G. Vaste uitkomsten en grenzen')) {
    $src = file_get_contents($examples[0]);
    ok('G controle twee keer gelijk', json_encode(aitl_check($src)) === json_encode(aitl_check($src)));
    ok('G bron-vingerafdruk in het model', aitl_check($src)['model']['bronHash'] === hash('sha256', $src));
    $crlf = str_replace("\n", "\r\n", $src);
    ok('G Windows-regeleinden werken hetzelfde', aitl_check($crlf)['ok'] && json_encode(aitl_check($crlf)['model']['casussen']) === json_encode(aitl_check($src)['model']['casussen']));
    ok('G BOM aan het begin wordt genegeerd', aitl_check("\xEF\xBB\xBF" . $src)['ok']);
    $big = "uitleg \"T\"\n" . str_repeat("stap \"S\"\n  duiding: Een korte stap.\n", 1500);
    $t = microtime(true); $r = aitl_check($big); $ms = (microtime(true) - $t) * 1000;
    ok('G 1.500 stappen binnen 3 s (' . (int)$ms . ' ms)', $ms < 3000 && $r['model'] !== null);
    $many = "uitleg \"T\"\n" . str_repeat("x\n", 5001);
    ok('G meer dan 5.000 regels → E005', in_array('E005', codes(aitl_check($many)), true));
    $chain = "uitleg \"T\"\ngegeven x\n  type: getal\n  duiding: x.\nregel r0: x\n  duiding: r.\n";
    for ($i = 1; $i <= 150; $i++) $chain .= "regel r$i: r" . ($i - 1) . " + 1\n  duiding: r.\n";
    $chain .= "casus \"c\"\n  x: 1\n  verwacht r150: 151\n";
    $r = aitl_check($chain);
    ok('G keten van 150 regels rekent goed door', $r['ok'], implode(' | ', array_column($r['fouten'], 'melding')));
}

// ---------------------------------------------------------------------------
if (part('H. Engels')) {
    $en = array_values(array_filter($examples, fn($f) => str_contains(file_get_contents($f), 'language: en')));
    ok('H er is een Engels voorbeeld', (bool)$en);
    if ($en) {
        $r = aitl_check(file_get_contents($en[0]));
        $html = aitl_render_html($r['model']);
        ok('H Engelse sleutelwoorden worden herkend', $r['ok'] && $r['model']['taal'] === 'en');
        ok('H rapport is Engels (lang, koppen)', str_contains($html, '<html lang="en">') && str_contains($html, 'How does it work?') && str_contains($html, 'Where the comparison ends'));
    }
}

// ---------------------------------------------------------------------------
if (part('I. Opdrachtregel')) {
    $bin = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../bin/aitl');
    exec("$bin controleer " . escapeshellarg($examples[0]) . ' 2>&1', $o, $code);
    ok('I controleer voorbeeld → exit 0', $code === 0, implode("\n", $o));
    $tmp = tempnam(sys_get_temp_dir(), 'aitl');
    file_put_contents($tmp, "uitleg \"T\"\nstap \"X\"\n  techniek: t.\n");
    exec("$bin controleer " . escapeshellarg($tmp) . ' 2>&1', $o2, $code2);
    ok('I controleer met fout → exit 1 en regelnummer', $code2 === 1 && str_contains(implode("\n", $o2), ':2: fout E010'), implode("\n", $o2));
    exec("$bin 2>&1", $o3, $code3);
    ok('I zonder opdracht → exit 2 met uitleg', $code3 === 2 && str_contains(implode("\n", $o3), 'Gebruik'));
    $out = $tmp . '.html';
    exec("$bin rapport " . escapeshellarg($examples[0]) . ' -o ' . escapeshellarg($out) . ' 2>&1', $o4, $code4);
    ok('I rapport schrijft HTML', $code4 === 0 && str_starts_with((string)@file_get_contents($out), '<!doctype html>'));
    exec("$bin check " . escapeshellarg(__DIR__ . '/../voorbeelden/ai-transparent-label.aitl') . ' 2>&1', $o5, $code5);
    $t5 = implode("\n", $o5);
    ok('I Engelse uitleg → Engelse samenvatting', $code5 === 0 && str_contains($t5, 'Readability:') && str_contains($t5, '0 error(s)') && !str_contains($t5, 'Leesbaarheid'), $t5);
    exec("$bin casus - < " . escapeshellarg($examples[0]) . ' 2>&1', $o6, $code6);
    ok('I bron via standaardinvoer (-)', $code6 === 0 && str_contains(implode("\n", $o6), '■'), implode("\n", $o6));
    @unlink($tmp); @unlink($out);
}

// ---------------------------------------------------------------------------
if (part('K. Documentatie')) {
    $readme = @file_get_contents(__DIR__ . '/../README.md');
    ok('K README bestaat', is_string($readme) && $readme !== '');
    if (is_string($readme) && preg_match('~```aitl\n(.*?)```~s', $readme, $m)) {
        $r = aitl_check($m[1]);
        ok('K voorbeeld in README is foutloos en zonder waarschuwingen', $r['ok'] && !$r['waarschuwingen'], implode(',', codes($r)));
        $c = $r['model']['casussen'][0]['uitkomsten'][0]['route'] ?? '';
        ok('K route in README klopt met de uitvoer', str_contains($readme, $c) && $c !== '', $c);
    } else ok('K README bevat een aitl-voorbeeld', false);
    ok('K SPEC en LICENSE aanwezig', is_file(__DIR__ . '/../SPEC.md') && is_file(__DIR__ . '/../LICENSE'));
    // Elke meldingscode uit de implementatie staat in de specificatie.
    $src = file_get_contents(__DIR__ . '/../src/aitl.php');
    preg_match_all("~'([EW]\\d{3})'~", $src, $cm);
    $spec = (string)@file_get_contents(__DIR__ . '/../SPEC.md');
    $missing = array_values(array_filter(array_unique($cm[1]), fn($c) => !str_contains($spec, "| $c |")));
    ok('K alle meldingscodes staan in SPEC.md', !$missing, implode(',', $missing));
}

// ---------------------------------------------------------------------------
if (part('J. Fuzz: beschadigde bronnen')) {
    $n = (int)(getenv('FUZZ_N') ?: 300);
    mt_srand((int)(getenv('FUZZ_SEED') ?: 20260928));
    $tokens = ["uitleg", "stap", "regel r: ja", "tenzij", "dan", "want", "metafoor:", "grens:", "duiding:", "techniek:", "casus \"c\"",
        "verwacht r:", "  ", "\t", "\"", "“", "(", ")", "%", "<script>", "é", "\x00", "0,5", "ja", "nee", ":", "#", "\n", "gegeven x", "type: getal"];
    foreach ($examples as $f) {
        $src0 = file_get_contents($f);
        $lines0 = explode("\n", $src0);
        $exceptions = []; $warnings = []; $badLines = 0; $renderFail = 0; $renderWhy = [];
        for ($i = 0; $i < $n; $i++) {
            $lines = $lines0;
            $k = mt_rand(1, 4);
            for ($j = 0; $j < $k; $j++) {
                $li = mt_rand(0, count($lines) - 1);
                switch (mt_rand(0, 6)) {
                    case 0: array_splice($lines, $li, 1); break;
                    case 1: array_splice($lines, $li, 0, [$lines[$li]]); break;
                    case 2: $lines[$li] = str_repeat(' ', mt_rand(0, 8)) . ltrim($lines[$li]); break;
                    case 3: $p = mt_rand(0, max(0, strlen($lines[$li]))); $lines[$li] = substr($lines[$li], 0, $p) . $tokens[mt_rand(0, count($tokens) - 1)] . substr($lines[$li], $p); break;
                    case 4: $lines[$li] = substr($lines[$li], 0, mt_rand(0, strlen($lines[$li]))); break;
                    case 5: $lines[$li] = strrev($lines[$li]); break;
                    case 6: if ($lines[$li] !== '') { $p = mt_rand(0, strlen($lines[$li]) - 1); $lines[$li][$p] = chr(mt_rand(32, 126)); } break;
                }
            }
            $src = implode("\n", $lines);
            $max = count($lines);
            set_error_handler(function ($no, $str, $file, $line) use (&$warnings) { if (error_reporting() & $no) $warnings[] = "$str @" . basename($file) . ":$line"; return true; });
            try {
                $r = aitl_check($src);
                foreach (array_merge($r['fouten'], $r['waarschuwingen']) as $d) if ($d['regel'] < 1 || $d['regel'] > $max + 1) $badLines++;
                if ($r['model']) {
                    $html = aitl_render_html($r['model']);
                    if ($r['ok'] && ($hp = html_ok($html))) { $renderFail++; $renderWhy[] = $hp[0]; }
                }
            } catch (Throwable $e) {
                $exceptions[] = $e->getMessage() . ' @' . basename($e->getFile()) . ':' . $e->getLine();
            }
            restore_error_handler();
        }
        $name = basename($f);
        ok("J fuzz $name: geen exceptions ($n mutaties)", !$exceptions, count($exceptions) . 'x: ' . implode(' | ', array_slice(array_unique($exceptions), 0, 3)));
        ok("J fuzz $name: geen PHP-warnings", !$warnings, count($warnings) . 'x: ' . implode(' | ', array_slice(array_unique($warnings), 0, 3)));
        ok("J fuzz $name: regelnummers altijd geldig", $badLines === 0, "$badLines ongeldig");
        ok("J fuzz $name: geldige bron geeft veilige HTML", $renderFail === 0, "$renderFail mislukt: " . implode(' | ', array_slice(array_unique($renderWhy), 0, 3)));
    }
}

$total = count($results);
printf("\n%d/%d geslaagd%s\n", $total - $failures, $total, $failures ? ", \033[31m$failures GEFAALD\033[0m" : " \033[32m— alles groen\033[0m");
exit($failures ? 1 : 0);
