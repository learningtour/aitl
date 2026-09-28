<?php
// AITL — rapportweergave: van rapportmodel naar een HTML-pagina voor iedereen.
//
// Uitgangspunten:
//  - Gewone taal eerst; de techniek staat erbij, uitklapbaar ("Technisch bekeken").
//  - Elke vergelijking toont ook waar ze ophoudt.
//  - Zekerheid en kansen ook als "ongeveer 7 op de 10", met tien stippen.
//  - Vaktermen in de tekst verwijzen naar de begrippenlijst.
//  - Geen JavaScript en geen externe bestanden: veilig, printbaar en duurzaam.
//
// Licentie: MIT — https://github.com/learningtour/aitl

declare(strict_types=1);

require_once __DIR__ . '/aitl.php';

const AITL_UI = [
    'nl' => [
        'badge' => 'Uitgelegd met AITL', 'voor' => 'voor', 'versie' => 'versie',
        'kort' => 'In het kort', 'doel' => 'Waarvoor is het?', 'hoe' => 'Hoe werkt het?', 'weegt' => 'Wat weegt mee?',
        'regels' => 'Welke regels gelden?', 'casussen' => 'Voorbeelden: zo pakt het uit', 'uitkomst' => 'De uitkomst',
        'beperkingen' => 'Wat kan het niet?', 'toezicht' => 'Wie houdt toezicht?', 'begrippen' => 'Begrippen',
        'bronnen' => 'Bronnen', 'over' => 'Over deze uitleg', 'stap' => 'Stap',
        'vergelijk' => 'Vergelijk het met', 'grens' => 'Waar de vergelijking ophoudt', 'technisch' => 'Technisch bekeken',
        'instelling' => 'Gebruikte instelling of waarde', 'zekerheid' => 'Hoe zeker?', 'standaard' => 'Standaard',
        'tenzij' => 'Uitzondering', 'als' => 'als', 'dan' => 'dan', 'want' => 'want', 'gegevens' => 'Gegevens in dit voorbeeld',
        'uitkomstVan' => 'Uitkomst', 'controles' => 'Alle controles', 'geldt' => 'geldt', 'geldtNiet' => 'geldt niet', 'hier' => 'hier',
        'verwachtOk' => 'komt overeen met de verwachting', 'verwachtNiet' => 'wijkt af van de verwachting',
        'verhoogt' => 'maakt de uitkomst hoger', 'verlaagt' => 'maakt de uitkomst lager', 'beide' => 'kan de uitkomst hoger én lager maken',
        'contact' => 'Contact', 'ook' => 'Ook', 'principes' => 'De vijf AITL-principes',
        'methode' => 'Dit rapport is gemaakt met AITL (AI Transparent Language), een open taal om algoritmes uit te leggen. Elke technische uitleg heeft een duiding in gewone taal, elke vergelijking laat zien waar ze ophoudt, en bij elke uitkomst staat de route.',
        'leesbaarheid' => 'Leesbaarheid van de gewone-taalteksten', 'wpz' => 'woorden per zin gemiddeld',
        'bronhash' => 'Vingerafdruk van de bron', 'gemaakt' => 'Gemaakt met AITL', 'onvolledig' => 'Niet doorgerekend: er ontbreken gegevens.',
        'gepubliceerd' => 'Gepubliceerd door', 'op' => 'op', 'ondertekend' => 'digitaal ondertekend', 'bekijkBron' => 'Bekijk de AITL-bron',
        'lagen' => 'Deze uitleg heeft twee lagen: eerst gewone taal, daarna — uitklapbaar — de techniek.',
        'gegevensLijst' => 'Welke gegevens gebruikt het?', 'type' => ['getal' => 'getal', 'percentage' => 'percentage', 'tekst' => 'tekst', 'janee' => 'ja of nee', 'datum' => 'datum'],
    ],
    'en' => [
        'badge' => 'Explained with AITL', 'voor' => 'for', 'versie' => 'version',
        'kort' => 'In short', 'doel' => 'What is it for?', 'hoe' => 'How does it work?', 'weegt' => 'What counts?',
        'regels' => 'Which rules apply?', 'casussen' => 'Examples: how it works out', 'uitkomst' => 'The outcome',
        'beperkingen' => 'What can it not do?', 'toezicht' => 'Who is in control?', 'begrippen' => 'Terms',
        'bronnen' => 'Sources', 'over' => 'About this explanation', 'stap' => 'Step',
        'vergelijk' => 'Think of it as', 'grens' => 'Where the comparison ends', 'technisch' => 'Technical view',
        'instelling' => 'Setting or value used', 'zekerheid' => 'How certain?', 'standaard' => 'Standard',
        'tenzij' => 'Exception', 'als' => 'if', 'dan' => 'then', 'want' => 'because', 'gegevens' => 'Data in this example',
        'uitkomstVan' => 'Outcome', 'controles' => 'All checks', 'geldt' => 'applies', 'geldtNiet' => 'does not apply', 'hier' => 'here',
        'verwachtOk' => 'matches the expectation', 'verwachtNiet' => 'differs from the expectation',
        'verhoogt' => 'raises the outcome', 'verlaagt' => 'lowers the outcome', 'beide' => 'can raise or lower the outcome',
        'contact' => 'Contact', 'ook' => 'Also', 'principes' => 'The five AITL principles',
        'methode' => 'This report was made with AITL (AI Transparent Language), an open language for explaining algorithms. Every technical explanation comes with plain language, every comparison shows where it ends, and every outcome shows its route.',
        'leesbaarheid' => 'Readability of the plain-language texts', 'wpz' => 'words per sentence on average',
        'bronhash' => 'Fingerprint of the source', 'gemaakt' => 'Made with AITL', 'onvolledig' => 'Not calculated: data is missing.',
        'gepubliceerd' => 'Published by', 'op' => 'on', 'ondertekend' => 'digitally signed', 'bekijkBron' => 'View the AITL source',
        'lagen' => 'This explanation has two layers: plain language first, then — expandable — the technology.',
        'gegevensLijst' => 'Which data does it use?', 'type' => ['getal' => 'number', 'percentage' => 'percentage', 'tekst' => 'text', 'janee' => 'yes or no', 'datum' => 'date'],
    ],
];

function aitl_h(?string $s): string {
    $s = preg_replace('~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]~', '', (string)$s) ?? '';
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/**
 * Gewone-taaltekst verrijken: alinea's, verwijzingen naar begrippen (eerste keer
 * per tekst) en percentages met hun natuurlijke frequentie.
 */
function aitl_rich(?string $text, array $terms, string $lang): string {
    if ($text === null || trim($text) === '') return '';
    $out = [];
    foreach (preg_split('~\n{2,}~', $text) as $para) {
        $s = aitl_h(trim($para));
        $slots = []; $used = [];
        foreach ($terms as [$form, $anchor, $tip]) {
            if (isset($used[$anchor])) continue;
            $re = '~(?<![\p{L}\p{N}#&;\x01])(' . preg_quote(aitl_h($form), '~') . ')(?![\p{L}\p{N}])~iu';
            $s = preg_replace_callback($re, function ($m) use (&$slots, &$used, $anchor, $tip) {
                if (isset($used[$anchor])) return $m[0];
                $used[$anchor] = true;
                $slots[] = '<a class="term" href="#' . aitl_h($anchor) . '" title="' . aitl_h($tip) . '">' . $m[1] . '</a>';
                return "\x01" . (count($slots) - 1) . "\x02";
            }, $s, 1) ?? $s;
        }
        $s = preg_replace_callback('~(\d+(?:[.,]\d+)?)\s?%~u', function ($m) use ($lang) {
            $p = (float)str_replace(',', '.', $m[1]) / 100;
            if ($p <= 0 || $p >= 1) return $m[0];
            return $m[0] . ' <span class="freq">(' . aitl_h(aitl_frequency($p, $lang)) . ')</span>';
        }, $s) ?? $s;
        $s = preg_replace_callback('~\x01(\d+)\x02~', fn($m) => $slots[(int)$m[1]] ?? '', $s) ?? $s;
        $out[] = '<p>' . $s . '</p>';
    }
    return implode('', $out);
}

function aitl_plain(?string $text): string {
    if ($text === null || trim($text) === '') return '';
    return implode('', array_map(fn($p) => '<p>' . aitl_h(trim($p)) . '</p>', preg_split('~\n{2,}~', $text)));
}

function aitl_dots(float $p): string {
    $n = (int)round($p * 10);
    $d = '';
    for ($i = 0; $i < 10; $i++) $d .= $i < $n ? '<span class="dot on" aria-hidden="true"></span>' : '<span class="dot" aria-hidden="true"></span>';
    return '<span class="dots" role="img" aria-label="' . $n . ' / 10">' . $d . '</span>';
}

/** Eén uitleg-item: duiding, vergelijking, zekerheid en uitklapbare techniek. */
function aitl_render_item(array $it, array $terms, array $ui, string $lang, string $extraTech = ''): string {
    $h = aitl_rich($it['duiding'] ?? null, $terms, $lang);
    if (!empty($it['metafoor'])) {
        $h .= '<div class="vergelijking"><p><strong>' . aitl_h($ui['vergelijk']) . ':</strong> ' . aitl_h($it['metafoor']['tekst']) . '</p>';
        if (!empty($it['metafoor']['grens'])) $h .= '<p class="grens"><strong>' . aitl_h($ui['grens']) . ':</strong> ' . aitl_h($it['metafoor']['grens']) . '</p>';
        $h .= '</div>';
    }
    if (!empty($it['zekerheid'])) {
        $z = $it['zekerheid'];
        $h .= '<div class="zekerheid"><p class="zlabel"><strong>' . aitl_h($ui['zekerheid']) . '</strong> ' . aitl_dots($z['p'])
            . ' <span class="zwoord">' . aitl_h($z['tekst']) . ' · ' . aitl_h($z['woorden']) . '</span></p><p>' . aitl_h($z['uitleg']) . '</p></div>';
    }
    $tech = '';
    if (!empty($it['techniek'])) $tech .= aitl_plain($it['techniek']);
    if (!empty($it['waarde'])) $tech .= '<p><strong>' . aitl_h($ui['instelling']) . ':</strong> <code>' . aitl_h($it['waarde']) . '</code></p>';
    $tech .= $extraTech;
    if ($tech !== '') $h .= '<details class="tech"><summary>' . aitl_h($ui['technisch']) . '</summary><div class="techbody">' . $tech . '</div></details>';
    return $h;
}

/**
 * Het volledige rapport als zelfstandige HTML-pagina.
 * $opts (optioneel): publicatie => [org, datum, id, url, bronUrl, ondertekend(bool)],
 *                    lettertypeCss => @font-face-regels van de aanroeper (bijv. voor Inter);
 *                    zonder deze optie gebruikt het rapport het systeemlettertype.
 */
function aitl_render_html(array $m, array $opts = []): string {
    $lang = $m['taal'] === 'en' ? 'en' : 'nl';
    $ui = AITL_UI[$lang];
    // Vaktermen uit de begrippenlijst, langste eerst.
    $terms = [];
    foreach ($m['begrippen'] as $b) {
        foreach (array_merge([$b['titel']], $b['ook'] ? preg_split('~\s*,\s*~u', $b['ook']) : []) as $form) {
            if (trim((string)$form) !== '') $terms[] = [trim((string)$form), $b['anker'], (string)$b['duiding']];
        }
    }
    usort($terms, fn($a, $b) => mb_strlen($b[0]) <=> mb_strlen($a[0]) ?: strcmp($a[0], $b[0]));

    $meta = array_filter([$m['systeem'], $m['organisatie'], $m['versie'] ? $ui['versie'] . ' ' . $m['versie'] : null,
        $m['doelgroep'] ? $ui['voor'] . ' ' . $m['doelgroep'] : null]);
    $b = '<header class="kop"><p class="badge">' . aitl_h($ui['badge']) . '</p><h1>' . aitl_h($m['titel']) . '</h1>'
        . ($meta ? '<p class="meta">' . implode(' · ', array_map('aitl_h', $meta)) . '</p>' : '')
        . '<p class="lagen">' . aitl_h($ui['lagen']) . '</p></header><main>';

    // In het kort + doel
    if ($m['samenvatting'] || $m['doel']) {
        $b .= '<section id="kort"><h2>' . aitl_h($ui['kort']) . '</h2>';
        if ($m['samenvatting']) $b .= '<div class="lead">' . aitl_rich($m['samenvatting'], $terms, $lang) . '</div>';
        if ($m['doel']) $b .= '<h3>' . aitl_h($ui['doel']) . '</h3>' . aitl_render_item($m['doel'], $terms, $ui, $lang);
        $b .= '</section>';
    }

    // Gegevens
    if ($m['gegevens']) {
        $b .= '<section id="gegevens"><h2>' . aitl_h($ui['gegevensLijst']) . '</h2><dl class="gegevens">';
        foreach ($m['gegevens'] as $g) {
            $tech = trim(($g['techniek'] ? $g['techniek'] : '') . ($g['bron'] ? "\n\n" . ($lang === 'en' ? 'Source: ' : 'Bron: ') . $g['bron'] : ''));
            $b .= '<dt>' . aitl_h($g['label']) . ' <span class="type">' . aitl_h($ui['type'][$g['type']] ?? $g['type'])
                . ($g['eenheid'] ? ', ' . aitl_h($g['eenheid']) : '') . '</span></dt><dd>' . aitl_rich($g['duiding'], $terms, $lang)
                . ($tech !== '' ? '<details class="tech"><summary>' . aitl_h($ui['technisch']) . '</summary><div class="techbody">' . aitl_plain($tech) . '<p><code>' . aitl_h($g['naam']) . '</code></p></div></details>' : '')
                . '</dd>';
        }
        $b .= '</dl></section>';
    }

    // Stappen
    if ($m['stappen']) {
        $b .= '<section id="hoe"><h2>' . aitl_h($ui['hoe']) . '</h2><ol class="stappen">';
        foreach ($m['stappen'] as $i => $s) {
            $b .= '<li class="stap"><h3><span class="nr">' . ($i + 1) . '</span> ' . aitl_h($s['titel']) . '</h3>' . aitl_render_item($s, $terms, $ui, $lang) . '</li>';
        }
        $b .= '</ol></section>';
    }

    // Factoren
    if ($m['factoren']) {
        $b .= '<section id="weegt"><h2>' . aitl_h($ui['weegt']) . '</h2><div class="factoren">';
        foreach ($m['factoren'] as $f) {
            $w = $f['gewichtP'] !== null ? (int)round($f['gewichtP'] * 100) : 0;
            $dir = $f['richting'] ? (['verhoogt' => 'verhoogt', 'increases' => 'verhoogt', 'verlaagt' => 'verlaagt', 'decreases' => 'verlaagt', 'beide' => 'beide', 'both' => 'beide'][$f['richting']] ?? null) : null;
            $b .= '<div class="factor"><h3>' . aitl_h($f['titel']) . ' <span class="gewicht">' . aitl_h($f['gewicht']) . '</span></h3>'
                . '<div class="bar" role="img" aria-label="' . aitl_h($f['gewicht']) . '"><i style="width:' . $w . '%"></i></div>'
                . ($dir ? '<p class="richting">' . aitl_h($ui[$dir]) . '</p>' : '')
                . aitl_render_item($f, $terms, $ui, $lang) . '</div>';
        }
        $b .= '</div></section>';
    }

    // Regels
    if ($m['regels']) {
        $b .= '<section id="regels"><h2>' . aitl_h($ui['regels']) . '</h2>';
        foreach ($m['regels'] as $r) {
            $b .= '<div class="regel"><h3>' . aitl_h(mb_strtoupper(mb_substr($r['label'], 0, 1)) . mb_substr($r['label'], 1)) . '</h3>'
                . aitl_rich($r['duiding'], $terms, $lang)
                . '<ul class="uitzonderingen"><li><strong>' . aitl_h($ui['standaard']) . ':</strong> ' . aitl_h($r['standaard']) . '</li>';
            foreach ($r['uitzonderingen'] as $x) {
                $b .= '<li><strong>' . aitl_h($ui['tenzij']) . ':</strong> ' . aitl_h($ui['als']) . ' ' . aitl_h($x['voorwaarde']) . ', '
                    . aitl_h($ui['dan']) . ' ' . aitl_h($x['dan']) . ($x['want'] ? ' <span class="want">(' . aitl_h($ui['want']) . ' ' . aitl_h($x['want']) . ')</span>' : '') . '</li>';
            }
            $b .= '</ul><details class="tech"><summary>' . aitl_h($ui['technisch']) . '</summary><div class="techbody">'
                . aitl_plain($r['techniek']) . '<pre>' . aitl_h($r['bron']) . '</pre></div></details></div>';
        }
        $b .= '</section>';
    }

    // Casussen
    if ($m['casussen']) {
        $b .= '<section id="casussen"><h2>' . aitl_h($ui['casussen']) . '</h2>';
        foreach ($m['casussen'] as $c) {
            $b .= '<div class="casus"><h3>' . aitl_h($c['titel']) . '</h3>' . aitl_rich($c['duiding'], $terms, $lang);
            if ($c['gegevens']) {
                $b .= '<table class="gegevens"><caption>' . aitl_h($ui['gegevens']) . '</caption><tbody>';
                foreach ($c['gegevens'] as $g) $b .= '<tr><th scope="row">' . aitl_h($g['label']) . '</th><td>' . aitl_h($g['waarde'])
                    . (!empty($g['frequentie']) ? ' <span class="freq">(' . aitl_h($g['frequentie']) . ')</span>' : '') . '</td></tr>';
                $b .= '</tbody></table>';
            }
            if (!$c['compleet']) $b .= '<p class="let">' . aitl_h($ui['onvolledig']) . '</p>';
            foreach ($c['uitkomsten'] as $u) {
                $b .= '<div class="uitkomstregel"><p class="waarde"><span class="lbl">' . aitl_h($ui['uitkomstVan']) . ' — ' . aitl_h($u['regel']) . ':</span> <strong>'
                    . aitl_h($u['waarde']) . '</strong></p><p class="route">' . aitl_h($u['route']) . '</p>';
                if ($u['controles']) {
                    $b .= '<details class="tech"><summary>' . aitl_h($ui['controles']) . '</summary><div class="techbody"><ol class="controles">';
                    foreach ($u['controles'] as $ch) {
                        $here = $ch['hier'] ? ' <span class="hier">(' . aitl_h($ui['hier']) . ': ' . aitl_h(implode(', ', array_map(fn($x) => $x['label'] . ' ' . $x['waarde'], $ch['hier']))) . ')</span>' : '';
                        $b .= '<li class="' . ($ch['geldt'] ? 'ja' : 'nee') . '">' . ($ch['geldt'] ? '✔ ' : '✗ ') . aitl_h($ch['voorwaarde']) . ' — '
                            . aitl_h($ch['geldt'] ? $ui['geldt'] : $ui['geldtNiet']) . $here . ($ch['geldt'] ? ' → ' . aitl_h($ch['dan']) : '') . '</li>';
                    }
                    $b .= '</ol></div></details>';
                }
                $b .= '</div>';
            }
            foreach ($c['verwacht'] as $v) {
                $b .= '<p class="verwacht ' . ($v['klopt'] ? 'ok' : 'nok') . '">' . ($v['klopt'] ? '✔ ' : '✗ ') . aitl_h($v['regel']) . ' = ' . aitl_h($v['verwacht']) . ': '
                    . aitl_h($v['klopt'] ? $ui['verwachtOk'] : $ui['verwachtNiet']) . '</p>';
            }
            $b .= '</div>';
        }
        $b .= '</section>';
    }

    // Uitkomsten, beperkingen, toezicht
    foreach (['uitkomsten' => 'uitkomst', 'beperkingen' => 'beperkingen'] as $key => $title) {
        if (!$m[$key]) continue;
        $b .= '<section id="' . $key . '"><h2>' . aitl_h($ui[$title]) . '</h2>';
        foreach ($m[$key] as $it) $b .= '<div class="' . ($key === 'beperkingen' ? 'beperking' : 'uitkomst') . '">' . ($it['titel'] ? '<h3>' . aitl_h($it['titel']) . '</h3>' : '') . aitl_render_item($it, $terms, $ui, $lang) . '</div>';
        $b .= '</section>';
    }
    if ($m['toezicht']) {
        $b .= '<section id="toezicht"><h2>' . aitl_h($ui['toezicht']) . '</h2>' . aitl_render_item($m['toezicht'], $terms, $ui, $lang)
            . (!empty($m['toezicht']['contact']) ? '<p><strong>' . aitl_h($ui['contact']) . ':</strong> ' . aitl_h($m['toezicht']['contact']) . '</p>' : '') . '</section>';
    }

    // Begrippen en bronnen
    if ($m['begrippen']) {
        $b .= '<section id="begrippen"><h2>' . aitl_h($ui['begrippen']) . '</h2><dl class="begrippen">';
        foreach ($m['begrippen'] as $t) {
            $b .= '<dt id="' . aitl_h($t['anker']) . '">' . aitl_h($t['titel']) . ($t['ook'] ? ' <span class="ook">(' . aitl_h($ui['ook']) . ': ' . aitl_h($t['ook']) . ')</span>' : '') . '</dt><dd>'
                . aitl_render_item($t, [], $ui, $lang) . '</dd>';
        }
        $b .= '</dl></section>';
    }
    if ($m['bronnen']) {
        $b .= '<section id="bronnen"><h2>' . aitl_h($ui['bronnen']) . '</h2><ul class="bronnen">';
        foreach ($m['bronnen'] as $s) {
            $title = aitl_h($s['titel']);
            if ($s['url'] && preg_match('~^https?://~i', $s['url'])) $title = '<a href="' . aitl_h($s['url']) . '" rel="noopener nofollow">' . $title . '</a>';
            $b .= '<li>' . $title . ($s['duiding'] ? ' — ' . aitl_h($s['duiding']) : '') . '</li>';
        }
        $b .= '</ul></section>';
    }

    // Over deze uitleg
    $r = $m['leesbaarheid'] ?? ['score' => null, 'label' => '', 'woordenPerZin' => null];
    $b .= '<section id="over" class="over"><h2>' . aitl_h($ui['over']) . '</h2><p>' . aitl_h($ui['methode']) . '</p>'
        . '<h3>' . aitl_h($ui['principes']) . '</h3><ol class="principes">';
    foreach ($m['principes'] ?? [] as $p) $b .= '<li class="' . ($p['ok'] ? 'ok' : 'nok') . '">' . ($p['ok'] ? '✔ ' : '✗ ') . aitl_h($p['titel']) . '</li>';
    $b .= '</ol>';
    if ($r['score'] !== null) {
        $b .= '<p><strong>' . aitl_h($ui['leesbaarheid']) . ':</strong> ' . aitl_h($r['label']) . ' (Flesch-Douma ' . aitl_h(aitl_num((float)$r['score'], $lang, 0)) . ', '
            . aitl_h(aitl_num((float)$r['woordenPerZin'], $lang, 1)) . ' ' . aitl_h($ui['wpz']) . ').</p>';
    }
    $pub = $opts['publicatie'] ?? null;
    if ($pub) {
        $b .= '<p class="pub"><strong>' . aitl_h($ui['gepubliceerd']) . '</strong> ' . aitl_h($pub['org'] ?? '') . ' ' . aitl_h($ui['op']) . ' ' . aitl_h($pub['datum'] ?? '')
            . (!empty($pub['id']) ? ' · <code>' . aitl_h($pub['id']) . '</code>' : '')
            . (!empty($pub['ondertekend']) ? ' · ✔ ' . aitl_h($ui['ondertekend']) : '')
            . (!empty($pub['bronUrl']) ? ' · <a href="' . aitl_h($pub['bronUrl']) . '">' . aitl_h($ui['bekijkBron']) . '</a>' : '') . '</p>';
    }
    $b .= '<p class="hash">' . aitl_h($ui['bronhash']) . ' (SHA-256): <code>' . aitl_h($m['bronHash'] ?? '') . '</code></p></section></main>'
        . '<footer class="voet">' . aitl_h($ui['gemaakt']) . ' ' . aitl_h($m['aitl']) . ' · <a href="https://github.com/learningtour/aitl" rel="noopener">AI Transparent Language</a></footer>';

    return '<!doctype html><html lang="' . $lang . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="color-scheme" content="light"><meta name="generator" content="AITL ' . aitl_h($m['aitl']) . '">'
        . '<title>' . aitl_h($m['titel']) . ' — ' . aitl_h($lang === 'en' ? 'explanation' : 'uitleg') . '</title><style>' . (is_string($opts['lettertypeCss'] ?? null) ? str_replace('<', '', $opts['lettertypeCss']) : '') . aitl_css() . '</style></head><body>'
        . $b . '</body></html>';
}

function aitl_css(): string {
    return <<<CSS
:root{--blauw:#003399;--blauw50:#f2f5fc;--blauw100:#dfe6f6;--inkt:#101828;--inkt2:#344054;--zacht:#5b677d;--licht:#8a94a6;--papier:#f8f9fb;--kaart:#fff;--lijn:#e4e7ec;--lijn2:#d0d5dd;--vlak:#f1f3f6;--ok:#067647;--okbg:#ecfdf3;--oklijn:#abefc6;--fout:#b42318;--foutbg:#fef3f2;--foutlijn:#fecdca;--sans:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;--mono:ui-monospace,"SF Mono",Menlo,Consolas,monospace}
*{box-sizing:border-box}html{background:#fff;-webkit-text-size-adjust:100%}body{margin:0;font:16px/1.65 var(--sans);color:var(--inkt2);background:#fff;-webkit-font-smoothing:antialiased}
h1,h2,h3{color:var(--inkt);font-weight:600;letter-spacing:-.015em;text-wrap:balance}strong,b{font-weight:600;color:var(--inkt)}
.kop{background:var(--papier);border-bottom:1px solid var(--lijn);padding:2.4rem max(1.25rem,calc(50% - 23rem)) 2rem}
.kop h1{font-size:2rem;line-height:1.2;letter-spacing:-.03em;margin:.9rem 0 .5rem}
.badge{display:inline-flex;align-items:center;gap:.45rem;margin:0;background:#fff;border:1px solid var(--lijn);border-radius:999px;color:var(--inkt2);font-size:.78rem;font-weight:600;padding:.2rem .7rem .2rem .55rem}
.badge::before{content:"";width:.45rem;height:.45rem;border-radius:50%;background:var(--blauw)}
.meta{margin:0;color:var(--zacht);font-size:.95rem}.lagen{margin:.6rem 0 0;color:var(--licht);font-size:.88rem}
main{max-width:46rem;margin:0 auto;padding:.6rem 1.25rem 2rem}section{margin:2.6rem 0}
h2{font-size:1.35rem;line-height:1.3;margin:0 0 .9rem;padding-top:1.6rem;border-top:1px solid var(--lijn)}
section:first-child{margin-top:1.8rem}section:first-child h2{border-top:0;padding-top:0}
h3{font-size:1.05rem;line-height:1.35;margin:1.3rem 0 .35rem}p{margin:.4rem 0 .75rem}
.lead p{font-size:1.1rem;color:var(--inkt)}
.stappen{list-style:none;padding:0;margin:0;display:grid;gap:.75rem}.stap,.factor,.regel,.casus,.beperking,.uitkomst{background:var(--kaart);border:1px solid var(--lijn);border-radius:10px;padding:1.1rem 1.25rem;box-shadow:0 1px 2px rgba(16,24,40,.05)}
.stap h3,.factor h3,.regel h3,.casus h3,.beperking h3,.uitkomst h3{margin-top:0}
.nr{display:inline-grid;place-items:center;width:1.6rem;height:1.6rem;border-radius:50%;background:var(--blauw);color:#fff;font-size:.8rem;font-weight:600;margin-right:.45rem;vertical-align:1px}
.vergelijking{background:var(--blauw50);border:1px solid var(--blauw100);border-radius:8px;padding:.7rem .95rem;margin:.85rem 0}.vergelijking p{margin:.2rem 0}.grens{color:var(--inkt2);font-size:.95rem}
details.tech{margin:.75rem 0 0;border-top:1px solid var(--vlak);padding-top:.6rem}details.tech summary{cursor:pointer;color:var(--blauw);font-weight:600;font-size:.9rem}
.techbody{font-size:.92rem;color:var(--inkt2);padding:.4rem 0 0}.techbody pre,code{font-family:var(--mono);font-size:.84rem}pre{background:var(--papier);border:1px solid var(--lijn);border-radius:8px;padding:.7rem .85rem;overflow-x:auto;white-space:pre-wrap}
.zekerheid{background:var(--papier);border:1px solid var(--lijn);border-radius:8px;padding:.7rem .95rem;margin:.85rem 0}.zlabel{margin:0 0 .3rem}.dots{display:inline-flex;gap:3px;vertical-align:middle;margin:0 .4rem}
.dot{width:.7rem;height:.7rem;border-radius:50%;border:1.5px solid var(--blauw);background:#fff}.dot.on{background:var(--blauw)}.zwoord{color:var(--zacht);font-size:.9rem}
.factoren{display:grid;gap:.75rem}.bar{background:var(--vlak);border-radius:999px;height:.5rem;overflow:hidden;margin:.35rem 0}.bar i{display:block;height:100%;background:var(--blauw);border-radius:999px}
.gewicht{color:var(--zacht);font-weight:600;font-size:.88rem}.richting{font-size:.9rem;color:var(--zacht);margin:.2rem 0}
.uitzonderingen{padding-left:1.2rem}.uitzonderingen li{margin:.3rem 0}.want{color:var(--zacht)}
table.gegevens{border-collapse:collapse;width:100%;margin:.6rem 0;font-size:.95rem}table.gegevens caption{text-align:left;font-weight:600;font-size:.85rem;color:var(--zacht);padding-bottom:.35rem}
table.gegevens th,table.gegevens td{text-align:left;padding:.5rem .1rem;border-bottom:1px solid var(--vlak)}table.gegevens th{font-weight:400;color:var(--zacht);width:50%}table.gegevens td{color:var(--inkt)}
.uitkomstregel{border-left:3px solid var(--blauw);padding:.15rem 0 .15rem .9rem;margin:1rem 0}.uitkomstregel .waarde{margin:0;font-size:1.02rem}.lbl{color:var(--zacht)}
.route{margin:.3rem 0}.controles{padding-left:1.1rem;font-size:.93rem}.controles li.ja{color:var(--ok)}.controles li.nee{color:var(--zacht)}.hier{color:var(--zacht)}
.verwacht{font-size:.85rem;font-weight:600;border-radius:999px;padding:.2rem .7rem;display:inline-block;border:1px solid}.verwacht.ok{background:var(--okbg);color:var(--ok);border-color:var(--oklijn)}.verwacht.nok{background:var(--foutbg);color:var(--fout);border-color:var(--foutlijn)}
dl.gegevens dt,dl.begrippen dt{font-weight:600;color:var(--inkt);margin-top:1rem}.type,.ook{font-weight:400;color:var(--licht);font-size:.86rem}dl dd{margin:.2rem 0 0 0}
a{color:var(--blauw);text-underline-offset:2px}a.term{color:inherit;text-decoration:underline dotted var(--blauw);text-underline-offset:3px}.freq{color:var(--zacht);font-size:.92em}
.over{background:var(--papier);border:1px solid var(--lijn);border-radius:10px;padding:1.2rem 1.4rem}.over h2{border-top:0;padding-top:0}.principes{padding-left:1.2rem}.principes li.ok{color:var(--ok)}.principes li.nok{color:var(--fout)}
.hash code{word-break:break-all;font-size:.75rem}.hash,.pub{font-size:.86rem;color:var(--zacht)}.let{color:var(--fout)}
.voet{text-align:center;color:var(--licht);font-size:.8rem;padding:1rem 1rem 2.4rem}.voet a{color:var(--zacht)}
@media (max-width:600px){body{font-size:15.5px}.kop{padding:1.8rem 1.1rem 1.5rem}.kop h1{font-size:1.6rem}.stap,.factor,.regel,.casus,.beperking,.uitkomst{padding:.9rem 1rem}}
@media print{.kop{background:#fff;padding:1rem 0}.stap,.factor,.regel,.casus,.beperking,.uitkomst{break-inside:avoid;box-shadow:none}a{color:inherit}}
CSS;
}
