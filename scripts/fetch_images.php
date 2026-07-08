<?php

/**
 * catalog2/*.json ürünlerinin ilk 2 görselini indirir, en fazla 800px'e küçültüp
 * storage/app/public/products/{slug}-{n}.jpg olarak kaydeder (JPEG q80).
 * Bu dosyalar repoya commit'lenir; import (--skip-images) onları DB'ye bağlar.
 *
 *   php scripts/fetch_images.php            # tüm kaynaklar
 *   php scripts/fetch_images.php egricayir  # tek kaynak
 *
 * Resumable: zaten var olan dosyayı atlar.
 */

$root = dirname(__DIR__);
$dir = $root . '/database/data/catalog2';
$outBase = $root . '/storage/app/public/products/';
@mkdir($outBase, 0775, true);

$only = $argv[1] ?? '';
$maxImgs = 2;
$maxSide = 800;

$files = glob($dir . '/*.json') ?: [];
if ($only !== '') {
    $files = array_filter($files, fn ($f) => basename($f, '.json') === $only);
}

// 1) indirilecek işleri topla (slug, idx, url) — mevcut dosyaları atla
$jobs = [];
foreach ($files as $f) {
    $d = json_decode((string) file_get_contents($f), true);
    foreach ($d['products'] ?? [] as $p) {
        $slug = $p['slug'] ?? null;
        if (! $slug || empty($p['images'])) {
            continue;
        }
        $idx = 0;
        foreach (array_slice($p['images'], 0, $maxImgs) as $url) {
            $idx++;
            $dest = $outBase . $slug . '-' . $idx . '.jpg';
            if (is_file($dest)) {
                continue;
            }
            $jobs[] = ['url' => (string) $url, 'dest' => $dest];
        }
    }
}
echo count($jobs) . " görsel inecek...\n";

function resizeToJpeg(string $bin, string $dest, int $maxSide): bool
{
    $im = @imagecreatefromstring($bin);
    if (! $im) {
        return false;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    if ($w < 1 || $h < 1) {
        imagedestroy($im);

        return false;
    }
    $scale = min(1.0, $maxSide / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    // şeffaf PNG/webp -> beyaz zemin
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
    imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = imagejpeg($dst, $dest, 80);
    imagedestroy($im);
    imagedestroy($dst);

    return $ok;
}

// 2) toplu indir (curl_multi) + küçült
$conc = 12;
$done = 0;
$fail = 0;
for ($i = 0; $i < count($jobs); $i += $conc) {
    $batch = array_slice($jobs, $i, $conc);
    $mh = curl_multi_init();
    $hs = [];
    foreach ($batch as $k => $job) {
        $ch = curl_init($job['url']);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30, CURLOPT_USERAGENT => 'Mozilla/5.0', CURLOPT_SSL_VERIFYPEER => false]);
        curl_multi_add_handle($mh, $ch);
        $hs[$k] = $ch;
    }
    do {
        $st = curl_multi_exec($mh, $running);
        curl_multi_select($mh, 1.0);
    } while ($running > 0 && $st === CURLM_OK);
    foreach ($hs as $k => $ch) {
        $bin = (string) curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        if ($code === 200 && strlen($bin) > 500 && resizeToJpeg($bin, $batch[$k]['dest'], $maxSide)) {
            $done++;
        } else {
            $fail++;
        }
    }
    curl_multi_close($mh);
    if ($i % 120 === 0) {
        echo "  {$done} ok / {$fail} hata\n";
    }
}
echo "Bitti: {$done} indirildi, {$fail} başarısız.\n";
