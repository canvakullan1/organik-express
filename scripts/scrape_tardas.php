<?php
/**
 * satis.tardas.com.tr (IdeaSoft) organik gıda kataloğu kazıyıcı — yalnızca HTTP.
 * Ürün sitemap'inden tüm ürünleri gezer; isim, fiyat, stok durumu, görsel,
 * kaynak açıklama ve kategori çıkarır. STOK DIŞI ürünleri işaretler.
 * storage/app/import/tardas-raw.json dosyasına yazar.
 *
 * Çalıştırma:  php scripts/scrape_tardas.php
 */

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36';
const BASE = 'https://satis.tardas.com.tr';
const PROD_SITEMAP = 'https://satis.tardas.com.tr/xml/sitemap_product_1.xml?sr=6a4c6c8366cdc';
const CAT_SITEMAP = 'https://satis.tardas.com.tr/xml/sitemap_category_1.xml?sr=6a4c6c8366cc8';
const OUT = __DIR__ . '/../storage/app/import/tardas-raw.json';

function fetch_one(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => UA, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_ENCODING => '',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($code >= 200 && $code < 300 && $body) ? $body : null;
}

/** Paralel çek (curl_multi). url => html. */
function fetch_many(array $urls, int $concurrency = 12): array
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
                CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => UA, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_ENCODING => '',
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

function sitemap_locs(string $xml): array
{
    preg_match_all('#<loc>([^<]+)</loc>#', $xml, $m);

    return array_map('trim', $m[1]);
}

/** IdeaSoft ürün sayfası ayrıştır. */
function parse_product(string $url, string $html): ?array
{
    $name = null;
    if (preg_match('#<h1[^>]*class="section-title"[^>]*>(.*?)</h1>#is', $html, $m)) {
        $name = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if (! $name) {
        return null;
    }

    $price = null;
    if (preg_match('#itemprop=[\'"]price[\'"][^>]*content="([\d.]+)"#i', $html, $m)) {
        $price = (float) $m[1];
    }

    $avail = 'unknown';
    if (preg_match('#itemprop=[\'"]availability[\'"][^>]*schema\.org/(InStock|OutOfStock)#i', $html, $m)) {
        $avail = strtolower($m[1]) === 'instock' ? 'in' : 'out';
    }

    $image = null;
    if (preg_match('#property=[\'"]og:image[\'"][^>]*content=[\'"]([^\'"]+)[\'"]#i', $html, $m)) {
        $image = preg_replace('/\?.*$/', '', trim($m[1])); // revision querystring'i at
    }

    // Açıklama: product-detail-tab-content bloğu
    $desc = null;
    if (preg_match('#class="product-detail-tab-content"[^>]*>(.*?)</div>\s*</div>#is', $html, $m)) {
        $desc = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    // Marka + kategori (JS "Name:" alanlarından ilk üçü: ürün, kategori, marka)
    $brand = null;
    if (preg_match_all('#Name:\s*"([^"]{2,80})"#', $html, $m)) {
        // 0=ürün, sonrakiler kategori/marka olabilir; "Organik" içermeyen kısa ad marka olabilir
        foreach (array_slice($m[1], 1) as $cand) {
            if (stripos($cand, 'happy life') !== false || stripos($cand, 'organik') === false) {
                $brand = trim($cand);
                break;
            }
        }
    }

    return [
        'source_url' => $url,
        'slug' => basename(parse_url($url, PHP_URL_PATH)),
        'name' => $name,
        'brand' => $brand,
        'price' => $price,
        'currency' => 'TRY',
        'availability' => $avail,
        'source_description' => $desc !== '' ? $desc : null,
        'images' => $image ? [$image] : [],
    ];
}

// 1) Kategori sitemap → kategori sayfalarını çek → ürün slug → kategori haritası
fwrite(STDERR, "Kategori haritası kuruluyor...\n");
$catXml = fetch_one(CAT_SITEMAP);
$catUrls = $catXml ? sitemap_locs($catXml) : [];
// parent/genel kategorileri map'te düşük öncelikli tut
$generic = ['organik-gida', 'diger-organik-urunler', 'konvansiyonel-urunler'];
$catPages = fetch_many($catUrls, 8);
$slugToCat = [];
foreach ($catUrls as $cu) {
    $html = $catPages[$cu] ?? null;
    if (! $html) {
        continue;
    }
    $catSlug = basename(parse_url($cu, PHP_URL_PATH));
    preg_match_all('#/urun/([a-z0-9\-]+)#i', $html, $pm);
    foreach (array_unique($pm[1]) as $ps) {
        // spesifik kategori varsa generic'i ezme
        if (! isset($slugToCat[$ps]) || (in_array($slugToCat[$ps], $generic, true) && ! in_array($catSlug, $generic, true))) {
            $slugToCat[$ps] = $catSlug;
        }
    }
}
fwrite(STDERR, sprintf("Kategori eşlemesi: %d ürün slug\n", count($slugToCat)));

// 2) Ürün sitemap → tüm ürün sayfaları
fwrite(STDERR, "Ürün sitemap çekiliyor...\n");
$prodXml = fetch_one(PROD_SITEMAP);
$prodUrls = $prodXml ? sitemap_locs($prodXml) : [];
fwrite(STDERR, sprintf("Ürün sayfası: %d\n", count($prodUrls)));

$pages = fetch_many($prodUrls, 12);

$products = [];
$outStock = 0;
$failed = [];
foreach ($prodUrls as $url) {
    $html = $pages[$url] ?? null;
    if (! $html) {
        $failed[] = $url;

        continue;
    }
    $p = parse_product($url, $html);
    if (! $p) {
        $failed[] = $url;

        continue;
    }
    $p['source_category'] = $slugToCat[$p['slug']] ?? null;
    if ($p['availability'] === 'out') {
        $outStock++;
    }
    $products[] = $p;
}

usort($products, fn ($a, $b) => strcmp($a['name'], $b['name']));

if (! is_dir(dirname(OUT))) {
    mkdir(dirname(OUT), 0775, true);
}
file_put_contents(OUT, json_encode([
    'scraped_at' => date('c'),
    'count' => count($products),
    'in_stock' => count($products) - $outStock,
    'out_of_stock' => $outStock,
    'failed' => $failed,
    'products' => $products,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

fwrite(STDERR, sprintf(
    "\nTAMAM: %d ürün (%d stokta, %d stok dışı), %d başarısız.\n%s\n",
    count($products), count($products) - $outStock, $outStock, count($failed), OUT
));
