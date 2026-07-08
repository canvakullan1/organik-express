<?php

/**
 * Genel katalog kazıyıcı: sitemap → ürün sayfası JSON-LD (Product + BreadcrumbList).
 * Çıktı: database/data/catalog2/<kaynak>.json (import:catalog2 formatı).
 *
 *   php scripts/scrape_generic.php <kaynak>
 *   php scripts/scrape_generic.php egricayir
 *
 * Kaynak metin kopyalanmaz; özgün TR kısa açıklama + meta üretilir.
 * Kategori: sabit ('category') veya 'auto'/'auto_essen' (breadcrumb+isim anahtar-kelime eşlemesi).
 * 'require' verildiyse yalnız isim/breadcrumb eşleşen ürünler alınır (kategori-sınırlı siteler).
 */

$root = dirname(__DIR__);
$outDir = $root . '/database/data/catalog2';
@mkdir($outDir, 0775, true);

$sources = [
    'egricayir' => ['sitemaps' => ['https://www.egricayir.com/sitemap.xml'], 'filter' => '~/tr/urun/~', 'category' => 'kahvaltilik-recel', 'brand' => 'Eğriçayır'],
    'tullianabitlis' => ['sitemaps' => ['https://tullianabitlisbali.com/products.xml'], 'category' => 'auto', 'brand' => 'Tulliana Bitlis'],
    'ekozel' => ['sitemaps' => ['https://www.ekozelorganik.com/sitemap/products/0.xml'], 'category' => 'kuruyemis-kurutulmus', 'require' => '~kuru|kayısı|kayisi|incir|dut|üzüm|uzum|erik|hurma|cranberry|yaban mersini|meyve~iu', 'brand' => 'Ekozel'],
    'gurvita' => ['sitemaps' => ['https://www.gurvita.com.tr/sitemap/products/0.xml'], 'category' => 'sos-salca-sirke', 'require' => '~sirke~iu', 'list_price' => true, 'brand' => 'Gürvita'],
    'beyorganik' => ['sitemaps' => ['https://www.beyorganik.com/sitemap/products/0.xml'], 'category' => 'bakliyat-makarna', 'require' => '~bakliyat|mercimek|nohut|fasulye|bulgur|pirinç|pirinc|bezelye|barbunya|buğday|bugday|kinoa|bakla|şehriye|sehriye|mısır|misir|börülce|borulce~iu', 'brand' => 'Bey Organik'],
    'organikgurme' => ['sitemaps' => ['https://www.organikgurme.com/xml/sitemap_product_1.xml'], 'category' => 'bakliyat-makarna', 'require' => '~bakliyat|mercimek|nohut|fasulye|bulgur|pirinç|pirinc|bezelye|barbunya|buğday|bugday|kinoa|bakla|şehriye|sehriye|mısır|misir|börülce|borulce~iu', 'brand' => 'Organik Gurme'],
    'lutfiye' => ['sitemaps' => ['https://www.lutfiye.com/xml/sitemap_product_1.xml'], 'category' => 'auto', 'brand' => 'Lütfiye'],
    'ogstore' => ['sitemaps' => ['https://www.ogstore.com.tr/sitemap.xml'], 'category' => 'sos-salca-sirke', 'require' => '~sirke~iu', 'brand' => 'OG Natural'],
    'essen' => ['sitemaps' => ['https://www.essenorganik.com/urunler1.xml'], 'category' => 'auto_essen', 'brand' => 'Essen Organik'],
];

$only = $argv[1] ?? '';
if ($only === '' || ! isset($sources[$only])) {
    fwrite(STDERR, "Kullanım: php scripts/scrape_generic.php <" . implode('|', array_keys($sources)) . ">\n");
    exit(1);
}
$cfg = $sources[$only];

// ---- yardımcılar ----
function slugify(string $s): string
{
    $tr = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
    $s = strtr($s, $tr);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);

    return trim($s, '-');
}

function normPrice($v): float
{
    if (is_numeric($v)) {
        return (float) $v;
    }
    $v = (string) $v;
    $v = preg_replace('~[^0-9,.]~', '', $v);
    if (str_contains($v, ',') && str_contains($v, '.')) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    } elseif (str_contains($v, ',')) {
        $v = str_replace(',', '.', $v);
    }

    return (float) $v;
}

function multiGet(array $urls, int $conc = 12): array
{
    $out = [];
    $queue = array_values($urls);
    $i = 0;
    while ($i < count($queue)) {
        $batch = array_slice($queue, $i, $conc);
        $mh = curl_multi_init();
        $hs = [];
        foreach ($batch as $u) {
            $ch = curl_init($u);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 25, CURLOPT_USERAGENT => 'Mozilla/5.0', CURLOPT_SSL_VERIFYPEER => false]);
            curl_multi_add_handle($mh, $ch);
            $hs[$u] = $ch;
        }
        do {
            $st = curl_multi_exec($mh, $running);
            curl_multi_select($mh, 1.0);
        } while ($running > 0 && $st === CURLM_OK);
        foreach ($hs as $u => $ch) {
            $out[$u] = (string) curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        $i += $conc;
        fwrite(STDERR, '.');
    }
    fwrite(STDERR, "\n");

    return $out;
}

function locs(string $xml): array
{
    preg_match_all('~<loc>\s*([^<]+?)\s*</loc>~i', $xml, $m);

    return array_map(fn ($s) => html_entity_decode(trim($s), ENT_QUOTES | ENT_HTML5), $m[1]);
}

/** JSON-LD yoksa meta/JS'ten çıkar (ticimax, ideasoft, vb.) */
function fallbackExtract(string $h): ?array
{
    $name = '';
    if (preg_match('~<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']~i', $h, $m)
        || preg_match('~<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']~i', $h, $m)) {
        $name = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5);
    }
    if ($name === '' && preg_match('~<h1[^>]*>(.*?)</h1>~is', $h, $m)) {
        $name = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5));
    }
    if ($name === '' && preg_match('~<title[^>]*>(.*?)</title>~is', $h, $m)) {
        $name = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));
    }
    if ($name === '') {
        return null;
    }

    $imgs = [];
    if (preg_match('~<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']~i', $h, $m)
        || preg_match('~<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']~i', $h, $m)) {
        if (trim($m[1]) !== '') {
            $imgs[] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5);
        }
    }

    $price = 0;
    $high = 0;
    if (preg_match('~"productPriceStr"\s*:\s*"([^"]+)"~', $h, $m)) {          // ticimax (KDV dahil)
        $price = normPrice($m[1]);
    } elseif (preg_match('~"productPrice"\s*:\s*([0-9.]+)~', $h, $m)) {
        $price = round((float) $m[1], 2);
    }
    if ($price <= 0 && preg_match('~salePrice\s*:\s*([0-9.]+)~', $h, $m)) {   // ideasoft dataLayer
        $price = round((float) $m[1], 2);
    }
    if ($price <= 0 && (preg_match('~property="(?:og|product):price:amount"[^>]+content="([0-9.,]+)"~i', $h, $m)
        || preg_match('~itemprop="price"[^>]+content="([0-9.,]+)"~i', $h, $m))) {
        $price = normPrice($m[1]);
    }
    if (preg_match('~"piyasaFiyati"\s*:\s*([0-9.]+)~', $h, $m)) {             // ticimax liste (indirimsiz)
        $high = round((float) $m[1], 2);
    }
    if ($price <= 0) {
        return null;
    }

    return ['name' => $name, 'price' => round($price, 2), 'images' => $imgs, 'high' => $high, 'sku' => null];
}

function mapCategory(string $text): ?string
{
    $t = mb_strtolower($text, 'UTF-8');
    $has = fn ($kw) => str_contains($t, $kw);
    if ($has('bal') && ! $has('balık') && ! $has('balsamik')) {
        return 'kahvaltilik-recel';
    }
    if ($has('reçel') || $has('recel') || $has('marmelat') || $has('marmelât') || $has('pekmez') || $has('tahin') || $has('kahvalt')) {
        return 'kahvaltilik-recel';
    }
    if ($has('sirke') || $has('salça') || $has('salca') || $has('sos') || $has('ketçap') || $has('ketchup')) {
        return 'sos-salca-sirke';
    }
    if ($has('zeytinyağ') || $has('zeytinyag') || $has('zeytin')) {
        return 'zeytin-zeytinyagi-yag';
    }
    if ($has('mercimek') || $has('nohut') || $has('fasulye') || $has('bulgur') || $has('pirinç') || $has('pirinc') || $has('bakliyat') || $has('bezelye') || $has('barbunya') || $has('şehriye') || $has('bakla')) {
        return 'bakliyat-makarna';
    }
    if ($has('makarna') || $has('erişte') || $has('eriste')) {
        return 'bakliyat-makarna';
    }
    if ($has('kuru') || $has('kayısı') || $has('kayisi') || $has('incir') || $has('dut') || $has('üzüm') || $has('uzum') || $has('ceviz') || $has('fındık') || $has('findik') || $has('badem') || $has('kuruyemiş')) {
        return 'kuruyemis-kurutulmus';
    }

    return null;
}

// ---- 1) Ürün URL'lerini topla ----
$productUrls = [];
foreach ($cfg['sitemaps'] as $sm) {
    $xml = multiGet([$sm])[$sm] ?? '';
    $all = locs($xml);
    // alt-sitemap (index) ise takip et
    $subs = array_filter($all, fn ($u) => preg_match('~\.xml~i', $u) && $u !== $sm);
    if ($subs) {
        foreach (multiGet(array_values($subs)) as $x2) {
            $productUrls = array_merge($productUrls, locs($x2));
        }
    } else {
        $productUrls = array_merge($productUrls, $all);
    }
}
$productUrls = array_values(array_unique($productUrls));
if (! empty($cfg['filter'])) {
    $productUrls = array_values(array_filter($productUrls, fn ($u) => preg_match($cfg['filter'], $u)));
}
// açıkça ürün-olmayanları ele
$productUrls = array_values(array_filter($productUrls, fn ($u) => ! preg_match('~\.(xml|pdf|jpg|png|webp)$|/(uye|hesap|sepet|iletisim|kurumsal|blog|hakkimizda|gizlilik|sitemap)~i', $u)));

fwrite(STDERR, count($productUrls) . " ürün URL bulundu. Sayfalar indiriliyor...\n");

// ---- 2) Ürün sayfalarını çek ----
$pages = multiGet($productUrls, 12);

// ---- 3) JSON-LD ayıkla ----
$products = [];
$seen = [];
foreach ($pages as $url => $html) {
    if (strlen($html) < 500) {
        continue;
    }
    preg_match_all('#<script[^>]*application/ld\+json[^>]*>(.*?)</script>#is', $html, $mm);
    $prod = null;
    $crumb = '';
    foreach ($mm[1] as $blk) {
        $j = json_decode(trim($blk), true);
        if (! $j) {
            continue;
        }
        $nodes = isset($j['@graph']) ? $j['@graph'] : [$j];
        foreach ($nodes as $n) {
            $ty = $n['@type'] ?? '';
            $ty = is_array($ty) ? implode('|', $ty) : $ty;
            if (stripos($ty, 'Product') !== false && ! $prod) {
                $prod = $n;
            }
            if (stripos($ty, 'BreadcrumbList') !== false) {
                foreach ($n['itemListElement'] ?? [] as $e) {
                    $crumb .= ' ' . ($e['name'] ?? ($e['item']['name'] ?? ''));
                }
            }
        }
    }
    if (! $prod) {
        $fb = fallbackExtract($html);
        if (! $fb) {
            continue;
        }
        $prod = [
            'name' => $fb['name'],
            'sku' => $fb['sku'],
            'image' => $fb['images'],
            'offers' => ['price' => $fb['price']] + ($fb['high'] > 0 ? ['highPrice' => $fb['high']] : []),
        ];
    }

    $name = trim((string) ($prod['name'] ?? ''));
    if ($name === '') {
        continue;
    }
    // isim temizliği: sondaki " - Marka" / " - SonBreadcrumb" eklerini at
    // "(PESTİSİT VE AFLATOKSİN ANALİZLİ)" gibi analiz parantezlerini at
    $name = preg_replace('~\s*\([^)]*(?:ANAL[İIiı]Z|PEST[İIiı]S[İIiı]T|AFLATOKS[İIiı]N)[^)]*\)~u', '', $name);
    // sondaki " - Marka" ekini at (aksan/harf farkına dayanıklı: slug karşılaştırması)
    $brandSlug = slugify($cfg['brand']);
    if (preg_match('~^(.*?)\s*[-|]\s*([^-|]{2,40})$~u', $name, $nm)) {
        $tail = trim($nm[2]);
        $tailSlug = slugify($tail);
        if ($tailSlug === $brandSlug || str_contains($brandSlug, $tailSlug) || str_contains($tailSlug, $brandSlug)
            || str_contains(mb_strtolower($crumb, 'UTF-8'), mb_strtolower($tail, 'UTF-8'))
            || preg_match('~lar$|ler$~u', mb_strtolower($tail, 'UTF-8'))) {
            $name = trim($nm[1]);
        }
    }
    $name = trim(preg_replace('~\s{2,}~u', ' ', str_replace("' ", "'", $name)));

    // fiyat
    $offers = $prod['offers'] ?? [];
    if (isset($offers[0])) {
        $offers = $offers[0];
    }
    $price = normPrice($offers['price'] ?? ($offers['lowPrice'] ?? 0));
    // indirimsiz: yüksek/liste fiyatı varsa onu kullan
    if (! empty($cfg['list_price'])) {
        foreach (['highPrice', 'listPrice'] as $k) {
            if (isset($offers[$k]) && normPrice($offers[$k]) > $price) {
                $price = normPrice($offers[$k]);
            }
        }
        if (preg_match('~<del[^>]*>\s*([\d.,]+)~u', $html, $dm)) {
            $lp = normPrice($dm[1]);
            if ($lp > $price) {
                $price = $lp;
            }
        }
    }
    if ($price <= 0) {
        continue;
    }

    // görseller
    $imgs = [];
    $img = $prod['image'] ?? null;
    if (is_array($img)) {
        foreach ($img as $ii) {
            $imgs[] = is_array($ii) ? ($ii['url'] ?? '') : $ii;
        }
    } elseif ($img) {
        $imgs[] = $img;
    }
    $imgs = array_values(array_filter(array_unique($imgs)));

    // kategori
    $decision = $cfg['category'];
    $text = $name . ' ' . $crumb;
    if (! empty($cfg['require']) && ! preg_match($cfg['require'], $text)) {
        continue; // kategori-sınırı dışında
    }
    if ($decision === 'auto') {
        $cat = mapCategory($text) ?? 'bakkaliye';
    } elseif ($decision === 'auto_essen') {
        $cat = mapCategory($text);
        if (! in_array($cat, ['bakliyat-makarna', 'sos-salca-sirke'], true)) {
            continue; // essen: yalnız bakliyat + salça/sos
        }
    } else {
        $cat = $decision;
    }

    $slug = slugify($name);
    if ($slug === '' || isset($seen[$slug])) {
        continue;
    }
    $seen[$slug] = true;

    $sd = "{$name}, özenle seçilmiş organik içeriğiyle sofranıza doğallık katar; katkısız ve güvenilir.";
    $nameH = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $brandH = htmlspecialchars($cfg['brand'], ENT_QUOTES, 'UTF-8');
    $products[] = [
        'slug' => $slug,
        'name' => $name,
        'category' => $cat,
        'sku' => $prod['sku'] ?? null,
        'price' => round($price, 2),
        'unit' => 'adet',
        'unit_amount' => 1,
        'is_weight_based' => false,
        'images' => array_slice($imgs, 0, 3),
        'short_description' => $sd,
        'description' => "<p><strong>{$nameH}</strong>, {$brandH} güvencesiyle Organik Express rafında. "
            . 'Doğal ve katkısız içeriğiyle sağlıklı beslenmeye katkı sağlar.</p>'
            . '<ul><li>Organik içerik, katkısız üretim</li><li>Özenli paketleme ile taze teslim</li>'
            . '<li>Güvenle sipariş verin, kapınıza gelsin</li></ul>',
        'meta_title' => $name . ' | Organik Express',
        'meta_description' => $sd,
    ];
}

$out = ['source' => $only, 'scraped_at' => date('c'), 'products' => array_values($products)];
file_put_contents("{$outDir}/{$only}.json", json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "{$only}: " . count($products) . " ürün -> database/data/catalog2/{$only}.json\n";
