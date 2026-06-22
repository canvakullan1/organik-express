# Wikimedia Commons'tan demo fotograf indirme (yalniz yerel demo; curl + bekleme ile nazik)
# Kullanim: powershell -File scripts/photo-batch.ps1 [baslangicIndeksi]

$ErrorActionPreference = 'Continue'
$root = Split-Path $PSScriptRoot -Parent
$storage = Join-Path $root 'storage\app\public'

# yol | arama | sonucIndeksi | genislik
$manifest = @(
    @('banners/photo-1.jpg',    'fresh vegetables market display', 0, 1600),
    @('banners/photo-2.jpg',    'olive oil bottles glass',         0, 1600),
    @('banners/photo-3.jpg',    'turkish breakfast spread',        0, 1600),
    @('categories/photo-1.jpg', 'fresh vegetables basket',         0, 900),
    @('categories/photo-6.jpg', 'cheese platter rustic',           0, 900),
    @('categories/photo-11.jpg','green olives bowl',               1, 900),
    @('categories/photo-15.jpg','dried legumes beans lentils',     0, 900),
    @('categories/photo-20.jpg','fresh fish ice market',           1, 900),
    @('categories/photo-24.jpg','natural handmade soap bars',      0, 900),
    @('categories/photo-28.jpg','lavender soap natural',           0, 900),
    @('products/photo-1.jpg',   'ripe red tomatoes',               1, 900),
    @('products/photo-3.jpg',   'brown chicken eggs basket',       0, 900),
    @('products/photo-4.jpg',   'honey jar wooden dipper',         0, 900),
    @('products/photo-5.jpg',   'olive oil glass bottle',          1, 900),
    @('products/photo-6.jpg',   'wheat flour bowl',                0, 900),
    @('products/photo-7.jpg',   'red lentils dry',                 0, 900),
    @('products/photo-8.jpg',   'almonds raw nuts',                0, 900),
    @('products/photo-9.jpg',   'kashkaval cheese',                0, 900),
    @('products/photo-11.jpg',  'parsley fresh herb',              0, 900),
    @('products/photo-12.jpg',  'olive oil soap natural',          0, 900),
    @('bundles/photo-1.jpg',    'vegetable box harvest',           0, 1200),
    @('bundles/photo-2.jpg',    'turkish breakfast table',         0, 1200),
    @('bundles/photo-3.jpg',    'fresh fruit basket',              0, 1200),
    @('producers/photo-1.jpg',  'olive grove trees',               0, 1200),
    @('producers/photo-2.jpg',  'vegetable greenhouse',            0, 1200),
    @('producers/photo-3.jpg',  'beekeeper apiary hives',          0, 1200),
    @('producers/photo-4.jpg',  'dairy cows pasture',              0, 1200),
    @('blog/photo-1.jpg',       'organic vegetables wooden table', 0, 1200),
    @('blog/photo-2.jpg',       'autumn fruits harvest',           0, 1200),
    @('blog/photo-3.jpg',       'artichoke dish cooked',           0, 1200),
    @('blog/photo-4.jpg',       'olive harvest picking',           0, 1200),
    @('blog/photo-5.jpg',       'lentil soup bowl',                0, 1200),
    @('blog/photo-6.jpg',       'roasted vegetables tray',         0, 1200)
)

$start = 0
if ($args.Count -ge 1) { $start = [int]$args[0] }

$ok = 0; $fail = 0
for ($i = $start; $i -lt $manifest.Count; $i++) {
    $path = $manifest[$i][0]; $search = $manifest[$i][1]; $idx = [int]$manifest[$i][2]; $w = [int]$manifest[$i][3]

    $enc = [uri]::EscapeDataString("filetype:bitmap $search")
    $api = "https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch=$enc&gsrlimit=8&gsrnamespace=6&prop=imageinfo&iiprop=url&iiurlwidth=$w&format=json"

    $json = curl.exe -s --max-time 40 $api
    Start-Sleep -Milliseconds 800
    try { $data = $json | ConvertFrom-Json } catch { $data = $null }

    $pages = @()
    if ($data -and $data.query -and $data.query.pages) {
        $pages = @($data.query.pages.PSObject.Properties.Value | Sort-Object index)
    }
    if ($pages.Count -le $idx) {
        Write-Host ("{0,2}. SONUC YOK  {1}  ({2})" -f $i, $path, $search) -ForegroundColor Yellow
        $fail++; Start-Sleep -Seconds 1; continue
    }

    $url = $pages[$idx].imageinfo[0].thumburl
    $dest = Join-Path $storage ($path -replace '/', '\')
    New-Item -ItemType Directory -Force -Path (Split-Path $dest) | Out-Null

    curl.exe -s -L --max-time 60 -o $dest $url
    $size = if (Test-Path $dest) { (Get-Item $dest).Length } else { 0 }

    if ($size -gt 5000) {
        Write-Host ("{0,2}. OK  {1,-26} {2,4}KB  ({3})" -f $i, $path, [math]::Round($size/1KB), $pages[$idx].title)
        $ok++
    } else {
        Write-Host ("{0,2}. INDIRME HATASI  {1}  <- {2}" -f $i, $path, $url) -ForegroundColor Red
        $fail++
    }
    Start-Sleep -Milliseconds 1200
}

Write-Host ""
Write-Host "Bitti: $ok basarili, $fail basarisiz"
