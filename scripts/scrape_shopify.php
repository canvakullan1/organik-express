<?php

/**
 * Shopify tabanlı kaynaklardan (products.json) ürün kazır ve
 * database/data/catalog2/<kaynak>.json olarak yazar (import:catalog2 formatı).
 *
 *   php scripts/scrape_shopify.php
 *
 * Kaynak metin KOPYALANMAZ; özgün TR kısa açıklama + meta üretilir.
 */

$root = dirname(__DIR__);
$outDir = $root . '/database/data/catalog2';
@mkdir($outDir, 0775, true);

/** Kaynak tanımları: koleksiyon handle => hedef kategori slug (öncelik sırasına göre) */
$sources = [
    'sadeorganik' => [
        'domain' => 'https://sadeorganik.com.tr',
        'brand' => 'Sade Organik',
        // öncelik: en spesifik önce (aynı ürün birden çok koleksiyonda olabilir)
        'collections' => [
            'organik-bebek-urunleri' => 'bebek',
            'organik-makarnalar' => 'bakliyat-makarna',
            'organik-bakliyatlar' => 'bakliyat-makarna',
            'organik-soslar-ve-yaglar' => 'sos-salca-sirke', // yağ olanlar başlıktan zeytin-zeytinyagi'ya taşınır
        ],
    ],
    'wefood' => [
        'domain' => 'https://wefood.com.tr',
        'brand' => 'Wefood',
        'collections' => [
            'glutensiz-urunler' => 'glutensiz',
        ],
    ],
    'guzelgida' => [
        'domain' => 'https://siparis-guzelgida.com',
        'brand' => 'Güzel Gıda',
        'all_products' => true,          // /products.json + başlık filtresi
        'title_filter' => '~sirke~iu',   // yalnız sirkeler
        'category' => 'sos-salca-sirke',
        'collections' => [],
    ],
];

function fetch(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || ! $body) {
        return null;
    }
    $j = json_decode($body, true);

    return is_array($j) ? $j : null;
}

/** Başlıktan gramaj/hacim yakala (görsel amaçlı; birim 'adet' kalır) */
function priceOf(array $variant): float
{
    $p = (float) ($variant['price'] ?? 0);
    $cmp = (float) ($variant['compare_at_price'] ?? 0);
    // "indirimsiz" liste fiyatı: compare_at mantıklı şekilde daha yüksekse onu kullan
    return ($cmp > $p) ? $cmp : $p;
}

function shortDesc(string $name): string
{
    $n = trim(preg_replace('/\s+/', ' ', $name));

    return "{$n}, özenle seçilmiş organik içeriğiyle sofranıza doğallık katar; katkısız ve güvenilir.";
}

function longDesc(string $name, string $brand): string
{
    $n = htmlspecialchars(trim(preg_replace('/\s+/', ' ', $name)), ENT_QUOTES, 'UTF-8');
    $b = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');

    return "<p><strong>{$n}</strong>, {$b} güvencesiyle Organik Express rafında. Doğal ve katkısız içeriğiyle "
        . 'günlük beslenmenize sağlıklı bir seçenek sunar.</p>'
        . '<ul><li>Organik içerik, katkısız üretim</li>'
        . '<li>Soğuk zincir ve özenli paketleme ile taze teslim</li>'
        . '<li>Güvenle sipariş verin, kapınıza gelsin</li></ul>';
}

/** Başlıktan yağ olup olmadığını anla (soslar-ve-yaglar koleksiyonu için) */
function isOil(string $title): bool
{
    $t = mb_strtolower($title, 'UTF-8');

    return str_contains($t, 'yağ') || str_contains($t, 'zeytinyağ');
}

foreach ($sources as $sourceName => $cfg) {
    $seen = [];
    $products = [];

    $endpoints = ! empty($cfg['all_products'])
        ? ['__all__' => $cfg['category']]
        : $cfg['collections'];
    foreach ($endpoints as $handle => $catSlug) {
        $page = 1;
        while (true) {
            $base = $handle === '__all__'
                ? "{$cfg['domain']}/products.json"
                : "{$cfg['domain']}/collections/{$handle}/products.json";
            $url = "{$base}?limit=250&page={$page}";
            $data = fetch($url);
            $items = $data['products'] ?? [];
            if (! $items) {
                break;
            }
            foreach ($items as $it) {
                $slug = $it['handle'] ?? null;
                if (! $slug || isset($seen[$slug])) {
                    continue;
                }
                $seen[$slug] = true;

                $variant = $it['variants'][0] ?? [];
                $price = priceOf($variant);
                if ($price <= 0) {
                    continue; // fiyatsız/stoksuz atla
                }

                $title = trim($it['title'] ?? '');
                if (! empty($cfg['title_filter']) && ! preg_match($cfg['title_filter'], $title)) {
                    continue;
                }
                $cat = $catSlug;
                if ($handle === 'organik-soslar-ve-yaglar' && isOil($title)) {
                    $cat = 'zeytin-zeytinyagi-yag';
                }

                $images = [];
                foreach ($it['images'] ?? [] as $img) {
                    if (! empty($img['src'])) {
                        $images[] = $img['src'];
                    }
                }

                $products[] = [
                    'slug' => $slug,
                    'name' => $title,
                    'category' => $cat,
                    'sku' => $variant['sku'] ?? null,
                    'price' => round($price, 2),
                    'unit' => 'adet',
                    'unit_amount' => 1,
                    'is_weight_based' => false,
                    'images' => array_slice($images, 0, 4),
                    'short_description' => shortDesc($title),
                    'description' => longDesc($title, $cfg['brand']),
                    'meta_title' => $title . ' | Organik Express',
                    'meta_description' => shortDesc($title),
                ];
            }
            $page++;
            if ($page > 8) {
                break; // güvenlik
            }
        }
    }

    $out = [
        'source' => $sourceName,
        'scraped_at' => date('c'),
        'products' => $products,
    ];
    $file = "{$outDir}/{$sourceName}.json";
    file_put_contents($file, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo "{$sourceName}: " . count($products) . " ürün -> {$file}\n";
}
