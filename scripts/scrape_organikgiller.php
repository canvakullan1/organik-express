<?php
/**
 * organikgiller.com (Wix) katalog kazıyıcı — yalnızca HTTP, DB gerektirmez.
 * store-products-sitemap.xml içindeki tüm ürün sayfalarını gezer,
 * JSON-LD (Product) ve OG meta verisini ayrıştırır,
 * storage/app/import/organikgiller-raw.json dosyasına yazar.
 *
 * Çalıştırma:  php scripts/scrape_organikgiller.php
 */

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36';
const SITEMAP = 'https://www.organikgiller.com/store-products-sitemap.xml';
const OUT = __DIR__ . '/../storage/app/import/organikgiller-raw.json';

function fetch_one(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => UA,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => '',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($code >= 200 && $code < 300 && $body) ? $body : null;
}

/** Birden çok URL'i paralel çek (curl_multi). url => html döner. */
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
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => UA,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_ENCODING => '',
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

/** Ürün sayfasından JSON-LD Product + OG meta ayrıştır. */
function parse_product(string $url, string $html): ?array
{
    // JSON-LD Product bloğu
    $name = $sku = $desc = null;
    $price = null;
    $currency = 'TRY';
    $availability = null;
    $images = [];

    if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m)) {
        foreach ($m[1] as $json) {
            $data = json_decode(trim($json), true);
            if (! is_array($data)) {
                continue;
            }
            $type = $data['@type'] ?? null;
            if ($type === 'Product' || (is_array($type) && in_array('Product', $type, true))) {
                $name = trim($data['name'] ?? '');
                $sku = trim((string) ($data['sku'] ?? ''));
                $desc = trim((string) ($data['description'] ?? ''));

                $imgNode = $data['image'] ?? ($data['Image'] ?? null);
                foreach ((array) $imgNode as $img) {
                    if (is_string($img)) {
                        $images[] = $img;
                    } elseif (is_array($img) && ! empty($img['contentUrl'])) {
                        $images[] = $img['contentUrl'];
                    }
                }

                $offer = $data['Offers'] ?? ($data['offers'] ?? null);
                if (is_array($offer)) {
                    // tek offer ya da liste olabilir
                    $offers = isset($offer['@type']) ? [$offer] : $offer;
                    foreach ($offers as $o) {
                        if (! is_array($o)) {
                            continue;
                        }
                        $p = $o['price'] ?? ($o['Price'] ?? null);
                        if ($p !== null && $price === null) {
                            $price = (float) $p;
                        }
                        $currency = $o['priceCurrency'] ?? ($o['PriceCurrency'] ?? $currency);
                        $availability = $o['Availability'] ?? ($o['availability'] ?? $availability);
                    }
                }
            }
        }
    }

    // OG fallback
    if (! $price && preg_match('/<meta property="product:price:amount" content="([^"]+)"/', $html, $mm)) {
        $price = (float) $mm[1];
    }
    if (! $name && preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $mm)) {
        $name = trim(html_entity_decode($mm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $name = preg_replace('/\s*\|\s*Organikgiller\s*$/u', '', $name);
    }

    if (! $name) {
        return null;
    }

    // Görselleri normalize et: en yüksek çözünürlük (w_1280) + tekille
    $clean = [];
    foreach ($images as $img) {
        // .../v1/fit/w_500,h_500,q_90/file.png  → w_1280,h_1280
        $img = preg_replace('#/v1/fit/[^/]+/#', '/v1/fit/w_1280,h_1280,q_90/', $img);
        // base media id'e göre tekille
        $key = preg_replace('#/v1/.*$#', '', $img);
        $clean[$key] = $img;
    }

    return [
        'source_url' => $url,
        'slug' => basename($url),
        'name' => html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'sku' => $sku ?: null,
        'price' => $price,
        'currency' => $currency,
        'availability' => $availability ? (str_contains($availability, 'InStock') ? 'in' : 'out') : null,
        'source_description' => $desc !== '' ? $desc : null,
        'images' => array_values($clean),
    ];
}

// 1) Sitemap → ürün URL'leri + sitemap görseli
fwrite(STDERR, "Sitemap çekiliyor...\n");
$xml = fetch_one(SITEMAP);
if (! $xml) {
    fwrite(STDERR, "HATA: sitemap alınamadı\n");
    exit(1);
}

preg_match_all('#<url>\s*<loc>([^<]+)</loc>.*?(?:<image:loc>([^<]+)</image:loc>)?\s*</url>#s', $xml, $um, PREG_SET_ORDER);
$urls = [];
$sitemapImg = [];
foreach ($um as $u) {
    $loc = trim($u[1]);
    if (! str_contains($loc, '/product-page/')) {
        continue;
    }
    $urls[] = $loc;
    if (! empty($u[2])) {
        $sitemapImg[$loc] = trim($u[2]);
    }
}
$urls = array_values(array_unique($urls));
fwrite(STDERR, sprintf("Ürün sayfası: %d\n", count($urls)));

// 2) Tüm ürün sayfalarını paralel çek
$pages = fetch_many($urls, 12);

// 3) Ayrıştır
$products = [];
$failed = [];
foreach ($urls as $url) {
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
    // sitemap görselini de ekle (yoksa)
    if (empty($p['images']) && ! empty($sitemapImg[$url])) {
        $img = preg_replace('#/v1/fit/[^/]+/#', '/v1/fit/w_1280,h_1280,q_90/', $sitemapImg[$url]);
        $p['images'] = [$img];
    }
    $products[] = $p;
}

usort($products, fn ($a, $b) => strcmp($a['name'], $b['name']));

file_put_contents(OUT, json_encode([
    'scraped_at' => date('c'),
    'count' => count($products),
    'failed' => $failed,
    'products' => $products,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

fwrite(STDERR, sprintf("\nTAMAM: %d ürün yazıldı, %d başarısız.\n%s\n", count($products), count($failed), OUT));
