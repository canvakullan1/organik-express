<?php
/**
 * ekotime.com.tr (OpenCart) + tazedirekt/migros yumurta sayfaları kazıyıcı.
 * Ürün sayfalarından JSON-LD (Product), OG meta ve HTML ayrıştırır;
 * storage/app/import/ekotime-raw.json dosyasına yazar.
 *
 * Çalıştırma:  php scripts/scrape_ekotime.php
 */

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36';
const OUT = __DIR__ . '/../storage/app/import/ekotime-raw.json';

// slug => [url, liste fiyatı (teyit için)]
$EKOTIME = [
    // ORGANİK EKMEKLER (31)
    'cavdarli-organik-ekmek-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/cavdarli-organik-ekmek-700-gr/',
    'organik-akdeniz-ekmegi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-akdeniz-ekmegi-500-gr/',
    'organik-artisan-siyez-ekmek-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-artisan-siyez-ekmek-700-gr/',
    'organik-artisan-tam-bugday-ekmek-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-artisan-tam-bugday-ekmek-700-gr/',
    'organik-baget-ekmek-9-lu-susamli-440-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-baget-ekmek-9-lu-susamli-440-gr/',
    'organik-cavdarli-tost-ekmegi-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-cavdarli-tost-ekmegi-700-gr/',
    'organik-kavilca-ekmegi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-kavilca-ekmegi-500-gr/',
    'organik-mini-baget-ekmek-10-lu-sade-400-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-mini-baget-ekmek-10-lu-sade-400-gr/',
    'organik-misir-ekmegi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-misir-ekmegi-500-gr/',
    'organik-osmanli-ekmegi-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-osmanli-ekmegi-700-gr/',
    'organik-ramazan-pidesi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-ramazan-pidesi-500-gr/',
    'organik-sertifikali-2-li-hamburger-ekmegi-200-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-sertifikali-2-li-hamburger-ekmegi-200-gr/',
    'organik-siyez-balina-ekmegi-1000-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-siyez-balina-ekmegi-1000-gr/',
    'organik-siyez-cevizli-ekmek-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-siyez-cevizli-ekmek-500-gr/',
    'organik-siyez-tost-ekmegi-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-siyez-tost-ekmegi-700-gr/',
    'organik-soguk-fermente-sari-somun-ekmek-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-soguk-fermente-sari-somun-ekmek-700-gr/',
    'organik-soguk-fermente-tam-bugday-ekmek-1-kg' => 'https://ekotime.com.tr/organik-ekmekler/organik-soguk-fermente-tam-bugday-ekmek-1-kg/',
    'organik-soguk-fermente-tam-bugday-ekmek-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-soguk-fermente-tam-bugday-ekmek-500-gr/',
    'organik-soguk-fermente-tam-bugday-ekmek-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-soguk-fermente-tam-bugday-ekmek-700-gr/',
    'organik-tam-bugday-balina-ekmegi-1000-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-tam-bugday-balina-ekmegi-1000-gr/',
    'organik-tam-bugday-tost-ekmegi-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-tam-bugday-tost-ekmegi-700-gr/',
    'organik-tost-ekmegi-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-tost-ekmegi-700-gr/',
    'organik-vegan-tam-karabugday-ekmegi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/organik-vegan-tam-karabugday-ekmegi-500-gr/',
    'siyez-koy-ekmegi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/siyez-koy-ekmegi-500-gr/',
    'siyez-uzumlu-cevizli-balina-ekmegi-1000-gr' => 'https://ekotime.com.tr/organik-ekmekler/siyez-uzumlu-cevizli-balina-ekmegi-1000-gr/',
    'siyez-uzumlu-cevizli-balina-ekmegi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/siyez-uzumlu-cevizli-balina-ekmegi-500-gr/',
    'soguk-fermente-organik-siyez-ekmek-1kg' => 'https://ekotime.com.tr/organik-ekmekler/soguk-fermente-organik-siyez-ekmek-1kg/',
    'soguk-fermente-organik-siyez-ekmek-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/soguk-fermente-organik-siyez-ekmek-500-gr/',
    'soguk-fermente-organik-siyez-ekmek-700-gr' => 'https://ekotime.com.tr/organik-ekmekler/soguk-fermente-organik-siyez-ekmek-700-gr/',
    'tahilli-eksi-maya-alman-cavdar-ekmegi-500-gr' => 'https://ekotime.com.tr/organik-ekmekler/tahilli-eksi-maya-alman-cavdar-ekmegi-500-gr/',
    'tam-bugday-organik-buyuk-tost-ekmegi-1500-gr' => 'https://ekotime.com.tr/organik-ekmekler/tam-bugday-organik-buyuk-tost-ekmegi-1500-gr/',
    // ORGANİK ERİŞTE, MAKARNA & MANTI (8)
    'organik-beyaz-eriste-350-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-beyaz-eriste-350-gr/',
    'organik-burgu-makarna-500-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-burgu-makarna-500-gr/',
    'organik-kalem-makarna-500-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-kalem-makarna-500-gr/',
    'organik-siyez-bugday-corbalik-eriste-400-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-siyez-bugday-corbalik-eriste-400-gr/',
    'organik-siyez-eriste-350-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-siyez-eriste-350-gr/',
    'organik-tam-bugday-corbalik-eriste-400-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-tam-bugday-corbalik-eriste-400-gr/',
    'organik-tam-bugday-eriste-350-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-tam-bugday-eriste-350-gr/',
    'organik-tam-bugday-manti-500-gr' => 'https://ekotime.com.tr/organik-eriste-makarna-manti/organik-tam-bugday-manti-500-gr/',
    // ORGANİK SİMİT & POĞAÇA (10)
    'organik-pogaca-peynirli-80-gr-2-adet' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-pogaca-peynirli-80-gr-2-adet/',
    'organik-pogaca-zeytinli-80-gr-2-adet' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-pogaca-zeytinli-80-gr-2-adet/',
    'organik-sade-pogaca-80-gr-adet-2' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-sade-pogaca-80-gr-adet-2/',
    'organik-siyez-aycekirdekli-simit-2-lu-yari-pismis-300-gr' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-siyez-aycekirdekli-simit-2-lu-yari-pismis-300-gr/',
    'organik-siyez-aycekirdekli-simit-3-lu-yari-pismis-300-gr' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-siyez-aycekirdekli-simit-3-lu-yari-pismis-300-gr/',
    'organik-siyez-susamli-simit-2-lu-yari-pismis-300-gr' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-siyez-susamli-simit-2-lu-yari-pismis-300-gr/',
    'organik-siyez-susamli-simit-3-lu-yari-pismis-300-gr' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-siyez-susamli-simit-3-lu-yari-pismis-300-gr/',
    'organik-tahinli-corek-200-gr' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-tahinli-corek-200-gr/',
    'organik-tam-bugday-aycekirdekli-simit-3-lu-yari-pismis-300-gr' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-tam-bugday-aycekirdekli-simit-3-lu-yari-pismis-300-gr/',
    'organik-tam-bugday-susamli-simit-3-lu-yari-pismis-300-gr' => 'https://ekotime.com.tr/pastane-grubu/organik-simit-pogaca/organik-tam-bugday-susamli-simit-3-lu-yari-pismis-300-gr/',
];

// Yumurta sayfaları (fiyat + görsel; sayfa JS-render → REST API'den okunur.
// URL sonundaki hex id, decimal ürün koduna çevrilir: p-1313494 → hexdec = 20001940)
$EGGS = [
    'orvital-organik-yumurta-10lu-tazedirekt' => [
        'url' => 'https://www.tazedirekt.com/organik-yumurta-10lu-p-1313494',
        'api' => 'https://rest.tazedirekt.com/sanalmarket/products/20001940',
    ],
    'aoc-organik-yumurta-10lu-m-tazedirekt' => [
        'url' => 'https://www.tazedirekt.com/organik-yumurta-10lu-m-boy-53-63-g-p-1312d4b',
        'api' => 'https://rest.tazedirekt.com/sanalmarket/products/20000075',
    ],
    'raya-organik-8li-m-yumurta-migros' => [
        'url' => 'https://www.migros.com.tr/raya-organik-8li-m-boy-yumurta-53-62-g-p-1312e36',
        'api' => 'https://rest.migros.com.tr/sanalmarket/products/20000310',
    ],
    'orvital-organik-10lu-m-yumurta-migros' => [
        'url' => 'https://www.migros.com.tr/orvital-organik-10lu-m-orta-boy-yumurta-53-62-g-p-1313494',
        'api' => 'https://rest.migros.com.tr/sanalmarket/products/20001940',
    ],
];

/** Birden çok URL'i paralel çek (curl_multi). url => html döner. */
function fetch_many(array $urls, int $concurrency = 8): array
{
    $results = [];
    $queue = array_values($urls);
    $i = 0;

    while ($i < count($queue)) {
        $batch = array_slice($queue, $i, $concurrency);
        $mh = curl_multi_init();
        $handles = [];

        foreach ($batch as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_USERAGENT => UA,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => '',
                CURLOPT_HTTPHEADER => ['Accept-Language: tr-TR,tr;q=0.9'],
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$url] = $ch;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $url => $ch) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $body = curl_multi_getcontent($ch);
            $results[$url] = ($code >= 200 && $code < 300 && $body) ? $body : null;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        $i += $concurrency;
        fwrite(STDERR, sprintf("  çekildi: %d / %d\n", min($i, count($queue)), count($queue)));
    }

    return $results;
}

/** JSON-LD Product bloklarını döndür. */
function jsonld_products(string $html): array
{
    $out = [];
    if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/s', $html, $m)) {
        foreach ($m[1] as $json) {
            $data = json_decode(trim($json), true);
            if (! is_array($data)) {
                continue;
            }
            $nodes = isset($data['@graph']) ? $data['@graph'] : [$data];
            foreach ($nodes as $node) {
                $type = $node['@type'] ?? null;
                if ($type === 'Product' || (is_array($type) && in_array('Product', $type, true))) {
                    $out[] = $node;
                }
            }
        }
    }

    return $out;
}

/** "1.234,56" / "175,00" → float (TR para formatı). */
function tr_price(string $s): float
{
    return (float) str_replace(',', '.', str_replace('.', '', trim($s)));
}

/** Ekotime (OpenCart tema) ürün sayfası ayrıştır. */
function parse_ekotime(string $url, string $slug, string $html): array
{
    $name = $sku = $desc = null;
    $price = null;
    $images = [];

    // Ad: <title> temiz ürün adı içeriyor (og:title / JSON-LD yok)
    if (preg_match('/<title>([^<]+)<\/title>/', $html, $mm)) {
        $name = trim(html_entity_decode($mm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    // Fiyat: ana ürün bloğu <h2 class="pro-price">₺ 175,00</h2>
    if (preg_match('/class="pro-price"[^>]*>\s*₺?\s*([\d.,]+)/u', $html, $mm)) {
        $price = tr_price($mm[1]);
    }

    // Ana galeri: <a class="thumbnail" href="...-700x700.jpg"> (ilgili ürünler bu sınıfı kullanmaz)
    if (preg_match_all('/class="thumbnail"[^>]*href="(https:\/\/ekotime\.com\.tr\/image\/cache\/catalog\/[^"]+)"/i', $html, $mm)) {
        $images = $mm[1];
    }

    // Açıklama: tab-description sekmesi (referans metin; sitede özgün SEO metni kullanılacak)
    if (preg_match('/<div[^>]*id="tab-description"[^>]*>(.*?)<\/div>/s', $html, $mm)) {
        $desc = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($mm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    // Barkod (bazı ürünlerde görünür)
    if (preg_match('/(?:Barkod|Stok Kodu|Ürün Kodu|Model)[^0-9]{0,60}(\d{8,14})/u', $html, $mm)) {
        $sku = $mm[1];
    }

    // Görselleri tekille (boyut son ekini anahtar yap)
    $clean = [];
    foreach ($images as $img) {
        $key = preg_replace('/-\d+x\d+\.(jpg|jpeg|png|webp)$/i', '', $img);
        if (! isset($clean[$key])) {
            $clean[$key] = $img;
        }
    }

    return [
        'source_url' => $url,
        'slug' => $slug,
        'name' => $name ?: null,
        'sku' => $sku ?: null,
        'price' => $price,
        'currency' => 'TRY',
        'source_description' => $desc !== null && $desc !== '' ? $desc : null,
        'images' => array_values($clean),
    ];
}

/** Migros/Tazedirekt sayfasından fiyat + görsel çıkar (JSON-LD ya da gömülü state). */
function parse_egg(string $url, string $slug, string $html): array
{
    $name = null;
    $price = null;
    $images = [];

    foreach (jsonld_products($html) as $node) {
        $name = $name ?: trim((string) ($node['name'] ?? ''));
        foreach ((array) ($node['image'] ?? []) as $img) {
            if (is_string($img)) {
                $images[] = $img;
            }
        }
        $offer = $node['offers'] ?? null;
        if (is_array($offer)) {
            $offers = isset($offer['@type']) ? [$offer] : $offer;
            foreach ($offers as $o) {
                if (is_array($o) && isset($o['price']) && $price === null) {
                    $price = (float) $o['price'];
                }
            }
        }
    }

    // Gömülü state fallback: "salePrice":{"amount":156.5,...} veya "shownPrice":15650 (kuruş)
    if ($price === null && preg_match('/"salePrice"\s*:\s*\{[^}]*"amount"\s*:\s*([\d.]+)/', $html, $mm)) {
        $price = (float) $mm[1];
    }
    if ($price === null && preg_match('/"shownPrice"\s*:\s*(\d+)/', $html, $mm)) {
        $price = ((int) $mm[1]) / 100;
    }
    if ($price === null && preg_match('/"regularPrice"\s*:\s*(\d+)/', $html, $mm)) {
        $price = ((int) $mm[1]) / 100;
    }

    if (empty($images) && preg_match_all('#https://images\.migrosone\.com/[^"\'\\\\ ]+\.(?:jpg|jpeg|png|webp)#i', $html, $mm)) {
        $images = array_slice(array_values(array_unique($mm[0])), 0, 4);
    }

    return [
        'source_url' => $url,
        'slug' => $slug,
        'name' => $name,
        'sku' => null,
        'price' => $price,
        'currency' => 'TRY',
        'source_description' => null,
        'images' => array_values(array_unique($images)),
    ];
}

// ---- Çalıştır ----
fwrite(STDERR, "Ekotime ürün sayfaları çekiliyor (" . count($EKOTIME) . ")...\n");
$pages = fetch_many(array_values($EKOTIME), 8);

$products = [];
$failed = [];
foreach ($EKOTIME as $slug => $url) {
    $html = $pages[$url] ?? null;
    if (! $html) {
        $failed[] = $url;
        continue;
    }
    $p = parse_ekotime($url, $slug, $html);
    if (! $p['name']) {
        $failed[] = $url;
        continue;
    }
    $products[] = $p;
}

fwrite(STDERR, "Yumurta sayfaları çekiliyor (" . count($EGGS) . ")...\n");
$eggPages = fetch_many(array_column($EGGS, 'url'), 4);
$apiJsons = fetch_many(array_column($EGGS, 'api'), 4);
foreach ($EGGS as $slug => $cfg) {
    $p = parse_egg($cfg['url'], $slug, (string) ($eggPages[$cfg['url']] ?? ''));

    // REST API: isim, fiyat (kuruş), görseller — sayfada SSR yoksa tek güvenilir kaynak
    $api = json_decode((string) ($apiJsons[$cfg['api']] ?? ''), true);
    $d = $api['data'] ?? null;
    if (is_array($d)) {
        $p['name'] = $p['name'] ?: ($d['name'] ?? null);
        if ($p['price'] === null && isset($d['shownPrice'])) {
            $p['price'] = ((int) $d['shownPrice']) / 100;
        }
        $p['sku'] = $p['sku'] ?: ($d['sku'] ?? null);
        if (! empty($d['images']) && is_array($d['images'])) {
            foreach ($d['images'] as $im) {
                foreach (['EXTRALARGE', 'LARGE', 'MEDIUM'] as $size) {
                    if (! empty($im['urls'][$size]['productDetail'])) {
                        $p['images'][] = $im['urls'][$size]['productDetail'];
                        break;
                    }
                }
            }
            $p['images'] = array_values(array_unique($p['images']));
        }
    }

    if (! $p['name']) {
        $failed[] = $cfg['url'];
        continue;
    }
    $products[] = $p;
}

if (! is_dir(dirname(OUT))) {
    mkdir(dirname(OUT), 0775, true);
}
file_put_contents(OUT, json_encode([
    'scraped_at' => date('c'),
    'count' => count($products),
    'failed' => $failed,
    'products' => $products,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

fwrite(STDERR, sprintf("\nTAMAM: %d ürün yazıldı, %d başarısız.\n%s\n", count($products), count($failed), OUT));
