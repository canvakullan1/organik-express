<?php
// Geçici operasyon ucu. Token = .env'deki APP_KEY (repoda gizli değil).
$env = @file_get_contents('/home/organikexpress/repositories/site/.env');
preg_match('/APP_KEY=(.*)/', (string) $env, $m);
$secret = trim($m[1] ?? '');
if ($secret === '' || ($_GET['token'] ?? '') !== $secret) { http_response_code(403); exit('forbidden'); }

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(120);

$repo = '/home/organikexpress/repositories/site';
$log = "$repo/storage/logs/laravel.log";
$do = $_GET['do'] ?? '';

if ($do === 'errmsg') {
    if (! file_exists($log)) { exit("log yok\n"); }
    $lines = file($log, FILE_IGNORE_NEW_LINES);
    $hits = array_values(array_filter($lines, fn ($x) => strpos($x, '.ERROR:') !== false));
    exit(implode("\n\n", array_slice($hits, -4)));
}
if ($do === 'log') {
    $n = (int) ($_GET['n'] ?? 120);
    if (! file_exists($log)) { exit("log yok\n"); }
    $lines = file($log, FILE_IGNORE_NEW_LINES);
    exit(implode("\n", array_slice($lines, -$n)));
}
if ($do === 'lsimg') {
    $doc = '/home/organikexpress/public_html';
    echo "public_html/storage link: " . (is_link("$doc/storage") ? readlink("$doc/storage") : (is_dir("$doc/storage") ? 'GERCEK-KLASOR' : 'YOK')) . "\n";
    echo "link uzerinden banners/photo-1.jpg: " . (file_exists("$doc/storage/banners/photo-1.jpg") ? 'VAR' : 'YOK') . "\n";
    echo "repo'da banners/photo-1.jpg: " . (file_exists("$repo/storage/app/public/banners/photo-1.jpg") ? 'VAR' : 'YOK') . "\n";
    echo "repo banners listesi:\n" . @shell_exec("ls -1 $repo/storage/app/public/banners 2>&1");
    exit;
}
exit("ops: ?do=errmsg | ?do=log&n=N | ?do=lsimg\n");
