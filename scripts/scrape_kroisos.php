<?php

/**
 * Kroisos (WooCommerce Store API) → database/data/catalog2/kroisos.json
 * İndirimsiz fiyat = prices.regular_price (kuruş → TL). Görsel Woo'dan.
 *
 *   php scripts/scrape_kroisos.php
 */

$root = dirname(__DIR__);
$outDir = $root . '/database/data/catalog2';
@mkdir($outDir, 0775, true);

$domain = 'https://kroisos.com.tr';
$brand = 'Kroisos';
$category = 'zeytin-zeytinyagi';

function slugify(string $s): string
{
    $tr = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
    $s = mb_strtolower(strtr($s, $tr), 'UTF-8');

    return trim(preg_replace('~[^a-z0-9]+~', '-', $s), '-');
}

$products = [];
$seen = [];
for ($page = 1; $page <= 10; $page++) {
    $url = "{$domain}/wp-json/wc/store/v1/products?per_page=100&page={$page}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_USERAGENT => 'Mozilla/5.0', CURLOPT_SSL_VERIFYPEER => false]);
    $body = curl_exec($ch);
    curl_close($ch);
    $items = json_decode((string) $body, true);
    if (! is_array($items) || ! $items) {
        break;
    }

    foreach ($items as $it) {
        $name = trim((string) ($it['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $pr = $it['prices'] ?? [];
        $minor = (int) ($pr['currency_minor_unit'] ?? 2);
        $div = 10 ** $minor;
        $reg = (float) ($pr['regular_price'] ?? 0) / $div;   // indirimsiz
        $cur = (float) ($pr['price'] ?? 0) / $div;
        $price = $reg > 0 ? $reg : $cur;
        if ($price <= 0) {
            continue;
        }

        $slug = slugify($name);
        if ($slug === '' || isset($seen[$slug])) {
            continue;
        }
        $seen[$slug] = true;

        $imgs = [];
        foreach ($it['images'] ?? [] as $img) {
            if (! empty($img['src'])) {
                $imgs[] = $img['src'];
            }
        }

        $sd = "{$name}, özenle seçilmiş organik içeriğiyle sofranıza doğallık katar; katkısız ve güvenilir.";
        $nameH = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $products[] = [
            'slug' => $slug,
            'name' => $name,
            'category' => $category,
            'sku' => $it['sku'] ?? null,
            'price' => round($price, 2),
            'unit' => 'adet',
            'unit_amount' => 1,
            'is_weight_based' => false,
            'images' => array_slice($imgs, 0, 3),
            'short_description' => $sd,
            'description' => "<p><strong>{$nameH}</strong>, {$brand} güvencesiyle Organik Express rafında. "
                . 'Doğal ve katkısız içeriğiyle sağlıklı beslenmeye katkı sağlar.</p>'
                . '<ul><li>Organik içerik, katkısız üretim</li><li>Özenli paketleme ile taze teslim</li>'
                . '<li>Güvenle sipariş verin, kapınıza gelsin</li></ul>',
            'meta_title' => $name . ' | Organik Express',
            'meta_description' => $sd,
        ];
    }
    if (count($items) < 100) {
        break;
    }
}

file_put_contents("{$outDir}/kroisos.json", json_encode(['source' => 'kroisos', 'scraped_at' => date('c'), 'products' => $products], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo 'kroisos: ' . count($products) . " ürün -> database/data/catalog2/kroisos.json\n";
