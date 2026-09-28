<?php
// AITL — AI Transparent Language, versie 1.0
//
// Een open taal om algoritmes en AI-systemen uit te leggen aan iedereen.
// Elke uitleg heeft twee lagen: de techniek, en de duiding in gewone taal.
// Een AITL-bestand is tegelijk leesbare tekst én iets wat een computer kan
// controleren en uitvoeren: regels worden doorgerekend, zodat bij elke uitkomst
// de route zichtbaar is.
//
// De vijf principes (de AITL-methode):
//   1. Geen techniek zonder duiding.
//   2. Geen vergelijking zonder grens.
//   3. Geen vakterm zonder uitleg.
//   4. Geen uitkomst zonder route.
//   5. Geen uitleg zonder beperkingen.
//
// Referentie-implementatie in puur PHP (8.1+), zonder afhankelijkheden.
// Licentie: MIT — https://github.com/learningtour/aitl

declare(strict_types=1);

const AITL_VERSION = '1.0';
const AITL_MAX_BYTES = 200000;
const AITL_MAX_LINES = 5000;
const AITL_MAX_DEPTH = 40;

// ---------- Woordenschat ----------

// Bloksoorten: multi = mag vaker voorkomen; arg = wat er achter het sleutelwoord staat.
const AITL_KINDS = [
    'uitleg'    => ['multi' => false, 'arg' => 'tekst', 'fields' => ['organisatie', 'systeem', 'versie', 'doelgroep', 'taal', 'samenvatting', 'contact', 'url'], 'required' => []],
    'doel'      => ['multi' => false, 'arg' => 'geen', 'fields' => ['duiding', 'techniek', 'metafoor'], 'required' => ['duiding']],
    'begrip'    => ['multi' => true, 'arg' => 'tekst', 'fields' => ['duiding', 'techniek', 'metafoor', 'ook'], 'required' => ['duiding']],
    'gegeven'   => ['multi' => true, 'arg' => 'naam', 'fields' => ['type', 'label', 'duiding', 'techniek', 'eenheid', 'bron'], 'required' => ['type', 'duiding']],
    'stap'      => ['multi' => true, 'arg' => 'tekst', 'fields' => ['duiding', 'techniek', 'metafoor', 'waarde', 'zekerheid'], 'required' => ['duiding']],
    'factor'    => ['multi' => true, 'arg' => 'tekst', 'fields' => ['gewicht', 'richting', 'duiding', 'techniek', 'metafoor'], 'required' => ['gewicht', 'duiding']],
    'regel'     => ['multi' => true, 'arg' => 'regel', 'fields' => ['label', 'duiding', 'techniek'], 'required' => ['duiding']],
    'casus'     => ['multi' => true, 'arg' => 'tekst', 'fields' => ['duiding'], 'required' => []],
    'uitkomst'  => ['multi' => true, 'arg' => 'optioneel', 'fields' => ['duiding', 'techniek', 'metafoor', 'zekerheid', 'waarde'], 'required' => ['duiding']],
    'beperking' => ['multi' => true, 'arg' => 'tekst', 'fields' => ['duiding', 'techniek', 'metafoor'], 'required' => ['duiding']],
    'toezicht'  => ['multi' => false, 'arg' => 'geen', 'fields' => ['duiding', 'techniek', 'contact'], 'required' => ['duiding']],
    'bron'      => ['multi' => true, 'arg' => 'tekst', 'fields' => ['url', 'duiding'], 'required' => []],
];

// Engelse sleutelwoorden, zodat AITL ook in het Engels geschreven kan worden.
const AITL_ALIASES = [
    'explain' => 'uitleg', 'explanation' => 'uitleg', 'purpose' => 'doel', 'term' => 'begrip',
    'input' => 'gegeven', 'data' => 'gegeven', 'step' => 'stap', 'rule' => 'regel', 'case' => 'casus',
    'outcome' => 'uitkomst', 'limitation' => 'beperking', 'oversight' => 'toezicht', 'source' => 'bron',
    'organisation' => 'organisatie', 'organization' => 'organisatie', 'system' => 'systeem',
    'version' => 'versie', 'audience' => 'doelgroep', 'language' => 'taal', 'summary' => 'samenvatting',
    'plain' => 'duiding', 'technical' => 'techniek', 'metaphor' => 'metafoor', 'limit' => 'grens',
    'also' => 'ook', 'unit' => 'eenheid', 'value' => 'waarde', 'confidence' => 'zekerheid',
    'weight' => 'gewicht', 'direction' => 'richting', 'expect' => 'verwacht',
];

const AITL_TYPES = ['getal' => 'getal', 'number' => 'getal', 'percentage' => 'percentage', 'percent' => 'percentage',
    'tekst' => 'tekst', 'text' => 'tekst', 'janee' => 'janee', 'boolean' => 'janee', 'yesno' => 'janee',
    'datum' => 'datum', 'date' => 'datum'];

// Vaktermen die in een duiding uitleg nodig hebben (principe 3). Bewust geen
// woorden die in gewone taal gangbaar zijn, zoals 'algoritme' of 'model'.
const AITL_JARGON = [
    'neuraal netwerk', 'neurale netwerken', 'parameter', 'parameters', 'token', 'tokens', 'embedding', 'embeddings',
    'vector', 'vectoren', 'dataset', 'datasets', 'finetuning', 'fine-tuning', 'inferentie', 'classificatie', 'classifier',
    'regressie', 'clustering', 'precisie', 'recall', 'f1-score', 'drempelwaarde', 'threshold', 'bias', 'overfitting',
    'hallucinatie', 'hallucinaties', 'prompt', 'prompts', 'llm', 'taalmodel', 'transformer', 'diffusie', 'diffusiemodel',
    'latente ruimte', 'encoder', 'decoder', 'attention', 'contextvenster', 'rag', 'retrieval', 'vectordatabase',
    'hash', 'hashfunctie', 'cryptografisch', 'cryptografische', 'metadata', 'api', 'json', 'machine learning',
    'deep learning', 'feature', 'features', 'softmax', 'logit', 'seed', 'sampling', 'gradiënt', 'backpropagation',
    'epoch', 'epochs', 'cosinusgelijkenis', 'heuristiek', 'regex', 'ocr', 'nlp', 'gewichten', 'trainingsdata',
    'gradient', 'hyperparameter', 'hyperparameters', 'confidence score', 'false positive', 'false negative',
    'vals positief', 'vals negatief', 'model weights', 'jumbf', 'cbor', 'x.509', 'c2pa', 'manifest',
    'perplexiteit', 'perplexity', 'neural network', 'neural networks', 'hallucination', 'hallucinations',
    'training data', 'large language model', 'language model', 'latent space', 'diffusion model', 'classification',
];

// Afkortingen die geen zinseinde zijn (leesbaarheid).
const AITL_ABBR = ['bijv', 'bv', 'o.a', 'd.w.z', 'enz', 'i.p.v', 'm.b.v', 'nl', 'e.g', 'i.e', 'etc', 'vs', 'ca', 'resp', 'ong'];

// ---------- Diagnostiek ----------

function aitl_diag(array &$d, string $level, string $code, int $line, string $message, string $hint = ''): void {
    $d[] = ['niveau' => $level, 'code' => $code, 'regel' => $line, 'melding' => $message, 'tip' => $hint];
}

function aitl_suggest(string $word, array $options): string {
    $best = ''; $bestD = 99;
    foreach ($options as $o) {
        $dist = levenshtein(mb_strtolower($word), $o);
        if ($dist < $bestD) { $bestD = $dist; $best = $o; }
    }
    return $bestD <= 3 ? $best : '';
}

// ---------- Regels inlezen ----------

/**
 * Leest de bron als regels met inspringing. Geeft een platte lijst met per regel
 * de ouder (op basis van inspringing), plus de regelnummers van lege regels.
 */
function aitl_lines(string $src, array &$diag): array {
    $src = preg_replace('~^\xEF\xBB\xBF~', '', $src) ?? '';
    $src = str_replace(["\r\n", "\r"], "\n", $src);
    $raw = explode("\n", $src);
    $lines = []; $blank = []; $stack = []; $tabWarned = false;
    foreach ($raw as $i => $text) {
        $n = $i + 1;
        if (str_contains($text, "\t")) {
            if (!$tabWarned) aitl_diag($diag, 'waarschuwing', 'W107', $n, 'Tabs gevonden; AITL rekent een tab als twee spaties.', 'Gebruik liever spaties voor inspringing.');
            $tabWarned = true;
            $text = str_replace("\t", '  ', $text);
        }
        $trim = trim($text);
        if ($trim === '') { $blank[$n] = true; continue; }
        if ($trim[0] === '#') continue;
        $ind = strlen($text) - strlen(ltrim($text, ' '));
        while ($stack && $lines[end($stack)]['ind'] >= $ind) array_pop($stack);
        $parent = $stack ? end($stack) : -1;
        $lines[] = ['n' => $n, 'ind' => $ind, 'txt' => rtrim($trim), 'parent' => $parent, 'kids' => []];
        $idx = count($lines) - 1;
        if ($parent >= 0) $lines[$parent]['kids'][] = $idx;
        $stack[] = $idx;
    }
    return ['lines' => $lines, 'blank' => $blank];
}

/** Alle tekst onder een regel (vervolgregels), met lege regels als alinea-overgang. */
function aitl_continuation(array $L, array $blank, array $kidIdx, int $afterLine): array {
    $parts = []; $prev = $afterLine;
    $collect = function (array $idxs) use (&$collect, $L, $blank, &$parts, &$prev) {
        foreach ($idxs as $k) {
            $n = $L[$k]['n'];
            $para = false;
            for ($b = $prev + 1; $b < $n; $b++) if (isset($blank[$b])) { $para = true; break; }
            $parts[] = ($para && $parts ? "\n\n" : ($parts ? ' ' : '')) . $L[$k]['txt'];
            $prev = $n;
            $collect($L[$k]['kids']);
        }
    };
    $collect($kidIdx);
    return ['text' => implode('', $parts), 'last' => $prev];
}

function aitl_canon(string $word): string {
    $w = mb_strtolower($word);
    return AITL_ALIASES[$w] ?? $w;
}

/** Splitst 'sleutel: waarde'. Geeft null als de regel geen veld is. */
function aitl_split_field(string $txt): ?array {
    if (!preg_match('~^([\p{L}_][\p{L}\p{N}_ -]{0,60}?)\s*:\s?(.*)$~u', $txt, $m)) return null;
    return [trim($m[1]), $m[2]];
}

function aitl_unquote(string $s): string {
    $s = trim($s);
    if (strlen($s) >= 2 && $s[0] === '"' && substr($s, -1) === '"') return substr($s, 1, -1);
    if (preg_match('~^“(.*)”$~u', $s, $m)) return $m[1];
    return $s;
}

// ---------- Blokken ----------

/** Interpreteert de regelboom tot een document. */
function aitl_parse(string $src, array &$diag): array {
    $doc = ['uitleg' => null, 'doel' => null, 'toezicht' => null, 'begrip' => [], 'gegeven' => [], 'stap' => [],
        'factor' => [], 'regel' => [], 'casus' => [], 'uitkomst' => [], 'beperking' => [], 'bron' => []];
    if (strlen($src) > AITL_MAX_BYTES) {
        aitl_diag($diag, 'fout', 'E005', 1, 'Het bestand is te groot (maximaal ' . intdiv(AITL_MAX_BYTES, 1000) . ' kB).');
        return $doc;
    }
    // Stuurtekens en ongeldige UTF-8 horen niet in een uitleg: melden en verwijderen.
    if (!mb_check_encoding($src, 'UTF-8')) {
        aitl_diag($diag, 'fout', 'E006', 1, 'Het bestand is geen geldige UTF-8-tekst.', 'Sla het bestand op als UTF-8.');
        $src = mb_scrub($src, 'UTF-8');
    }
    if (preg_match_all('~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]~', $src, $cm, PREG_OFFSET_CAPTURE)) {
        $reported = [];
        foreach ($cm[0] as [$ch, $off]) {
            $ln = substr_count($src, "\n", 0, $off) + 1;
            if (!isset($reported[$ln])) aitl_diag($diag, 'fout', 'E006', $ln, 'Onzichtbaar stuurteken (code ' . ord($ch) . ') gevonden.', 'Verwijder het teken; het wordt genegeerd.');
            $reported[$ln] = true;
        }
        $src = preg_replace('~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]~', '', $src) ?? '';
    }
    ['lines' => $L, 'blank' => $blank] = aitl_lines($src, $diag);
    if (count($L) > AITL_MAX_LINES) {
        aitl_diag($diag, 'fout', 'E005', 1, 'Het bestand heeft te veel regels (maximaal ' . AITL_MAX_LINES . ').');
        return $doc;
    }
    $first = true;
    foreach ($L as $idx => $line) {
        if ($line['parent'] !== -1) continue;
        if ($line['ind'] > 0) {
            aitl_diag($diag, 'fout', 'E004', $line['n'], 'Deze regel springt in, maar hoort bij geen blok.', 'Begin een blok aan het begin van de regel, bijvoorbeeld: stap "Tekst lezen"');
            continue;
        }
        $block = aitl_parse_block($L, $blank, $idx, $diag);
        if (!$block) continue;
        $kind = $block['kind'];
        if ($first && $kind !== 'uitleg') {
            aitl_diag($diag, 'fout', 'E040', $line['n'], 'Een AITL-bestand begint met een uitleg-blok.', 'Zet bovenaan bijvoorbeeld: uitleg "Naam van het systeem"');
        }
        $first = false;
        if (AITL_KINDS[$kind]['multi']) {
            $doc[$kind][] = $block;
        } elseif ($doc[$kind] !== null) {
            aitl_diag($diag, 'fout', 'E003', $line['n'], "Het blok '$kind' mag maar één keer voorkomen (eerder op regel {$doc[$kind]['line']}).");
        } else {
            $doc[$kind] = $block;
        }
    }
    if (!$doc['uitleg'] && !array_filter($diag, fn($d) => $d['code'] === 'E040')) {
        aitl_diag($diag, 'fout', 'E040', 1, 'Er ontbreekt een uitleg-blok met de titel.', 'Zet bovenaan: uitleg "Naam van het systeem"');
    }
    return $doc;
}

function aitl_parse_block(array $L, array $blank, int $idx, array &$diag): ?array {
    $line = $L[$idx];
    $txt = $line['txt'];
    if (!preg_match('~^([\p{L}]+)\s*(.*)$~u', $txt, $m)) {
        aitl_diag($diag, 'fout', 'E001', $line['n'], "Onbekende regel: '" . mb_substr($txt, 0, 40) . "'.", 'Een blok begint met een sleutelwoord zoals uitleg, doel, stap, begrip of regel.');
        return null;
    }
    $kind = aitl_canon($m[1]);
    $rest = trim($m[2]);
    if (!isset(AITL_KINDS[$kind])) {
        $sug = aitl_suggest($m[1], array_keys(AITL_KINDS));
        aitl_diag($diag, 'fout', 'E001', $line['n'], "Onbekend bloktype '{$m[1]}'.", $sug ? "Bedoel je '$sug'?" : 'Kies uit: ' . implode(', ', array_keys(AITL_KINDS)) . '.');
        return null;
    }
    $spec = AITL_KINDS[$kind];
    $block = ['kind' => $kind, 'line' => $line['n'], 'arg' => null, 'f' => []];

    // Argument achter het sleutelwoord.
    if ($spec['arg'] === 'regel') {
        if (!preg_match('~^([A-Za-z_][A-Za-z0-9_]*)\s*:\s*(.+)$~', $rest, $r)) {
            aitl_diag($diag, 'fout', 'E020', $line['n'], 'Een regel heeft de vorm: regel naam: standaardwaarde', 'Bijvoorbeeld: regel signaal: nee');
            return null;
        }
        $block['arg'] = $r[1];
        $block['default'] = ['src' => trim($r[2]), 'line' => $line['n']];
        $block['tenzij'] = [];
    } else {
        $rest = preg_replace('~:\s*$~', '', $rest) ?? $rest;
        $arg = aitl_unquote($rest);
        if ($spec['arg'] === 'geen' && $arg !== '') {
            aitl_diag($diag, 'waarschuwing', 'W115', $line['n'], "Achter '$kind' hoort geen naam; '$arg' wordt genegeerd.");
        } elseif (in_array($spec['arg'], ['tekst', 'naam'], true) && $arg === '') {
            aitl_diag($diag, 'fout', 'E012', $line['n'], "Het blok '$kind' heeft een naam nodig.", $kind === 'gegeven' ? 'Bijvoorbeeld: gegeven aanwezigheid' : "Bijvoorbeeld: $kind \"Naam\"");
            return null;
        } elseif ($spec['arg'] === 'naam' && !preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $arg)) {
            aitl_diag($diag, 'fout', 'E012', $line['n'], "De naam van een gegeven mag alleen letters, cijfers en _ bevatten: '$arg'.", 'Gebruik bijvoorbeeld: gegeven gemiddeld_cijfer (en een label voor de leesbare naam).');
            return null;
        } elseif ($spec['arg'] !== 'geen') {
            $block['arg'] = $arg !== '' ? $arg : null;
        }
    }
    if ($kind === 'casus') { $block['values'] = []; $block['verwacht'] = []; }

    foreach ($line['kids'] as $k) {
        $kl = $L[$k];
        $t = $kl['txt'];
        // regel: tenzij-regels
        if ($kind === 'regel' && preg_match('~^(tenzij|unless)\s+(.+)$~iu', $t, $tm)) {
            $body = $tm[2];
            foreach ($kl['kids'] as $kk) $body .= ' ' . $L[$kk]['txt'];
            $want = null;
            if (preg_match('~^(.*?),\s*(want|because)\s+(.+)$~iu', $body, $wm)) { $body = $wm[1]; $want = trim($wm[3]); }
            if (!preg_match('~^(.+?)\s+(dan|then)\s+(.+)$~iu', $body, $dm)) {
                aitl_diag($diag, 'fout', 'E020', $kl['n'], 'Een uitzondering heeft de vorm: tenzij voorwaarde dan waarde', 'Bijvoorbeeld: tenzij aanwezigheid < 80% dan ja, want de aanwezigheid is laag');
                continue;
            }
            $block['tenzij'][] = ['cond' => trim($dm[1]), 'then' => trim($dm[3]), 'want' => $want, 'line' => $kl['n']];
            continue;
        }
        // casus: verwachtingen
        if ($kind === 'casus' && preg_match('~^(verwacht|expect)\s+([A-Za-z_][A-Za-z0-9_]*)\s*:\s*(.+)$~iu', $t, $vm)) {
            $block['verwacht'][$vm[2]] = ['src' => trim($vm[3]), 'line' => $kl['n']];
            continue;
        }
        $fl = aitl_split_field($t);
        if (!$fl) {
            aitl_diag($diag, 'fout', 'E002', $kl['n'], "Verwacht een veld (naam: waarde) in blok '$kind'.", 'Bijvoorbeeld: duiding: Uitleg in gewone taal.');
            continue;
        }
        [$key, $val] = $fl;
        $ckey = aitl_canon($key);
        // casus: waarden voor gegevens
        if ($kind === 'casus' && $ckey !== 'duiding') {
            if (!preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $key)) {
                aitl_diag($diag, 'fout', 'E030', $kl['n'], "In een casus staat 'gegeven: waarde'; '$key' is geen geldige naam.");
                continue;
            }
            $cont = aitl_continuation($L, $blank, $kl['kids'], $kl['n']);
            $block['values'][$key] = ['src' => trim($val . ($cont['text'] !== '' ? ' ' . $cont['text'] : '')), 'line' => $kl['n']];
            continue;
        }
        if (!in_array($ckey, $spec['fields'], true)) {
            $sug = aitl_suggest($key, $spec['fields']);
            aitl_diag($diag, 'fout', 'E002', $kl['n'], "Onbekend veld '$key' in blok '$kind'.", $sug ? "Bedoel je '$sug'?" : 'Toegestaan: ' . implode(', ', $spec['fields']) . '.');
            continue;
        }
        if (isset($block['f'][$ckey])) {
            aitl_diag($diag, 'fout', 'E003', $kl['n'], "Het veld '$ckey' staat twee keer in dit blok (eerder op regel {$block['f'][$ckey]['line']}).");
            continue;
        }
        if ($ckey === 'metafoor') {
            $grens = null; $rest = [];
            foreach ($kl['kids'] as $kk) {
                $sub = aitl_split_field($L[$kk]['txt']);
                if ($sub && aitl_canon($sub[0]) === 'grens' && $grens === null) {
                    $gc = aitl_continuation($L, $blank, $L[$kk]['kids'], $L[$kk]['n']);
                    $grens = ['v' => trim($sub[1] . ($gc['text'] !== '' ? ' ' . $gc['text'] : '')), 'line' => $L[$kk]['n']];
                } else {
                    $rest[] = $kk;
                }
            }
            $cont = aitl_continuation($L, $blank, $rest, $kl['n']);
            $block['f']['metafoor'] = ['v' => trim($val . ($cont['text'] !== '' ? ' ' . $cont['text'] : '')), 'line' => $kl['n'], 'grens' => $grens];
            continue;
        }
        $cont = aitl_continuation($L, $blank, $kl['kids'], $kl['n']);
        $v = $val;
        if ($cont['text'] !== '') $v = ($v !== '' ? $v . (str_starts_with($cont['text'], "\n") ? '' : ' ') : '') . ltrim($cont['text'], "\n");
        $block['f'][$ckey] = ['v' => trim($v), 'line' => $kl['n']];
    }

    foreach ($spec['required'] as $req) {
        if (!isset($block['f'][$req]) || $block['f'][$req]['v'] === '') {
            aitl_diag($diag, 'fout', $req === 'duiding' ? 'E010' : 'E012', $line['n'],
                $req === 'duiding'
                    ? "Het blok '$kind" . ($block['arg'] ? " {$block['arg']}" : '') . "' heeft geen duiding in gewone taal."
                    : "Het blok '$kind" . ($block['arg'] ? " {$block['arg']}" : '') . "' mist het veld '$req'.",
                $req === 'duiding' ? 'Voeg toe: duiding: wat dit betekent, uitgelegd voor iemand zonder technische kennis.' : '');
        }
    }
    return $block;
}

// ---------- Expressies ----------
// Waarden: ['t' => getal|percentage|tekst|janee|datum, 'v' => float|string|bool]

function aitl_tokenize(string $s): array {
    $toks = []; $i = 0; $len = strlen($s);
    while ($i < $len) {
        $c = $s[$i];
        if (ctype_space($c)) { $i++; continue; }
        if ($c === '"' || str_starts_with(substr($s, $i), '“')) {
            $close = $c === '"' ? '"' : '”';
            $start = $i + ($c === '"' ? 1 : 3);
            $end = strpos($s, $close, $start);
            if ($end === false) throw new RuntimeException('tekst zonder afsluitend aanhalingsteken');
            $toks[] = ['str', substr($s, $start, $end - $start)];
            $i = $end + strlen($close);
            continue;
        }
        if (preg_match('~\G(\d{4}-\d{2}-\d{2})~', $s, $m, 0, $i)) { $toks[] = ['date', $m[1]]; $i += strlen($m[1]); continue; }
        if (preg_match('~\G(\d+(?:[.,]\d+)?)(\s?%)?~', $s, $m, 0, $i)) {
            aitl_check_number($m[1]);
            $num = (float)str_replace(',', '.', $m[1]);
            $toks[] = !empty($m[2]) ? ['pct', $num / 100] : ['num', $num];
            $i += strlen($m[0]);
            continue;
        }
        if (preg_match('~\G(<=|>=|!=|<|>|=|\+|-|\*|/|\(|\))~', $s, $m, 0, $i)) { $toks[] = ['op', $m[1]]; $i += strlen($m[1]); continue; }
        if (preg_match('~\G([A-Za-z_][A-Za-z0-9_]*)~', $s, $m, 0, $i)) {
            $w = strtolower($m[1]);
            $map = ['en' => 'and', 'and' => 'and', 'of' => 'or', 'or' => 'or', 'niet' => 'not', 'not' => 'not', 'is' => 'is'];
            if (isset($map[$w])) $toks[] = ['kw', $map[$w]];
            elseif (in_array($w, ['ja', 'yes', 'waar', 'true'], true)) $toks[] = ['bool', true];
            elseif (in_array($w, ['nee', 'no', 'onwaar', 'false'], true)) $toks[] = ['bool', false];
            else $toks[] = ['id', $m[1]];
            $i += strlen($m[1]);
            continue;
        }
        throw new RuntimeException("onverwacht teken '" . mb_substr(substr($s, $i), 0, 1) . "'");
    }
    return $toks;
}

/** Parser met voorrang: of < en < niet < vergelijking < +,- < *,/ < unair. */
function aitl_parse_expr(string $src): array {
    $toks = aitl_tokenize($src);
    $p = 0;
    $peek = function () use (&$toks, &$p) { return $toks[$p] ?? null; };
    $eat = function () use (&$toks, &$p) { return $toks[$p++] ?? null; };
    $depth = 0;
    $or = null; $and = null; $not = null; $cmp = null; $add = null; $mul = null; $un = null; $prim = null;
    $or = function () use (&$and, $peek, $eat) {
        $l = $and();
        while (($t = $peek()) && $t[0] === 'kw' && $t[1] === 'or') { $eat(); $l = ['or', $l, $and()]; }
        return $l;
    };
    $and = function () use (&$not, $peek, $eat) {
        $l = $not();
        while (($t = $peek()) && $t[0] === 'kw' && $t[1] === 'and') { $eat(); $l = ['and', $l, $not()]; }
        return $l;
    };
    $not = function () use (&$not, &$cmp, $peek, $eat, &$depth) {
        if (($t = $peek()) && $t[0] === 'kw' && $t[1] === 'not') {
            $eat();
            if (++$depth > AITL_MAX_DEPTH) throw new RuntimeException('expressie te diep genest');
            $e = ['not', $not()]; $depth--;
            return $e;
        }
        return $cmp();
    };
    $cmp = function () use (&$add, $peek, $eat, &$toks, &$p) {
        $l = $add();
        $t = $peek();
        if (!$t) return $l;
        $op = null;
        if ($t[0] === 'op' && in_array($t[1], ['<', '<=', '>', '>=', '=', '!='], true)) { $eat(); $op = $t[1]; }
        elseif ($t[0] === 'kw' && $t[1] === 'is') {
            $eat();
            if (($n = $peek()) && $n[0] === 'kw' && $n[1] === 'not') { $eat(); $op = '!='; } else { $op = '='; }
        }
        if ($op === null) return $l;
        return ['cmp', $op, $l, $add()];
    };
    $add = function () use (&$mul, $peek, $eat) {
        $l = $mul();
        while (($t = $peek()) && $t[0] === 'op' && ($t[1] === '+' || $t[1] === '-')) { $eat(); $l = ['arith', $t[1], $l, $mul()]; }
        return $l;
    };
    $mul = function () use (&$un, $peek, $eat) {
        $l = $un();
        while (($t = $peek()) && $t[0] === 'op' && ($t[1] === '*' || $t[1] === '/')) { $eat(); $l = ['arith', $t[1], $l, $un()]; }
        return $l;
    };
    $un = function () use (&$un, &$prim, $peek, $eat, &$depth) {
        if (($t = $peek()) && $t[0] === 'op' && $t[1] === '-') {
            $eat();
            if (++$depth > AITL_MAX_DEPTH) throw new RuntimeException('expressie te diep genest');
            $e = ['neg', $un()]; $depth--;
            return $e;
        }
        return $prim();
    };
    $prim = function () use (&$or, $peek, $eat, &$depth) {
        $t = $eat();
        if (!$t) throw new RuntimeException('expressie is onvolledig');
        switch ($t[0]) {
            case 'num': return ['lit', ['t' => 'getal', 'v' => $t[1]]];
            case 'pct': return ['lit', ['t' => 'percentage', 'v' => $t[1]]];
            case 'str': return ['lit', ['t' => 'tekst', 'v' => $t[1]]];
            case 'date': return ['lit', ['t' => 'datum', 'v' => $t[1]]];
            case 'bool': return ['lit', ['t' => 'janee', 'v' => $t[1]]];
            case 'id': return ['ref', $t[1]];
            case 'op':
                if ($t[1] === '(') {
                    if (++$depth > AITL_MAX_DEPTH) throw new RuntimeException('expressie te diep genest');
                    $e = $or();
                    $c = $eat();
                    if (!$c || $c[0] !== 'op' || $c[1] !== ')') throw new RuntimeException('haakje sluiten ontbreekt');
                    $depth--;
                    return $e;
                }
        }
        throw new RuntimeException("onverwacht '" . (is_bool($t[1]) ? ($t[1] ? 'ja' : 'nee') : (string)$t[1]) . "'");
    };
    $e = $or();
    if ($p < count($toks)) {
        $t = $toks[$p];
        throw new RuntimeException("onverwacht '" . (is_bool($t[1]) ? ($t[1] ? 'ja' : 'nee') : (string)$t[1]) . "' na de expressie");
    }
    return $e;
}

function aitl_refs(array $e): array {
    return match ($e[0]) {
        'ref' => [$e[1]],
        'lit' => [],
        'not', 'neg' => aitl_refs($e[1]),
        'and', 'or' => array_merge(aitl_refs($e[1]), aitl_refs($e[2])),
        'cmp', 'arith' => array_merge(aitl_refs($e[2]), aitl_refs($e[3])),
        default => [],
    };
}

function aitl_is_numeric_type(string $t): bool { return $t === 'getal' || $t === 'percentage'; }

/** Bepaalt het type van een expressie; gooit een foutmelding bij een typefout. */
function aitl_type_of(array $e, callable $typeOfRef): string {
    switch ($e[0]) {
        case 'lit': return $e[1]['t'];
        case 'ref': return $typeOfRef($e[1]);
        case 'not':
            if (aitl_type_of($e[1], $typeOfRef) !== 'janee') throw new RuntimeException("'niet' werkt alleen op ja/nee");
            return 'janee';
        case 'and': case 'or':
            foreach ([$e[1], $e[2]] as $s) if (aitl_type_of($s, $typeOfRef) !== 'janee') throw new RuntimeException("'" . ($e[0] === 'and' ? 'en' : 'of') . "' verbindt alleen ja/nee-voorwaarden");
            return 'janee';
        case 'neg':
            $t = aitl_type_of($e[1], $typeOfRef);
            if (!aitl_is_numeric_type($t)) throw new RuntimeException('een min-teken werkt alleen op getallen');
            return $t;
        case 'arith':
            $a = aitl_type_of($e[2], $typeOfRef); $b = aitl_type_of($e[3], $typeOfRef);
            if (!aitl_is_numeric_type($a) || !aitl_is_numeric_type($b)) throw new RuntimeException("rekenen ('{$e[1]}') kan alleen met getallen en percentages");
            if ($a === 'percentage' && $b === 'percentage' && ($e[1] === '+' || $e[1] === '-')) return 'percentage';
            return 'getal';
        case 'cmp':
            $a = aitl_type_of($e[2], $typeOfRef); $b = aitl_type_of($e[3], $typeOfRef);
            if ($a !== $b) throw new RuntimeException("je vergelijkt een $a met een $b");
            if (($a === 'tekst' || $a === 'janee') && !in_array($e[1], ['=', '!='], true)) throw new RuntimeException("een $a kun je alleen op gelijk of ongelijk vergelijken");
            return 'janee';
    }
    throw new RuntimeException('onbekende expressie');
}

function aitl_eval(array $e, callable $valueOfRef): array {
    switch ($e[0]) {
        case 'lit': return $e[1];
        case 'ref': return $valueOfRef($e[1]);
        case 'not': return ['t' => 'janee', 'v' => !aitl_eval($e[1], $valueOfRef)['v']];
        case 'and':
            $a = aitl_eval($e[1], $valueOfRef);
            return $a['v'] ? ['t' => 'janee', 'v' => (bool)aitl_eval($e[2], $valueOfRef)['v']] : ['t' => 'janee', 'v' => false];
        case 'or':
            $a = aitl_eval($e[1], $valueOfRef);
            return $a['v'] ? ['t' => 'janee', 'v' => true] : ['t' => 'janee', 'v' => (bool)aitl_eval($e[2], $valueOfRef)['v']];
        case 'neg':
            $a = aitl_eval($e[1], $valueOfRef);
            return ['t' => $a['t'], 'v' => -$a['v']];
        case 'arith':
            $a = aitl_eval($e[2], $valueOfRef); $b = aitl_eval($e[3], $valueOfRef);
            $t = ($a['t'] === 'percentage' && $b['t'] === 'percentage' && ($e[1] === '+' || $e[1] === '-')) ? 'percentage' : 'getal';
            if ($e[1] === '/' && abs((float)$b['v']) < 1e-12) throw new RuntimeException('delen door nul');
            $v = match ($e[1]) { '+' => $a['v'] + $b['v'], '-' => $a['v'] - $b['v'], '*' => $a['v'] * $b['v'], '/' => $a['v'] / $b['v'] };
            return ['t' => $t, 'v' => round((float)$v, 10)];
        case 'cmp':
            $a = aitl_eval($e[2], $valueOfRef); $b = aitl_eval($e[3], $valueOfRef);
            $x = $a['v']; $y = $b['v'];
            if (is_float($x) || is_int($x)) { $x = round((float)$x, 9); $y = round((float)$y, 9); }
            $r = match ($e[1]) { '<' => $x < $y, '<=' => $x <= $y, '>' => $x > $y, '>=' => $x >= $y, '=' => $x == $y, '!=' => $x != $y };
            return ['t' => 'janee', 'v' => $r];
    }
    throw new RuntimeException('onbekende expressie');
}

/** Leest een waarde uit een casus volgens het type van het gegeven. */
/**
 * Weigert getallen als 1.450: in het Nederlands is dat duizendvierhonderdvijftig,
 * in het Engels één komma vier vijf. AITL raadt niet, maar vraagt om duidelijkheid.
 */
function aitl_check_number(string $digits): void {
    if (preg_match('~^[1-9]\d{0,2}\.\d{3}$~', $digits)) {
        $dec = rtrim(rtrim(str_replace('.', ',', $digits), '0'), ',');
        throw new RuntimeException("dubbelzinnig getal '$digits': schrijf " . str_replace('.', '', $digits)
            . ' (duizendtallen zonder punt)' . (str_contains($dec, ',') ? " of $dec (komma voor decimalen)" : ''));
    }
}

function aitl_parse_value(string $src, string $type): array {
    $s = trim($src);
    switch ($type) {
        case 'janee':
            $w = mb_strtolower($s);
            if (in_array($w, ['ja', 'yes', 'waar', 'true'], true)) return ['t' => 'janee', 'v' => true];
            if (in_array($w, ['nee', 'no', 'onwaar', 'false'], true)) return ['t' => 'janee', 'v' => false];
            throw new RuntimeException("verwacht ja of nee, niet '$s'");
        case 'percentage':
            if (!preg_match('~^(-?\d+(?:[.,]\d+)?)\s?%$~', $s, $m)) throw new RuntimeException("verwacht een percentage zoals 72%, niet '$s'");
            aitl_check_number(ltrim($m[1], '-'));
            return ['t' => 'percentage', 'v' => (float)str_replace(',', '.', $m[1]) / 100];
        case 'getal':
            if (!preg_match('~^-?\d+(?:[.,]\d+)?$~', $s)) throw new RuntimeException("verwacht een getal zoals 6,8, niet '$s'");
            aitl_check_number(ltrim($s, '-'));
            return ['t' => 'getal', 'v' => (float)str_replace(',', '.', $s)];
        case 'datum':
            if (!preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $s, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) throw new RuntimeException("verwacht een datum als JJJJ-MM-DD, niet '$s'");
            return ['t' => 'datum', 'v' => $s];
        case 'tekst':
            return ['t' => 'tekst', 'v' => aitl_unquote($s)];
    }
    throw new RuntimeException('onbekend type');
}

// ---------- Getallen en woorden ----------

function aitl_num(float $v, string $lang = 'nl', int $maxDec = 2): string {
    $s = number_format($v, $maxDec, $lang === 'en' ? '.' : ',', $lang === 'en' ? ',' : '.');
    if (str_contains($s, $lang === 'en' ? '.' : ',')) $s = rtrim(rtrim($s, '0'), $lang === 'en' ? '.' : ',');
    return $s === '-0' ? '0' : $s;
}

function aitl_format(array $val, string $lang = 'nl'): string {
    return match ($val['t']) {
        'janee' => $val['v'] ? ($lang === 'en' ? 'yes' : 'ja') : ($lang === 'en' ? 'no' : 'nee'),
        'percentage' => aitl_num($val['v'] * 100, $lang) . '%',
        'getal' => aitl_num((float)$val['v'], $lang),
        'tekst' => '“' . $val['v'] . '”',
        'datum' => $val['v'],
        default => (string)$val['v'],
    };
}

/** Waarde met eenheid, behalve als het label die eenheid al noemt ("aantal woorden"). */
function aitl_format_unit(array $val, ?string $unit, string $label, string $lang = 'nl'): string {
    $s = aitl_format($val, $lang);
    if ($unit && $val['t'] === 'getal' && !aitl_find_term($label, mb_strtolower($unit))) $s .= " $unit";
    return $s;
}

/** Kans als natuurlijke frequentie: "ongeveer 7 op de 10". */
function aitl_frequency(float $p, string $lang = 'nl'): string {
    $en = $lang === 'en';
    if ($p <= 0) return $en ? 'never' : 'nooit';
    if ($p >= 1) return $en ? 'always' : 'altijd';
    $about = $en ? 'about' : 'ongeveer';
    $in = $en ? 'in' : 'op de';
    $fmt = fn(int $n) => number_format($n, 0, ',', $en ? ',' : '.');
    if ($p >= 0.095 && $p <= 0.905) return "$about " . (int)round($p * 10) . " $in 10";
    if ($p > 0.905 && $p <= 0.995) return "$about " . (int)round($p * 100) . " $in 100";
    if ($p > 0.995) return "$about " . $fmt((int)round($p * 1000)) . " $in " . $fmt(1000);
    if ($p >= 0.0095) return "$about " . (int)round($p * 100) . " $in 100";
    if ($p >= 0.00095) return "$about " . (int)round($p * 1000) . " $in " . $fmt(1000);
    return $en ? 'less than 1 in 1,000' : 'minder dan 1 op de 1.000';
}

/** Kans in woorden. */
function aitl_likelihood(float $p, string $lang = 'nl'): string {
    $w = $lang === 'en'
        ? [[0.99, 'almost certain'], [0.9, 'very likely'], [0.7, 'likely'], [0.55, 'more likely than not'], [0.45, 'as likely as not'], [0.3, 'rather unlikely'], [0.1, 'unlikely'], [0, 'very unlikely']]
        : [[0.99, 'vrijwel zeker'], [0.9, 'zeer waarschijnlijk'], [0.7, 'waarschijnlijk'], [0.55, 'eerder wel dan niet'], [0.45, 'even waarschijnlijk wel als niet'], [0.3, 'eerder niet'], [0.1, 'onwaarschijnlijk'], [0, 'zeer onwaarschijnlijk']];
    foreach ($w as [$min, $label]) if ($p >= $min) return $label;
    return end($w)[1];
}

/** Zekerheid van een systeem, in mensentaal. */
function aitl_confidence_text(float $p, string $lang = 'nl'): string {
    $pct = aitl_num($p * 100, $lang) . '%';
    if ($lang === 'en') {
        return "The system rates this $pct: " . aitl_likelihood($p, 'en') . ". Out of 10 similar cases it would be right about "
            . (int)round($p * 10) . ' times, and wrong about ' . (10 - (int)round($p * 10)) . ' times. This is the system\'s own estimate.';
    }
    return "Het systeem schat dit op $pct: " . aitl_likelihood($p) . '. Van 10 vergelijkbare gevallen zou het er ongeveer '
        . (int)round($p * 10) . ' goed hebben, en ' . (10 - (int)round($p * 10)) . ' niet. Dit is de inschatting van het systeem zelf.';
}

// ---------- Leesbaarheid ----------

function aitl_syllables(string $word): int {
    $w = mb_strtolower($word);
    $w = str_replace(['ij', 'y'], ['ï', 'i'], $w);
    $n = preg_match_all('~[aeiouáéíóúàèëïöüâêîôû]+~u', $w);
    return max(1, (int)$n);
}

function aitl_sentences(string $text): array {
    $t = preg_replace('~\s+~u', ' ', trim($text)) ?? '';
    if ($t === '') return [];
    foreach (AITL_ABBR as $a) $t = preg_replace('~(?<![\p{L}])' . preg_quote($a, '~') . '\.~iu', str_replace('.', '§', $a) . '§', $t) ?? $t;
    $parts = preg_split('~(?<=[.!?:;])\s+(?=[\p{Lu}\p{N}“"])~u', $t) ?: [$t];
    return array_values(array_filter(array_map(fn($s) => str_replace('§', '.', trim($s)), $parts), fn($s) => $s !== ''));
}

function aitl_words(string $sentence): array {
    preg_match_all('~[\p{L}][\p{L}\'’-]*~u', $sentence, $m);
    return $m[0];
}

/** Flesch-Douma (Nederlandse Flesch): hoger = makkelijker. */
function aitl_readability(string $text): array {
    $sent = aitl_sentences($text);
    $words = 0; $syl = 0; $long = [];
    foreach ($sent as $s) {
        $w = aitl_words($s);
        $words += count($w);
        foreach ($w as $x) $syl += aitl_syllables($x);
        if (count($w) > 25) $long[] = $s;
    }
    if ($words === 0 || !$sent) return ['score' => null, 'zinnen' => 0, 'woorden' => 0, 'lang' => []];
    $score = 206.835 - 0.93 * ($words / count($sent)) - 77 * ($syl / $words);
    return ['score' => round(max(0, min(100, $score)), 1), 'zinnen' => count($sent), 'woorden' => $words,
        'woordenPerZin' => round($words / count($sent), 1), 'lang' => $long];
}

function aitl_readability_label(?float $score, string $lang = 'nl'): string {
    if ($score === null) return $lang === 'en' ? 'not measured' : 'niet gemeten';
    $l = $lang === 'en'
        ? [[70, 'easy'], [60, 'fairly easy'], [50, 'average'], [30, 'difficult'], [0, 'very difficult']]
        : [[70, 'makkelijk'], [60, 'redelijk makkelijk'], [50, 'gemiddeld'], [30, 'moeilijk'], [0, 'zeer moeilijk']];
    foreach ($l as [$min, $label]) if ($score >= $min) return $label;
    return end($l)[1];
}

// ---------- Vaktermen ----------

/** Alle schrijfwijzen van een begrip (met meervoud en synoniemen). */
function aitl_term_forms(string $term, ?string $also): array {
    $forms = [];
    foreach (array_merge([$term], $also ? preg_split('~\s*,\s*~u', $also) : []) as $t) {
        $t = mb_strtolower(trim($t));
        if ($t === '') continue;
        foreach (['', 's', 'en', "'s"] as $suf) $forms[] = $t . $suf;
    }
    return array_values(array_unique($forms));
}

function aitl_find_term(string $text, string $form): bool {
    return (bool)preg_match('~(?<![\p{L}\p{N}])' . preg_quote($form, '~') . '(?![\p{L}\p{N}])~iu', $text);
}

// ---------- Controle en compilatie ----------

/**
 * De volledige pijplijn: inlezen, controleren, regels doorrekenen en een
 * rapportmodel maken. Deterministisch: dezelfde bron geeft hetzelfde resultaat.
 */
function aitl_check(string $src): array {
    $diag = [];
    $doc = aitl_parse($src, $diag);
    $lang = 'nl';
    if ($doc['uitleg'] && isset($doc['uitleg']['f']['taal'])) {
        $lv = mb_strtolower($doc['uitleg']['f']['taal']['v']);
        if (in_array($lv, ['nl', 'en'], true)) $lang = $lv;
        else aitl_diag($diag, 'fout', 'E041', $doc['uitleg']['f']['taal']['line'], "Taal '$lv' wordt niet ondersteund; kies nl of en.");
    }

    $blocks = aitl_all_blocks($doc);

    // Principes 1 en 2: techniek met duiding, metafoor met grens.
    foreach ($blocks as $b) {
        $f = $b['f'];
        if (isset($f['techniek']) && (!isset($f['duiding']) || $f['duiding']['v'] === '') && $b['kind'] !== 'uitleg') {
            // E010 is al gemeld als duiding verplicht was; anders hier.
            if (!in_array('duiding', AITL_KINDS[$b['kind']]['required'], true)) {
                aitl_diag($diag, 'fout', 'E010', $f['techniek']['line'], 'Techniek zonder duiding: leg ook in gewone taal uit wat dit betekent.', 'Voeg in hetzelfde blok toe: duiding: ...');
            }
        }
        if (isset($f['metafoor'])) {
            $g = $f['metafoor']['grens'];
            if (!$g || $g['v'] === '') {
                aitl_diag($diag, 'fout', 'E011', $f['metafoor']['line'], 'Vergelijking zonder grens: zeg ook waar de vergelijking ophoudt.', 'Voeg onder de metafoor (ingesprongen) toe: grens: waar de vergelijking niet klopt.');
            }
        }
        if (isset($f['duiding'], $f['techniek'])) {
            $a = mb_strtolower(preg_replace('~\W+~u', ' ', $f['duiding']['v']) ?? '');
            $c = mb_strtolower(preg_replace('~\W+~u', ' ', $f['techniek']['v']) ?? '');
            similar_text($a, $c, $pct);
            if ($a !== '' && ($a === $c || $pct > 85)) {
                aitl_diag($diag, 'waarschuwing', 'W108', $f['duiding']['line'], 'De duiding lijkt bijna letterlijk op de techniek.', 'Vertaal de techniek naar gewone taal, bijvoorbeeld met een voorbeeld of vergelijking.');
            }
        }
    }

    // Kopgegevens.
    $u = $doc['uitleg'];
    if ($u) {
        if (($u['arg'] ?? '') === '') aitl_diag($diag, 'fout', 'E012', $u['line'], 'De uitleg heeft een titel nodig.', 'Bijvoorbeeld: uitleg "Beeldgenerator voor lesmateriaal"');
        if (isset($u['f']['versie']) && !preg_match('~^\d{4}-\d{2}-\d{2}$~', $u['f']['versie']['v'])) {
            aitl_diag($diag, 'fout', 'E041', $u['f']['versie']['line'], 'Een versie is een datum in de vorm JJJJ-MM-DD.', 'Bijvoorbeeld: versie: 2026-09-01');
        }
        if (isset($u['f']['url']) && !preg_match('~^https?://~i', $u['f']['url']['v'])) {
            aitl_diag($diag, 'fout', 'E041', $u['f']['url']['line'], 'Een url begint met http:// of https://.');
        }
    }
    foreach ($doc['bron'] as $b) {
        if (isset($b['f']['url']) && !preg_match('~^https?://\S+$~i', $b['f']['url']['v'])) {
            aitl_diag($diag, 'fout', 'E041', $b['f']['url']['line'], 'Een url begint met http:// of https:// en bevat geen spaties.');
        }
    }

    // Zekerheid, gewicht, richting.
    $pctOf = function (string $v): ?float {
        return preg_match('~^(\d+(?:[.,]\d+)?)\s?%$~', trim($v), $m) ? (float)str_replace(',', '.', $m[1]) / 100 : null;
    };
    foreach ($blocks as $b) {
        if (isset($b['f']['zekerheid'])) {
            $p = $pctOf($b['f']['zekerheid']['v']);
            if ($p === null || $p > 1) aitl_diag($diag, 'fout', 'E041', $b['f']['zekerheid']['line'], 'Zekerheid is een percentage tussen 0% en 100%.', 'Bijvoorbeeld: zekerheid: 87%');
        }
    }
    $weightSum = 0.0;
    foreach ($doc['factor'] as $b) {
        $g = $b['f']['gewicht']['v'] ?? '';
        $p = $pctOf($g);
        if ($p === null && !in_array(mb_strtolower($g), ['groot', 'middel', 'klein', 'large', 'medium', 'small'], true) && $g !== '') {
            aitl_diag($diag, 'fout', 'E041', $b['f']['gewicht']['line'], 'Een gewicht is een percentage of groot, middel of klein.', 'Bijvoorbeeld: gewicht: 40%');
        }
        if ($p !== null) $weightSum += $p;
        if (isset($b['f']['richting']) && !in_array(mb_strtolower($b['f']['richting']['v']), ['verhoogt', 'verlaagt', 'beide', 'increases', 'decreases', 'both'], true)) {
            aitl_diag($diag, 'fout', 'E041', $b['f']['richting']['line'], 'Richting is verhoogt, verlaagt of beide.');
        }
    }
    if ($weightSum > 1.0001) aitl_diag($diag, 'waarschuwing', 'W114', $doc['factor'][0]['line'], 'De gewichten van de factoren tellen op tot meer dan 100% (' . aitl_num($weightSum * 100) . '%).');

    // Namen: begrippen, gegevens en regels uniek.
    $seen = [];
    foreach (['begrip', 'gegeven', 'regel'] as $kind) {
        foreach ($doc[$kind] as $b) {
            $key = $kind === 'begrip' ? 'b:' . mb_strtolower((string)$b['arg']) : 'n:' . $b['arg'];
            if (isset($seen[$key])) aitl_diag($diag, 'fout', 'E003', $b['line'], "'{$b['arg']}' is al gedefinieerd op regel {$seen[$key]}.");
            else $seen[$key] = $b['line'];
        }
    }

    // Gegevens en regels.
    $gegevens = [];
    foreach ($doc['gegeven'] as $g) {
        $tv = mb_strtolower($g['f']['type']['v'] ?? '');
        if ($tv !== '' && !isset(AITL_TYPES[$tv])) {
            aitl_diag($diag, 'fout', 'E041', $g['f']['type']['line'], "Onbekend type '$tv'.", 'Kies uit: getal, percentage, tekst, janee, datum.');
            continue;
        }
        if ($tv === '') continue;
        $gegevens[$g['arg']] = ['type' => AITL_TYPES[$tv], 'label' => $g['f']['label']['v'] ?? str_replace('_', ' ', $g['arg']),
            'eenheid' => $g['f']['eenheid']['v'] ?? null, 'block' => $g];
    }
    $rules = aitl_compile_rules($doc['regel'], $gegevens, $diag);

    // Casussen doorrekenen.
    $cases = [];
    foreach ($doc['casus'] as $c) $cases[] = aitl_run_case($c, $gegevens, $rules, $lang, $diag);

    // Principe 3: vaktermen in gewone-taalteksten.
    $defined = [];
    foreach ($doc['begrip'] as $b) foreach (aitl_term_forms((string)$b['arg'], $b['f']['ook']['v'] ?? null) as $form) $defined[$form] = true;
    $plainTexts = aitl_plain_texts($doc);
    $jargonHits = [];
    foreach ($plainTexts as [$text, $line]) {
        foreach (AITL_JARGON as $term) {
            if (isset($defined[$term])) continue;
            if (aitl_find_term($text, $term) && !isset($jargonHits[$term . '@' . $line])) {
                $jargonHits[$term . '@' . $line] = true;
                aitl_diag($diag, 'waarschuwing', 'W101', $line, "Vakterm '$term' in gewone-taaltekst zonder uitleg.", "Leg hem uit met een blok: begrip $term (met een duiding), of kies een gewoner woord.");
            }
        }
    }

    // Leesbaarheid.
    // Elke tekst telt als eigen zin(nen): zonder slotteken zou 'want …' aan de volgende tekst plakken.
    $all = implode("\n", array_map(fn($x) => preg_match('~[.!?:;]\s*$~u', $x[0]) ? $x[0] : $x[0] . '.', $plainTexts));
    $read = aitl_readability($all);
    foreach ($plainTexts as [$text, $line]) {
        foreach (aitl_readability($text)['lang'] as $s) {
            aitl_diag($diag, 'waarschuwing', 'W102', $line, 'Lange zin (' . count(aitl_words($s)) . ' woorden): "' . mb_substr($s, 0, 60) . '…"', 'Maak er twee of drie korte zinnen van; richtlijn is hooguit 15 à 20 woorden.');
        }
    }
    if ($read['score'] !== null && $read['score'] < 45 && $read['woorden'] >= 30) {
        aitl_diag($diag, 'waarschuwing', 'W103', 1, 'De gewone-taalteksten zijn moeilijk leesbaar (score ' . aitl_num($read['score'], 'nl', 0) . ').', 'Gebruik kortere zinnen en kortere woorden.');
    }

    // Principes 4 en 5 en de structuur.
    if ($doc['regel'] && !$doc['casus']) aitl_diag($diag, 'waarschuwing', 'W106', $doc['regel'][0]['line'], 'Er zijn regels, maar geen casus die laat zien hoe ze uitpakken.', 'Voeg een blok casus "Voorbeeld" toe met waarden voor de gegevens.');
    if (!$doc['stap'] && !$doc['regel'] && !$doc['factor']) aitl_diag($diag, 'waarschuwing', 'W112', 1, 'De uitleg zegt niet hoe het systeem tot een uitkomst komt.', 'Voeg stappen, factoren of regels toe.');
    if (!$doc['beperking']) aitl_diag($diag, 'waarschuwing', 'W104', 1, 'Er staan geen beperkingen in: wat kan het systeem niet, of waar gaat het mis?', 'Voeg ten minste één blok beperking "..." toe.');
    if ($doc['uitleg'] && !$doc['doel']) aitl_diag($diag, 'waarschuwing', 'W110', 1, 'Er ontbreekt een doel: waarvoor wordt het systeem ingezet?');
    if ($doc['uitleg'] && !$doc['toezicht']) aitl_diag($diag, 'waarschuwing', 'W111', 1, 'Er staat niet in wie toezicht houdt of kan ingrijpen.');

    usort($diag, fn($a, $b) => [$a['regel'], $a['code']] <=> [$b['regel'], $b['code']]);
    $errors = array_values(array_filter($diag, fn($d) => $d['niveau'] === 'fout'));
    $warnings = array_values(array_filter($diag, fn($d) => $d['niveau'] === 'waarschuwing'));
    $codes = array_column($diag, 'code');
    $has = fn(array $list) => (bool)array_intersect($codes, $list);
    $principles = [
        ['nr' => 1, 'titel' => $lang === 'en' ? 'No technology without plain language' : 'Geen techniek zonder duiding', 'ok' => !$has(['E010', 'W108'])],
        ['nr' => 2, 'titel' => $lang === 'en' ? 'No metaphor without its limit' : 'Geen vergelijking zonder grens', 'ok' => !$has(['E011'])],
        ['nr' => 3, 'titel' => $lang === 'en' ? 'No jargon without explanation' : 'Geen vakterm zonder uitleg', 'ok' => !$has(['W101'])],
        ['nr' => 4, 'titel' => $lang === 'en' ? 'No outcome without its route' : 'Geen uitkomst zonder route', 'ok' => !$has(['W106', 'W112', 'E030', 'E031', 'E032'])],
        ['nr' => 5, 'titel' => $lang === 'en' ? 'No explanation without limitations' : 'Geen uitleg zonder beperkingen', 'ok' => !$has(['W104'])],
    ];

    $model = $doc['uitleg'] ? aitl_model($doc, $gegevens, $rules, $cases, $lang) : null;
    if ($model) {
        $model['principes'] = $principles;
        $model['leesbaarheid'] = ['score' => $read['score'], 'label' => aitl_readability_label($read['score'], $lang),
            'woordenPerZin' => $read['woordenPerZin'] ?? null, 'woorden' => $read['woorden']];
        $model['bronHash'] = hash('sha256', $src);
    }
    return [
        'ok' => !$errors,
        'aitl' => AITL_VERSION,
        'taal' => $lang,
        'fouten' => $errors,
        'waarschuwingen' => $warnings,
        'principes' => $principles,
        'leesbaarheid' => $read + ['label' => aitl_readability_label($read['score'], $lang)],
        'model' => $model,
    ];
}

function aitl_all_blocks(array $doc): array {
    $out = [];
    foreach ($doc as $k => $v) {
        if ($v === null) continue;
        if (isset($v['kind'])) $out[] = $v; else foreach ($v as $b) $out[] = $b;
    }
    return $out;
}

/** Alle teksten die voor iedereen leesbaar moeten zijn, met hun regelnummer. */
function aitl_plain_texts(array $doc): array {
    $out = [];
    foreach (aitl_all_blocks($doc) as $b) {
        foreach (['duiding', 'samenvatting'] as $k) if (isset($b['f'][$k]) && $b['f'][$k]['v'] !== '') $out[] = [$b['f'][$k]['v'], $b['f'][$k]['line']];
        if (isset($b['f']['metafoor'])) {
            $out[] = [$b['f']['metafoor']['v'], $b['f']['metafoor']['line']];
            if ($b['f']['metafoor']['grens']) $out[] = [$b['f']['metafoor']['grens']['v'], $b['f']['metafoor']['grens']['line']];
        }
        if ($b['kind'] === 'regel') foreach ($b['tenzij'] as $t) if ($t['want']) $out[] = [$t['want'], $t['line']];
    }
    // Een begrip mag zijn eigen vakterm natuurlijk noemen; de uitleg staat erbij.
    return $out;
}

/** Parseert en typeert alle regels; geeft per regel de AST's en het type. */
function aitl_compile_rules(array $blocks, array $gegevens, array &$diag): array {
    $rules = [];
    foreach ($blocks as $b) {
        $r = ['name' => $b['arg'], 'label' => $b['f']['label']['v'] ?? str_replace('_', ' ', $b['arg']), 'block' => $b,
            'default' => null, 'tenzij' => [], 'type' => null, 'ok' => true];
        try {
            $r['default'] = aitl_parse_expr($b['default']['src']);
        } catch (Throwable $e) {
            aitl_diag($diag, 'fout', 'E020', $b['default']['line'], "Regel '{$b['arg']}': " . $e->getMessage() . '.');
            $r['ok'] = false;
        }
        foreach ($b['tenzij'] as $t) {
            try {
                $r['tenzij'][] = ['cond' => aitl_parse_expr($t['cond']), 'then' => aitl_parse_expr($t['then']),
                    'want' => $t['want'], 'line' => $t['line'], 'condSrc' => $t['cond'], 'thenSrc' => $t['then']];
            } catch (Throwable $e) {
                aitl_diag($diag, 'fout', 'E020', $t['line'], "Regel '{$b['arg']}': " . $e->getMessage() . '.');
                $r['ok'] = false;
            }
        }
        if (isset($rules[$b['arg']])) continue; // dubbele naam al gemeld
        $rules[$b['arg']] = $r;
    }
    // Onbekende namen, kringverwijzingen en typen.
    $state = [];
    $typeOf = null;
    $typeOf = function (string $name, int $line) use (&$typeOf, &$rules, $gegevens, &$state, &$diag): string {
        if (isset($gegevens[$name])) return $gegevens[$name]['type'];
        if (!isset($rules[$name])) throw new RuntimeException("onbekende naam '$name'");
        $r = &$rules[$name];
        if ($r['type'] !== null) return $r['type'];
        if (($state[$name] ?? '') === 'bezig') throw new RuntimeException("kringverwijzing via '$name'");
        if (!$r['ok'] || !$r['default']) throw new RuntimeException("regel '$name' bevat een fout");
        $state[$name] = 'bezig';
        $ref = fn(string $n) => $typeOf($n, $line);
        try {
            $t = aitl_type_of($r['default'], $ref);
            foreach ($r['tenzij'] as $x) {
                if (aitl_type_of($x['cond'], $ref) !== 'janee') throw new RuntimeException('een voorwaarde na tenzij moet ja of nee opleveren');
                $tt = aitl_type_of($x['then'], $ref);
                if ($tt !== $t) throw new RuntimeException("de uitzondering geeft een $tt, maar de standaardwaarde is een $t");
            }
        } finally {
            $state[$name] = 'klaar';
        }
        $r['type'] = $t;
        return $t;
    };
    foreach ($rules as $name => $r) {
        if (!$r['ok'] || !$r['default']) continue;
        try {
            $typeOf($name, $r['block']['line']);
        } catch (Throwable $e) {
            aitl_diag($diag, 'fout', str_contains($e->getMessage(), 'kringverwijzing') ? 'E023' : (str_contains($e->getMessage(), 'onbekende naam') ? 'E021' : 'E022'),
                $r['block']['line'], "Regel '$name': " . $e->getMessage() . '.',
                str_contains($e->getMessage(), 'onbekende naam') ? 'Definieer het met een blok gegeven of regel.' : '');
            $rules[$name]['ok'] = false;
        }
    }
    return $rules;
}

/** Rekent een casus door en bouwt per regel de route. */
function aitl_run_case(array $c, array $gegevens, array $rules, string $lang, array &$diag): array {
    $vals = [];
    foreach ($c['values'] as $name => $v) {
        if (!isset($gegevens[$name])) {
            aitl_diag($diag, 'fout', 'E030', $v['line'], "Casus '{$c['arg']}': onbekend gegeven '$name'.", 'Definieer het eerst met: gegeven ' . $name);
            continue;
        }
        try {
            $vals[$name] = aitl_parse_value($v['src'], $gegevens[$name]['type']);
        } catch (Throwable $e) {
            aitl_diag($diag, 'fout', 'E030', $v['line'], "Casus '{$c['arg']}', $name: " . $e->getMessage() . '.');
        }
    }
    $needed = [];
    foreach ($rules as $r) {
        if (!$r['ok']) continue;
        foreach (array_merge([$r['default']], array_merge(...array_map(fn($x) => [$x['cond'], $x['then']], $r['tenzij']) ?: [[]])) as $e) {
            if (!$e) continue;
            foreach (aitl_refs($e) as $ref) if (isset($gegevens[$ref])) $needed[$ref] = true;
        }
    }
    $missing = array_diff(array_keys($needed), array_keys($vals));
    if ($missing) {
        aitl_diag($diag, 'fout', 'E030', $c['line'], "Casus '{$c['arg']}' mist een waarde voor: " . implode(', ', $missing) . '.', 'Voeg in de casus toe: ' . reset($missing) . ': waarde');
    }
    $results = []; $trace = [];
    $valueOf = null;
    $valueOf = function (string $name) use (&$valueOf, &$vals, &$results, &$trace, $rules, $gegevens, $lang): array {
        if (isset($vals[$name])) return $vals[$name];
        if (isset($gegevens[$name])) throw new RuntimeException("waarde voor '$name' ontbreekt");
        if (isset($results[$name])) return $results[$name];
        $r = $rules[$name] ?? null;
        if (!$r || !$r['ok']) throw new RuntimeException("regel '$name' is niet uitvoerbaar");
        $value = aitl_eval($r['default'], $valueOf);
        $branch = null; $checks = [];
        foreach ($r['tenzij'] as $k => $x) {
            $hit = (bool)aitl_eval($x['cond'], $valueOf)['v'];
            $checks[] = ['nr' => $k + 1, 'voorwaarde' => aitl_verbalize($x['cond'], $gegevens, $rules, $lang),
                'geldt' => $hit, 'dan' => null, 'want' => $x['want'], 'hier' => aitl_values_used($x['cond'], $valueOf, $gegevens, $rules, $lang)];
            if ($hit) { $value = aitl_eval($x['then'], $valueOf); $branch = $k; }
        }
        foreach ($checks as $k => $ch) $checks[$k]['dan'] = aitl_format(aitl_eval($r['tenzij'][$k]['then'], $valueOf), $lang);
        $results[$name] = $value;
        $trace[$name] = ['waarde' => $value, 'tak' => $branch, 'controles' => $checks];
        return $value;
    };
    $out = [];
    if (!$missing) {
        foreach ($rules as $name => $r) {
            if (!$r['ok']) continue;
            try {
                $valueOf($name);
            } catch (Throwable $e) {
                aitl_diag($diag, 'fout', 'E032', $c['line'], "Casus '{$c['arg']}', regel '$name': " . $e->getMessage() . '.');
            }
        }
        foreach ($c['verwacht'] as $rname => $exp) {
            if (!isset($rules[$rname])) {
                aitl_diag($diag, 'fout', 'E031', $exp['line'], "Casus '{$c['arg']}': verwachting voor onbekende regel '$rname'.");
                continue;
            }
            if (!isset($results[$rname])) continue;
            try {
                $want = aitl_parse_value($exp['src'], $results[$rname]['t']);
            } catch (Throwable $e) {
                aitl_diag($diag, 'fout', 'E031', $exp['line'], "Casus '{$c['arg']}', verwachting $rname: " . $e->getMessage() . '.');
                continue;
            }
            $got = $results[$rname];
            $eq = is_float($got['v']) ? abs($got['v'] - $want['v']) < 1e-9 : $got['v'] === $want['v'];
            if (!$eq) {
                aitl_diag($diag, 'fout', 'E031', $exp['line'], "Casus '{$c['arg']}': verwacht $rname = " . aitl_format($want, $lang) . ', maar de regels geven ' . aitl_format($got, $lang) . '.',
                    'Klopt de verwachting niet, of klopt de regel niet? Een van beide moet worden aangepast.');
            }
            $out['verwacht'][$rname] = ['verwacht' => aitl_format($want, $lang), 'klopt' => $eq];
        }
    }
    return ['block' => $c, 'values' => $vals, 'results' => $results, 'trace' => $trace, 'verwacht' => $out['verwacht'] ?? [], 'compleet' => !$missing];
}

function aitl_values_used(array $cond, callable $valueOf, array $gegevens, array $rules, string $lang): array {
    $out = [];
    foreach (array_unique(aitl_refs($cond)) as $ref) {
        try {
            $v = $valueOf($ref);
        } catch (Throwable $e) {
            continue;
        }
        $label = $gegevens[$ref]['label'] ?? ($rules[$ref]['label'] ?? $ref);
        $out[] = ['label' => $label, 'waarde' => aitl_format_unit($v, $gegevens[$ref]['eenheid'] ?? null, $label, $lang)];
    }
    return $out;
}

/** Zet een voorwaarde om in een leesbare zin. */
function aitl_verbalize(array $e, array $gegevens, array $rules, string $lang, int $depth = 0): string {
    $en = $lang === 'en';
    $sub = fn(array $x) => aitl_verbalize($x, $gegevens, $rules, $lang, $depth + 1);
    switch ($e[0]) {
        case 'lit': return aitl_format($e[1], $lang);
        case 'ref': return $gegevens[$e[1]]['label'] ?? ($rules[$e[1]]['label'] ?? $e[1]);
        case 'not':
            if ($e[1][0] === 'ref') return ($en ? 'not ' : 'niet ') . $sub($e[1]);
            return ($en ? 'it is not the case that ' : 'het is niet zo dat ') . $sub($e[1]);
        case 'and': case 'or':
            $w = $e[0] === 'and' ? ($en ? 'and' : 'en') : ($en ? 'or' : 'of');
            $s = $sub($e[1]) . " $w " . $sub($e[2]);
            return $depth > 0 ? "($s)" : $s;
        case 'neg': return '-' . $sub($e[1]);
        case 'arith':
            $w = $en ? ['+' => 'plus', '-' => 'minus', '*' => 'times', '/' => 'divided by'] : ['+' => 'plus', '-' => 'min', '*' => 'keer', '/' => 'gedeeld door'];
            return $sub($e[2]) . ' ' . $w[$e[1]] . ' ' . $sub($e[3]);
        case 'cmp':
            $w = $en
                ? ['<' => 'is less than', '<=' => 'is at most', '>' => 'is more than', '>=' => 'is at least', '=' => 'is', '!=' => 'is not']
                : ['<' => 'is lager dan', '<=' => 'is hoogstens', '>' => 'is hoger dan', '>=' => 'is minstens', '=' => 'is', '!=' => 'is niet'];
            return $sub($e[2]) . ' ' . $w[$e[1]] . ' ' . $sub($e[3]);
    }
    return '?';
}

// ---------- Rapportmodel ----------

/** Stabiel HTML-anker (zelfde uitkomst op elk systeem, dus geen iconv). */
function aitl_anchor(string $prefix, string $name): string {
    $s = mb_strtolower($name);
    $s = strtr($s, ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ï' => 'i', 'î' => 'i', 'ó' => 'o', 'ö' => 'o', 'ô' => 'o', 'ú' => 'u', 'ü' => 'u', 'û' => 'u', 'ç' => 'c', 'ñ' => 'n']);
    $s = trim(preg_replace('~[^a-z0-9]+~', '-', $s) ?? '', '-');
    return $prefix . '-' . ($s !== '' ? $s : substr(hash('sha256', $name), 0, 8));
}

function aitl_item(?array $b, string $lang): ?array {
    if (!$b) return null;
    $f = $b['f'];
    $item = ['titel' => $b['arg'] ?? null, 'duiding' => $f['duiding']['v'] ?? null, 'techniek' => $f['techniek']['v'] ?? null,
        'regel' => $b['line']];
    if (isset($f['metafoor'])) $item['metafoor'] = ['tekst' => $f['metafoor']['v'], 'grens' => $f['metafoor']['grens']['v'] ?? null];
    if (isset($f['waarde'])) $item['waarde'] = $f['waarde']['v'];
    if (isset($f['zekerheid']) && preg_match('~^(\d+(?:[.,]\d+)?)\s?%$~', trim($f['zekerheid']['v']), $m)) {
        $p = min(1.0, (float)str_replace(',', '.', $m[1]) / 100);
        $item['zekerheid'] = ['p' => $p, 'tekst' => aitl_num($p * 100, $lang) . '%', 'woorden' => aitl_likelihood($p, $lang),
            'frequentie' => aitl_frequency($p, $lang), 'uitleg' => aitl_confidence_text($p, $lang)];
    }
    return $item;
}

function aitl_model(array $doc, array $gegevens, array $rules, array $cases, string $lang): array {
    $u = $doc['uitleg']['f'];
    $v = fn(string $k) => $u[$k]['v'] ?? null;
    $m = ['aitl' => AITL_VERSION, 'taal' => $lang, 'titel' => $doc['uitleg']['arg'] ?? '',
        'organisatie' => $v('organisatie'), 'systeem' => $v('systeem'), 'versie' => $v('versie'),
        'doelgroep' => $v('doelgroep'), 'samenvatting' => $v('samenvatting'), 'contact' => $v('contact'), 'url' => $v('url'),
        'doel' => aitl_item($doc['doel'], $lang), 'toezicht' => aitl_item($doc['toezicht'], $lang)];
    if ($m['toezicht'] && isset($doc['toezicht']['f']['contact'])) $m['toezicht']['contact'] = $doc['toezicht']['f']['contact']['v'];
    foreach (['stap' => 'stappen', 'uitkomst' => 'uitkomsten', 'beperking' => 'beperkingen'] as $k => $key) {
        $m[$key] = array_map(fn($b) => aitl_item($b, $lang), $doc[$k]);
    }
    $m['factoren'] = array_map(function ($b) use ($lang) {
        $it = aitl_item($b, $lang);
        $g = trim($b['f']['gewicht']['v'] ?? '');
        $it['gewicht'] = $g;
        $it['gewichtP'] = preg_match('~^(\d+(?:[.,]\d+)?)\s?%$~', $g, $mm) ? min(1.0, (float)str_replace(',', '.', $mm[1]) / 100)
            : (['groot' => 0.66, 'large' => 0.66, 'middel' => 0.4, 'medium' => 0.4, 'klein' => 0.15, 'small' => 0.15][mb_strtolower($g)] ?? null);
        $it['richting'] = isset($b['f']['richting']) ? mb_strtolower($b['f']['richting']['v']) : null;
        return $it;
    }, $doc['factor']);
    $m['begrippen'] = array_map(fn($b) => aitl_item($b, $lang) + ['ook' => $b['f']['ook']['v'] ?? null,
        'anker' => aitl_anchor('begrip', (string)$b['arg'])], $doc['begrip']);
    usort($m['begrippen'], fn($a, $b) => strcmp(mb_strtolower((string)$a['titel']), mb_strtolower((string)$b['titel'])));
    $m['bronnen'] = array_map(fn($b) => ['titel' => $b['arg'], 'url' => $b['f']['url']['v'] ?? null, 'duiding' => $b['f']['duiding']['v'] ?? null], $doc['bron']);
    $m['gegevens'] = array_values(array_map(fn($g) => ['naam' => $g['block']['arg'], 'label' => $g['label'], 'type' => $g['type'],
        'eenheid' => $g['eenheid'], 'duiding' => $g['block']['f']['duiding']['v'] ?? null, 'techniek' => $g['block']['f']['techniek']['v'] ?? null,
        'bron' => $g['block']['f']['bron']['v'] ?? null], $gegevens));
    $m['regels'] = [];
    foreach ($rules as $r) {
        $b = $r['block'];
        $m['regels'][] = ['naam' => $r['name'], 'label' => $r['label'], 'duiding' => $b['f']['duiding']['v'] ?? null,
            'techniek' => $b['f']['techniek']['v'] ?? null,
            'standaard' => $r['ok'] && $r['default'] ? aitl_verbalize($r['default'], $gegevens, $rules, $lang) : $b['default']['src'],
            'uitzonderingen' => array_map(fn($x) => ['voorwaarde' => aitl_verbalize($x['cond'], $gegevens, $rules, $lang),
                'dan' => aitl_verbalize($x['then'], $gegevens, $rules, $lang), 'want' => $x['want']], $r['tenzij']),
            'bron' => 'regel ' . $r['name'] . ': ' . $b['default']['src'] . implode('', array_map(fn($x) => "\n  tenzij {$x['cond']} dan {$x['then']}" . ($x['want'] ? ", want {$x['want']}" : ''), $b['tenzij']))];
    }
    $m['casussen'] = [];
    foreach ($cases as $c) {
        $mc = ['titel' => $c['block']['arg'], 'duiding' => $c['block']['f']['duiding']['v'] ?? null, 'compleet' => $c['compleet'], 'gegevens' => [], 'uitkomsten' => [], 'verwacht' => []];
        foreach ($c['values'] as $name => $val) {
            $g = $gegevens[$name];
            $freq = $val['t'] === 'percentage' && $val['v'] > 0 && $val['v'] < 1 ? aitl_frequency((float)$val['v'], $lang) : null;
            $mc['gegevens'][] = ['label' => $g['label'], 'waarde' => aitl_format_unit($val, $g['eenheid'], $g['label'], $lang), 'frequentie' => $freq];
        }
        foreach ($rules as $name => $r) {
            if (!isset($c['trace'][$name])) continue;
            $t = $c['trace'][$name];
            $route = aitl_route_text($r, $t, $lang);
            $mc['uitkomsten'][] = ['regel' => $r['label'], 'waarde' => aitl_format($t['waarde'], $lang), 'route' => $route, 'controles' => $t['controles']];
        }
        foreach ($c['verwacht'] as $rname => $e) $mc['verwacht'][] = ['regel' => $rules[$rname]['label'] ?? $rname] + $e;
        $m['casussen'][] = $mc;
    }
    return $m;
}

/** De route van een uitkomst in één of twee zinnen. */
function aitl_route_text(array $rule, array $t, string $lang): string {
    $en = $lang === 'en';
    $val = aitl_format($t['waarde'], $lang);
    if ($t['tak'] === null) {
        if (!$t['controles']) return $en ? "The outcome is $val: this rule always gives this value." : "De uitkomst is $val: deze regel geeft altijd deze waarde.";
        return $en ? "The outcome is $val, the standard value: none of the exceptions apply."
            : "De uitkomst is $val, de standaardwaarde: geen van de uitzonderingen is van toepassing.";
    }
    $c = $t['controles'][$t['tak']];
    $here = $c['hier'] ? ' (' . ($en ? 'here: ' : 'hier: ') . implode(', ', array_map(fn($h) => "{$h['label']} {$h['waarde']}", $c['hier'])) . ')' : '';
    $reason = $c['want'] ?: $c['voorwaarde'];
    return $en ? "The outcome is $val, because $reason$here." : "De uitkomst is $val, want $reason$here.";
}
