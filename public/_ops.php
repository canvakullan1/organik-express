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
if ($do === 'diag') {
    // PHP yükleme limitleri
    foreach (['file_uploads', 'upload_max_filesize', 'post_max_size', 'max_file_uploads', 'max_execution_time', 'max_input_time', 'memory_limit', 'upload_tmp_dir'] as $k) {
        echo str_pad($k, 22) . ini_get($k) . "\n";
    }
    $tmp = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
    echo str_pad('upload_tmp writable', 22) . (is_writable($tmp) ? 'EVET' : 'HAYIR <<<') . " ($tmp)\n";
    echo str_pad('gd', 22) . (extension_loaded('gd') ? 'ACIK' : 'KAPALI <<<') . "\n";
    // storage klasörleri
    $dirs = [
        'storage/app' => "$repo/storage/app",
        'storage/app/private' => "$repo/storage/app/private",
        'livewire-tmp' => "$repo/storage/app/private/livewire-tmp",
        'storage/app/public' => "$repo/storage/app/public",
        'framework/cache' => "$repo/storage/framework/cache",
    ];
    foreach ($dirs as $label => $path) {
        $exists = is_dir($path);
        $w = $exists ? (is_writable($path) ? 'yazilabilir' : 'YAZILAMAZ <<<') : 'YOK';
        echo str_pad($label, 34) . ($exists ? "var, $w" : 'YOK <<<') . "\n";
    }
    // public_html/.user.ini var mı
    $ui = "$docroot/.user.ini";
    echo str_pad('.user.ini (docroot)', 22) . (is_file($ui) ? 'VAR' : 'YOK <<<') . "\n";
    if (is_file($ui)) {
        foreach (file($ui, FILE_IGNORE_NEW_LINES) as $l) {
            if (stripos($l, 'upload_max') !== false || stripos($l, 'post_max') !== false) {
                echo '  ' . trim($l) . "\n";
            }
        }
    }
    // livewire-tmp oluşturmayı dene
    $lw = "$repo/storage/app/private/livewire-tmp";
    if (! is_dir($lw)) {
        @mkdir($lw, 0775, true);
        echo "livewire-tmp olusturuldu: " . (is_dir($lw) ? 'EVET' : 'HAYIR') . "\n";
    }
    exit;
}
if ($do === 'lsimg') {
    echo "public_html/storage: " . (is_link($publicStorage) ? 'symlink->' . readlink($publicStorage) : (is_dir($publicStorage) ? 'GERCEK-KLASOR' : 'YOK')) . "\n";
    echo "banners/photo-1.jpg: " . (file_exists("$publicStorage/banners/photo-1.jpg") ? 'VAR' : 'YOK') . "\n";
    echo "icindekiler: " . @shell_exec("ls -1 $publicStorage 2>&1");
    exit;
}
if ($do === 'import_og') {
    // Yalnızca organikgiller içe aktarma komutu (sabit, keyfi komut çalıştırmaz).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(1800);
    $flags = '';
    if (($_GET['skipimg'] ?? '') === '1') { $flags .= ' --skip-images'; }
    if (($_GET['status'] ?? '') === 'draft') { $flags .= ' --status=draft'; }
    $lim = (int) ($_GET['limit'] ?? 0);
    if ($lim > 0) { $flags .= ' --limit=' . $lim; }
    if (($_GET['reimages'] ?? '') === '1') { $flags .= ' --reimages'; }
    echo @shell_exec("cd $repo && $php artisan import:organikgiller$flags 2>&1");
    exit;
}
if ($do === 'import_eko') {
    // Yalnızca ekotime+yumurta içe aktarma komutu (sabit, keyfi komut çalıştırmaz).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(1800);
    $flags = '';
    if (($_GET['skipimg'] ?? '') === '1') { $flags .= ' --skip-images'; }
    if (($_GET['status'] ?? '') === 'draft') { $flags .= ' --status=draft'; }
    $lim = (int) ($_GET['limit'] ?? 0);
    if ($lim > 0) { $flags .= ' --limit=' . $lim; }
    if (($_GET['reimages'] ?? '') === '1') { $flags .= ' --reimages'; }
    echo @shell_exec("cd $repo && $php artisan import:ekotime$flags 2>&1");
    exit;
}
if ($do === 'import_tardas') {
    // Yalnızca Tardaş içe aktarma komutu (sabit).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(1800);
    $flags = '';
    if (($_GET['skipimg'] ?? '') === '1') { $flags .= ' --skip-images'; }
    if (($_GET['status'] ?? '') === 'draft') { $flags .= ' --status=draft'; }
    $lim = (int) ($_GET['limit'] ?? 0);
    if ($lim > 0) { $flags .= ' --limit=' . $lim; }
    if (($_GET['reimages'] ?? '') === '1') { $flags .= ' --reimages'; }
    echo @shell_exec("cd $repo && $php artisan import:tardas$flags 2>&1");
    exit;
}
if ($do === 'import_cat2') {
    // İkinci dalga çok kaynaklı katalog (sabit komut; source paramı sadece dosya adı filtreler).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(1800);
    $flags = '';
    $src = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['source'] ?? ''));
    if ($src !== '') { $flags .= ' --source=' . escapeshellarg($src); }
    if (($_GET['skipimg'] ?? '') === '1') { $flags .= ' --skip-images'; }
    if (($_GET['status'] ?? '') === 'draft') { $flags .= ' --status=draft'; }
    $lim = (int) ($_GET['limit'] ?? 0);
    if ($lim > 0) { $flags .= ' --limit=' . $lim; }
    if (($_GET['reimages'] ?? '') === '1') { $flags .= ' --reimages'; }
    echo @shell_exec("cd $repo && $php artisan import:catalog2$flags 2>&1");
    exit;
}
if ($do === 'purge_source') {
    // Bir kaynağın (ör. organikgiller) ürünlerini kaldır (soft-delete, sabit komut).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(600);
    $src = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['source'] ?? ''));
    if ($src === '') { exit("source gerekli\n"); }
    $flags = (($_GET['dryrun'] ?? '') === '1') ? ' --dry-run' : '';
    echo @shell_exec("cd $repo && $php artisan catalog:purge-source " . escapeshellarg($src) . "$flags 2>&1");
    exit;
}
if ($do === 'place_menu') {
    // Sonradan eklenen kategorileri üst gruplara yerleştir (kategori ağacı + header menü).
    if (! $php) { exit("php bulunamadi\n"); }
    echo @shell_exec("cd $repo && $php artisan catalog:place-menu 2>&1");
    exit;
}
if ($do === 'make_super') {
    if (! $php) { exit("php bulunamadi\n"); }
    $email = $_GET['email'] ?? '';
    $pass = $_GET['pass'] ?? '';
    if ($email === '' || $pass === '') { exit("email ve pass gerekli\n"); }
    $eArg = escapeshellarg($email);
    $pArg = escapeshellarg($pass);
    echo @shell_exec("cd $repo && $php artisan user:make-super $eArg $pArg 2>&1");
    exit;
}
if ($do === 'fix_images') {
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(1800);
    $flags = '';
    if (($_GET['report'] ?? '') === '1') { $flags .= ' --report'; }
    $lim = (int) ($_GET['limit'] ?? 0);
    if ($lim > 0) { $flags .= ' --limit=' . $lim; }
    echo @shell_exec("cd $repo && $php artisan products:fix-images$flags 2>&1");
    exit;
}
if ($do === 'seed_certificates') {
    // Sertifikalari (Sertifikalar sayfasi) JSON'dan ice aktar (sabit komut).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(600);
    $flags = '';
    if (($_GET['reimages'] ?? '') === '1') { $flags .= ' --reimages'; }
    if (($_GET['prune'] ?? '') === '1') { $flags .= ' --prune'; }
    echo @shell_exec("cd $repo && $php artisan certificates:seed$flags 2>&1");
    exit;
}
if ($do === 'seed_producers') {
    // Üreticileri (Üreticilerimiz sayfası) JSON'dan içe aktar (sabit komut).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(600);
    $flags = '';
    if (($_GET['reimages'] ?? '') === '1') { $flags .= ' --reimages'; }
    if (($_GET['prune'] ?? '') === '1') { $flags .= ' --prune'; }
    echo @shell_exec("cd $repo && $php artisan producers:seed$flags 2>&1");
    exit;
}
if ($do === 'write_pages') {
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(300);
    echo @shell_exec("cd $repo && $php artisan pages:write 2>&1");
    exit;
}
if ($do === 'setup_site') {
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(600);
    echo @shell_exec("cd $repo && $php artisan catalog:setup-site 2>&1");
    exit;
}
if ($do === 'purge_cat') {
    // Belirli bir kategoriyi + urunlerini kalici sil (sabit komut, slug parametreli).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(600);
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'] ?? ''));
    if ($slug === '') { exit("slug gerekli\n"); }
    echo @shell_exec("cd $repo && $php artisan catalog:purge-category " . escapeshellarg($slug) . " 2>&1");
    exit;
}
if ($do === 'cleanup_test') {
    // Yalnızca deneme ürünü temizleme komutu (sabit).
    if (! $php) { exit("php bulunamadi\n"); }
    set_time_limit(600);
    $flags = '';
    if (($_GET['dryrun'] ?? '') === '1') { $flags .= ' --dry-run'; }
    if (($_GET['force'] ?? '') === '1') { $flags .= ' --force'; }
    echo @shell_exec("cd $repo && $php artisan products:cleanup-test$flags 2>&1");
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
        // View'ları yeniden derle (taze .php dosyaları diske yazılır)
        echo @shell_exec("cd $repo && $php artisan view:cache 2>&1");
    }

    // Web SAPI opcache: derlenmiş view + uygulama PHP'lerini tek tek geçersiz kıl
    // (opcache_reset kapalıysa bile opcache_invalidate genelde açıktır).
    if (function_exists('opcache_invalidate')) {
        foreach (glob("$repo/storage/framework/views/*.php") ?: [] as $f) { @opcache_invalidate($f, true); }
        foreach (glob("$repo/bootstrap/cache/*.php") ?: [] as $f) { @opcache_invalidate($f, true); }
        echo "opcache view/bootstrap invalidated\n";
    }

    // public/ -> public_html (build, htaccess, favicon...)
    echo @shell_exec("cp -R $repo/public/. $docroot/ 2>&1");

    // index.php'yi repo konumunu isaret eden baslatici yap
    file_put_contents("$docroot/index.php", "<?php use Illuminate\\Foundation\\Application; use Illuminate\\Http\\Request; define('LARAVEL_START',microtime(true)); \$b='$repo'; if(file_exists(\$mm=\$b.'/storage/framework/maintenance.php'))require \$mm; require \$b.'/vendor/autoload.php'; (require_once \$b.'/bootstrap/app.php')->handleRequest(Request::capture());\n");

    // STORAGE: repo'daki tohum gorselleri public_html/storage'a BIRLESTIR.
    // ONEMLI: rm -rf YOK — panelden yuklenen logo/favicon/urun gorselleri deploy'da SILINMEZ.
    if (is_link($publicStorage)) { @unlink($publicStorage); }        // eski symlink varsa kaldir
    if (! is_dir($publicStorage)) { @mkdir($publicStorage, 0755, true); }
    echo @shell_exec("cp -R $repo/storage/app/public/. $publicStorage/ 2>&1"); // ustune yazar; admin dosyalarini korur
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
