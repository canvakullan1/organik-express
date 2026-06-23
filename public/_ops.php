<?php
// Operasyon ucu. Token = .env'deki APP_KEY (repoda gizli değil).
$repo = '/home/organikexpress/repositories/site';
$docroot = '/home/organikexpress/public_html';
$publicStorage = $docroot . '/storage';

$env = @file_get_contents($repo . '/.env');
preg_match('/APP_KEY=(.*)/', (string) $env, $m);
$secret = trim($m[1] ?? '');
if ($secret === '' || ($_GET['token'] ?? '') !== $secret) { http_response_code(403); exit('forbidden'); }

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300);
$log = "$repo/storage/logs/laravel.log";
$do = $_GET['do'] ?? '';

if (! function_exists('shell_exec')) { exit("shell_exec KAPALI\n"); }
function findbin($cands, $needle) {
    foreach ($cands as $c) { $v = @shell_exec("$c --version 2>&1"); if ($v && stripos($v, $needle) !== false) return $c; }
    return null;
}
$git = findbin(['git', '/usr/bin/git', '/usr/local/cpanel/3rdparty/bin/git', '/opt/cpanel/git/bin/git', '/usr/local/bin/git'], 'git version');
$php = findbin(['/usr/local/bin/php', '/opt/cpanel/ea-php82/root/usr/bin/php', '/opt/cpanel/ea-php83/root/usr/bin/php', 'php'], 'PHP ');

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
if ($do === 'ext') {
    $need = ['fileinfo', 'intl', 'gd', 'exif', 'mbstring', 'zip', 'bcmath', 'curl', 'openssl', 'pdo_mysql', 'fileinfo', 'iconv', 'dom'];
    foreach (array_unique($need) as $e) {
        echo str_pad($e, 12) . (extension_loaded($e) ? 'ACIK' : 'KAPALI <<<') . "\n";
    }
    echo 'PHP: ' . PHP_VERSION . "\n";
    exit;
}
if ($do === 'lsimg') {
    echo "public_html/storage: " . (is_link($publicStorage) ? 'symlink->' . readlink($publicStorage) : (is_dir($publicStorage) ? 'GERCEK-KLASOR' : 'YOK')) . "\n";
    echo "banners/photo-1.jpg: " . (file_exists("$publicStorage/banners/photo-1.jpg") ? 'VAR' : 'YOK') . "\n";
    echo "icindekiler: " . @shell_exec("ls -1 $publicStorage 2>&1");
    exit;
}
if ($do === 'deploy') {
    if (! $git) { exit("git bulunamadi\n"); }
    echo "git=$git php=$php\n";
    echo @shell_exec("cd $repo && $git fetch origin main 2>&1");
    echo @shell_exec("cd $repo && $git reset --hard origin/main 2>&1");
    echo "HEAD: " . @shell_exec("cd $repo && $git rev-parse --short HEAD 2>&1");

    // .env'e PUBLIC_DISK_ROOT ekle (yoksa) — yuklemeler public_html/storage'a gitsin
    if (strpos((string) @file_get_contents("$repo/.env"), 'PUBLIC_DISK_ROOT') === false) {
        @file_put_contents("$repo/.env", "\nPUBLIC_DISK_ROOT=$publicStorage\n", FILE_APPEND);
        echo ".env: PUBLIC_DISK_ROOT eklendi\n";
    }

    if ($php) {
        echo @shell_exec("cd $repo && $php artisan migrate --force 2>&1");
        echo @shell_exec("cd $repo && $php artisan optimize:clear 2>&1");
    }

    // public/ -> public_html (build, htaccess, favicon...)
    echo @shell_exec("cp -R $repo/public/. $docroot/ 2>&1");

    // index.php'yi repo konumunu isaret eden baslatici yap
    file_put_contents("$docroot/index.php", "<?php use Illuminate\\Foundation\\Application; use Illuminate\\Http\\Request; define('LARAVEL_START',microtime(true)); \$b='$repo'; if(file_exists(\$mm=\$b.'/storage/framework/maintenance.php'))require \$mm; require \$b.'/vendor/autoload.php'; (require_once \$b.'/bootstrap/app.php')->handleRequest(Request::capture());\n");

    // STORAGE: symlink calismadigi icin GERCEK klasor + commit'li gorselleri kopyala
    @shell_exec("rm -rf $publicStorage 2>&1");          // varsa symlink/eski klasoru kaldir
    @mkdir($publicStorage, 0755, true);
    echo @shell_exec("cp -R $repo/storage/app/public/. $publicStorage/ 2>&1");
    @shell_exec("rm -f $publicStorage/.gitignore 2>&1");

    // Eski tek-seferlik deploy ucunu kaldir (artik _ops.php kullaniliyor)
    @shell_exec("rm -f $docroot/_fix.php 2>&1");

    // Opcache'i sifirla (web SAPI'de eski derlenmis view/php bytecode kalmasin)
    if (function_exists('opcache_reset')) { @opcache_reset(); echo "opcache reset\n"; }

    echo "\nDEPLOY OK\n";
    exit;
}
if ($do === 'opcache') {
    $ok = function_exists('opcache_reset') ? @opcache_reset() : null;
    exit('opcache_reset: ' . var_export($ok, true) . "\n");
}
exit("ops: ?do=deploy | ?do=log&n=N | ?do=errmsg | ?do=lsimg\n");
