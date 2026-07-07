<?php
/**
 * Hepsiburada (tardasegenin) fiyat listesi + satis.tardas.com.tr görselleri ile
 * import edilebilir katalog üretir:
 *   database/data/tardas-raw.json          → {slug, price, images, sku}
 *   database/data/tardas/*.json            → SEO içerikli katalog (mevcut kategorilere)
 *
 * Girdi: geçici hb-products.json (parse_hb.php çıktısı) + storage/app/import/tardas-raw.json (görseller)
 * Çalıştırma: php scripts/build_tardas_catalog.php
 */

$HB = json_decode(file_get_contents('C:/Users/DMCBLG~1/AppData/Local/Temp/claude/hb-products.json'), true)['unique'];
$SITE = json_decode(file_get_contents(__DIR__ . '/../storage/app/import/tardas-raw.json'), true)['products'];

// --- yardımcılar ---
function core_key(string $n): string
{
    $s = mb_strtolower($n, 'UTF-8');
    $s = preg_replace('/\d+[.,]?\d*\s*(kg|kilo|lt|litre|gr|gram|ml|cc|g|l|ad)\b/u', ' ', $s);
    $s = str_replace(['tardaş', 'egenin', 'organik', 'sertifikalı', 'happy life', '-', '(', ')', 'teneke', 'dorika', 'filtresiz'], ' ', $s);
    $s = preg_replace('/[^a-zçğıöşü ]/u', ' ', $s);
    $w = array_filter(preg_split('/\s+/u', trim($s)));
    sort($w);

    return implode(' ', $w);
}

/** Ham HB adını temiz ürün adına çevir: marka önekini at, ağırlığı sona al, düzelt. */
function clean_name(string $raw): string
{
    $s = trim($raw);
    $s = preg_replace('/^tardaş\s+egenin\s*/iu', '', $s);
    $s = preg_replace('/^egenin\s*/iu', '', $s);          // "Egenin Egenin" kalıntısı
    $s = preg_replace('/^[-\s]+/u', '', $s);
    // ağırlık tokenini yakala
    $weight = null;
    if (preg_match('/(\d+[.,]?\d*)\s*(kg|kilo|lt|litre|gr|gram|ml|cc|g|l)\b/iu', $s, $m)) {
        $num = $m[1];
        $unit = mb_strtolower($m[2], 'UTF-8');
        $map = ['kilo' => 'kg', 'litre' => 'lt', 'l' => 'lt', 'gram' => 'gr', 'g' => 'gr', 'cc' => 'ml'];
        $unit = $map[$unit] ?? $unit;
        $weight = $num . ' ' . $unit;
        // ağırlığı metinden çıkar (tüm geçişleri)
        $s = preg_replace('/(\d+[.,]?\d*)\s*(kg|kilo|lt|litre|gr|gram|ml|cc|g|l)\b/iu', ' ', $s);
    }
    $s = preg_replace('/\s+/u', ' ', trim($s));
    $s = preg_replace('/\s*-\s*$/u', '', $s);
    // "Organik" başta değilse ve içinde geçmiyorsa dokunma; ilk harf büyük
    $s = trim($s);
    if ($weight) {
        $s .= ' ' . $weight;
    }

    return $s;
}

function slugify(string $s): string
{
    $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u'];
    $s = strtr(mb_strtolower($s, 'UTF-8'), $tr);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);

    return trim($s, '-');
}

/** Ada göre kategori slug'ı (mevcut kategorilerimiz). */
function category_of(string $name): string
{
    $n = mb_strtolower($name, 'UTF-8');
    $has = fn (...$k) => (bool) array_filter($k, fn ($x) => str_contains($n, $x));

    if ($has('zeytinyağ', 'sızma', 'zeytin yağ')) return 'zeytin-zeytinyagi-yag';
    if ($has('zeytin')) return 'zeytin-zeytinyagi-yag'; // zeytin, zeytin ezmesi
    if ($has('bal ', 'balı', 'petek', 'propolis', 'polen', 'pekmez', 'reçel', 'marmelat', 'tahin')) return 'kahvaltilik-recel';
    if ($has('sirke', 'ekşi', 'salça', 'menemen harc', 'kapari', 'nar ekş')) return 'sos-salca-sirke';
    if ($has('tuz')) return 'baharat-aktar';
    if ($has('konsantre', 'suyu')) return 'icecek-cay';
    if ($has('yulaf ezmes')) return 'kahvaltilik-recel';
    // bakliyat/un/makarna: un, mercimek, nohut, fasulye, bulgur, pirinç, makarna, şehriye, erişte, irmik, ruşeym, buğday, mısır, karabuğday, tarhana, galeta
    return 'bakliyat-makarna';
}

/** Kategoriye göre SEO/metin üret. */
function seo_for(string $name, string $cat, ?float $price): array
{
    $lname = mb_strtolower($name, 'UTF-8');
    $catLabel = [
        'zeytin-zeytinyagi-yag' => 'organik zeytin & zeytinyağı',
        'kahvaltilik-recel' => 'organik kahvaltılık',
        'sos-salca-sirke' => 'organik sos, salça & sirke',
        'baharat-aktar' => 'doğal tuz & baharat',
        'icecek-cay' => 'organik içecek',
        'bakliyat-makarna' => 'organik bakliyat, un & makarna',
    ][$cat] ?? 'organik ürün';

    $short = "{$name} — katkısız, doğal içerikli organik ürün. Tardaş Egenin güvencesiyle taze şekilde kapınıza gelsin.";
    $metaT = mb_substr($name, 0, 55) . ' | Organik & Katkısız';
    $metaD = "{$name} uygun fiyatla Organik Express'te. Sertifikalı, katkısız {$catLabel}; hızlı kargoyla kapınızda.";

    // kısa, kategoriye uygun açıklama
    $desc = "<p><strong>{$name}</strong>, organik tarım anlayışıyla üretilen, katkı maddesi içermeyen doğal bir üründür. Tardaş Egenin markasının kalite ve tazelik güvencesiyle sofralarınıza gelir.</p>"
        . "<ul><li>Organik ve <strong>katkısız</strong> içerik</li><li>Doğal üretim, koruyucu içermez</li><li>Özenli paketleme ile gönderilir</li></ul>"
        . "<p><strong>Not:</strong> Görsel ve içerik üreticiden alınmıştır; ambalaj ve gramaj parti bazında küçük farklılık gösterebilir.</p>";

    $storage = 'Serin, kuru ve doğrudan güneş almayan bir yerde, ağzı kapalı şekilde saklayın.';
    if (str_contains($lname, 'zeytinyağ') || str_contains($lname, 'sızma')) {
        $storage = 'Serin ve ışık almayan bir yerde saklayın; soğukta donma/tortu doğaldır, oda sıcaklığında çözülür.';
    } elseif (str_contains($lname, 'zeytin') || str_contains($lname, 'salça') || str_contains($lname, 'ezmes') || str_contains($lname, 'kapari')) {
        $storage = 'Açtıktan sonra buzdolabında saklayın; ürünün üzeri yağ/salamura ile kapalı kalmalıdır.';
    } elseif (str_contains($lname, 'bal') || str_contains($lname, 'pekmez') || str_contains($lname, 'reçel') || str_contains($lname, 'tahin')) {
        $storage = 'Oda sıcaklığında, serin ve kuru yerde saklayın. Ballarda zamanla kristalleşme doğaldır.';
    }

    return [$metaT, $metaD, $short, $desc, $storage];
}

// --- site görselleri: önce birebir core_key, sonra bulanık (Jaccard) eşleşme ---
$imgExact = [];
$siteWords = []; // [ [words[], image] ]
foreach ($SITE as $p) {
    if (empty($p['images'][0])) continue;
    $ck = core_key($p['name']);
    $imgExact[$ck] = $p['images'][0];
    $siteWords[] = ['words' => array_filter(explode(' ', $ck)), 'img' => $p['images'][0], 'wk' => weight_key($p['name'])];
}
function weight_key(string $n): string
{
    $s = mb_strtolower($n, 'UTF-8');
    if (preg_match('/([\d]+(?:[.,]\d+)?)\s*(kg|kilo)/u', $s, $m)) return (int) round(((float) str_replace(',', '.', str_replace('.', '', $m[1]))) * 1000) . 'g';
    if (preg_match('/([\d]+(?:[.,]\d+)?)\s*(lt|litre|l)\b/u', $s, $m)) return (int) round(((float) str_replace(',', '.', $m[1])) * 1000) . 'ml';
    if (preg_match('/([\d.]+)\s*(gr|g|gram)\b/u', $s, $m)) return (int) str_replace('.', '', $m[1]) . 'g';
    if (preg_match('/([\d]+)\s*(ml|cc)\b/u', $s, $m)) return (int) $m[1] . 'ml';
    return '?';
}
$match_image = function (string $hbName) use ($imgExact, $siteWords) {
    $ck = core_key($hbName);
    if (isset($imgExact[$ck])) return $imgExact[$ck];
    $hw = array_filter(explode(' ', $ck));
    $hwk = weight_key($hbName);
    $best = null; $bestScore = 0;
    foreach ($siteWords as $sw) {
        $inter = count(array_intersect($hw, $sw['words']));
        $uni = count(array_unique(array_merge($hw, $sw['words'])));
        $j = $uni ? $inter / $uni : 0;
        // aynı gramaj bonus
        if ($hwk !== '?' && $hwk === $sw['wk']) { $j += 0.15; }
        if ($j > $bestScore) { $bestScore = $j; $best = $sw['img']; }
    }
    return $bestScore >= 0.6 ? $best : null;
};

// --- üret ---
$raw = ['scraped_at' => date('c'), 'source' => 'hepsiburada tardasegenin + satis.tardas.com.tr images', 'products' => []];
$byCat = [];
$slugSeen = [];
$noImg = 0;

foreach ($HB as $hp) {
    $name = clean_name($hp['name']);
    if ($name === '') continue;
    $slug = slugify($name);
    if (isset($slugSeen[$slug])) continue; // aynı slug tekrar
    $slugSeen[$slug] = true;

    $cat = category_of($name);
    $img = $match_image($hp["name"]);
    if (! $img) { $noImg++; }

    [$metaT, $metaD, $short, $desc, $storage] = seo_for($name, $cat, $hp['price']);

    // birim/gramaj
    $unit = 'adet'; $variant = 'Standart';
    if (preg_match('/(\d+[.,]?\d*\s*(?:kg|gr|lt|ml))$/iu', $name, $wm)) { $variant = trim($wm[1]); }

    $raw['products'][] = [
        'source_url' => $hp['name'],
        'slug' => $slug,
        'name' => $name,
        'sku' => null,
        'price' => $hp['price'],
        'currency' => 'TRY',
        'images' => $img ? [$img] : [],
    ];

    $byCat[$cat][] = [
        'source_slug' => $slug,
        'category' => $cat,
        'name' => $name,
        'slug' => $slug,
        'unit' => $unit, 'unit_amount' => 1, 'variant_name' => $variant, 'is_weight_based' => false, 'tax_rate' => 1,
        'meta_title' => $metaT,
        'short_description' => $short,
        'meta_description' => $metaD,
        'storage_info' => $storage,
        'ingredients' => null,
        'description' => $desc,
    ];
}

// yaz
$dataDir = __DIR__ . '/../database/data';
file_put_contents($dataDir . '/tardas-raw.json', json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

@mkdir($dataDir . '/tardas', 0775, true);
$i = 1;
$catFile = [
    'bakliyat-makarna' => '01-bakliyat-un-makarna',
    'kahvaltilik-recel' => '02-kahvaltilik-bal-recel',
    'sos-salca-sirke' => '03-sirke-salca-sos',
    'zeytin-zeytinyagi-yag' => '04-zeytin-zeytinyagi',
    'baharat-aktar' => '05-tuz',
    'icecek-cay' => '06-konsantre-icecek',
];
foreach ($byCat as $cat => $items) {
    $fn = $catFile[$cat] ?? ('9' . $i);
    file_put_contents("$dataDir/tardas/$fn.json", json_encode(['categories' => [], 'products' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $i++;
}

echo "Toplam ürün: " . count($raw['products']) . "\n";
echo "Görselsiz: $noImg\n";
echo "Kategori dağılımı:\n";
foreach ($byCat as $c => $it) { echo "  $c: " . count($it) . "\n"; }
