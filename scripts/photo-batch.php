<?php

// Wikimedia Commons'tan demo fotoğraf indirme (yalnız yerel demo).
// Toplu: php scripts/photo-batch.php
// Tekil: php scripts/photo-batch.php single <yol> "<arama>" <index> [genislik]

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 OrganikDemo/1.0';

function commonsFetch(string $path, string $search, int $index = 0, int $width = 900): bool
{
    $api = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search'
        . '&gsrsearch=' . urlencode('filetype:bitmap ' . $search)
        . '&gsrlimit=8&gsrnamespace=6&prop=imageinfo&iiprop=url'
        . '&iiurlwidth=' . $width . '&format=json';

    $ctx = stream_context_create(['http' => ['timeout' => 40, 'header' => 'User-Agent: ' . UA]]);
    $json = @file_get_contents($api, false, $ctx);
    $data = json_decode((string) $json, true);

    $pages = $data['query']['pages'] ?? [];
    if (! $pages) {
        fwrite(STDERR, "ARAMA BOS: {$search}\n");

        return false;
    }

    // API sırasız döner; 'index' alanına göre sırala
    usort($pages, fn ($a, $b) => ($a['index'] ?? 99) <=> ($b['index'] ?? 99));
    $page = $pages[$index] ?? null;
    $url = $page['imageinfo'][0]['thumburl'] ?? null;

    if (! $url) {
        fwrite(STDERR, "SONUC YOK: {$search} #{$index}\n");

        return false;
    }

    $img = @file_get_contents($url, false, $ctx);
    if ($img === false || strlen($img) < 5000) {
        fwrite(STDERR, "INDIRME HATASI: {$url}\n");

        return false;
    }

    $full = __DIR__ . '/../storage/app/public/' . $path;
    @mkdir(dirname($full), 0775, true);
    file_put_contents($full, $img);
    echo 'OK ' . str_pad($path, 28) . ' #' . $index . ' ' . round(strlen($img) / 1024) . "KB  ({$page['title']})\n";

    return true;
}

if (($argv[1] ?? '') === 'single') {
    exit(commonsFetch($argv[2], $argv[3], (int) ($argv[4] ?? 0), (int) ($argv[5] ?? 900)) ? 0 : 1);
}

// [yol, arama, indeks, genişlik]
$manifest = [
    // Hero banner'lar (geniş)
    ['banners/photo-1.jpg', 'fresh vegetables market display', 0, 1600],
    ['banners/photo-2.jpg', 'olive oil bottles glass', 0, 1600],
    ['banners/photo-3.jpg', 'turkish breakfast spread', 0, 1600],
    // Kategoriler (kare)
    ['categories/photo-1.jpg', 'fresh vegetables basket', 0, 900],
    ['categories/photo-6.jpg', 'cheese platter rustic', 0, 900],
    ['categories/photo-11.jpg', 'green olives bowl', 0, 900],
    ['categories/photo-15.jpg', 'dried legumes beans lentils', 0, 900],
    ['categories/photo-20.jpg', 'fresh fish ice market', 0, 900],
    ['categories/photo-24.jpg', 'natural soap bars handmade', 0, 900],
    ['categories/photo-28.jpg', 'lavender spa cosmetics', 0, 900],
    // Ürünler
    ['products/photo-1.jpg', 'ripe red tomatoes', 0, 900],
    ['products/photo-2.jpg', 'fresh cucumbers', 0, 900],
    ['products/photo-3.jpg', 'brown chicken eggs basket', 0, 900],
    ['products/photo-4.jpg', 'honey jar wooden dipper', 0, 900],
    ['products/photo-5.jpg', 'olive oil glass bottle', 0, 900],
    ['products/photo-6.jpg', 'wheat flour sack', 0, 900],
    ['products/photo-7.jpg', 'red lentils bowl', 0, 900],
    ['products/photo-8.jpg', 'raw almonds bowl', 0, 900],
    ['products/photo-9.jpg', 'aged cheese wheel', 0, 900],
    ['products/photo-10.jpg', 'green olives plate', 0, 900],
    ['products/photo-11.jpg', 'fresh parsley bunch', 0, 900],
    ['products/photo-12.jpg', 'liquid soap pump bottle', 0, 900],
    // Kutular
    ['bundles/photo-1.jpg', 'vegetable box harvest csa', 0, 1200],
    ['bundles/photo-2.jpg', 'breakfast table cheese honey', 0, 1200],
    ['bundles/photo-3.jpg', 'green smoothie fruit detox', 0, 1200],
    // Üreticiler
    ['producers/photo-1.jpg', 'olive grove trees', 0, 1200],
    ['producers/photo-2.jpg', 'vegetable greenhouse rows', 0, 1200],
    ['producers/photo-3.jpg', 'beekeeper hives apiary', 0, 1200],
    ['producers/photo-4.jpg', 'dairy cows pasture', 0, 1200],
    // Blog
    ['blog/photo-1.jpg', 'organic vegetables wooden table', 0, 1200],
    ['blog/photo-2.jpg', 'seasonal autumn fruits', 0, 1200],
    ['blog/photo-3.jpg', 'artichoke cooked dish', 0, 1200],
    ['blog/photo-4.jpg', 'olive harvest picking', 0, 1200],
    ['blog/photo-5.jpg', 'red lentil soup bowl', 0, 1200],
    ['blog/photo-6.jpg', 'roasted vegetables baking tray', 0, 1200],
];

$ok = 0;
foreach ($manifest as [$path, $search, $index, $width]) {
    commonsFetch($path, $search, $index, $width) && $ok++;
    usleep(250_000); // API'ye nazik davran
}
echo "\n{$ok}/" . count($manifest) . " indirildi\n";
