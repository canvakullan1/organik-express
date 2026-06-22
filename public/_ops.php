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
exit("ops: ?do=errmsg | ?do=log&n=N\n");
