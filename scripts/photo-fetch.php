<?php

// Demo fotoğraf yeniden çekme aracı (yalnız yerel kullanım).
// Kullanım: php scripts/photo-fetch.php <storage_relatif_yol> <keywords> <w> <h> <lock>
[$_, $path, $keywords, $w, $h, $lock] = $argv + [null, null, null, 800, 800, 1];

$url = "https://loremflickr.com/{$w}/{$h}/{$keywords}?lock={$lock}";
$ctx = stream_context_create(['http' => ['timeout' => 40, 'follow_location' => 1]]);
$data = @file_get_contents($url, false, $ctx);

if ($data === false || strlen($data) < 5000) {
    fwrite(STDERR, "FAIL {$url}\n");
    exit(1);
}

$full = __DIR__ . '/../storage/app/public/' . $path;
@mkdir(dirname($full), 0775, true);
file_put_contents($full, $data);
echo 'OK ' . $path . ' (' . round(strlen($data) / 1024) . " KB) <- {$keywords} lock={$lock}\n";
