# Commons Special:FilePath ile bilinen dosyalari deterministik indir + akilli atama
$ErrorActionPreference = 'Continue'
$root = Split-Path $PSScriptRoot -Parent
$storage = Join-Path $root 'storage\app\public'

function Pull([string]$path, [string]$file, [int]$w) {
    $enc = [uri]::EscapeDataString($file)
    $url = "https://commons.wikimedia.org/wiki/Special:FilePath/$enc`?width=$w"
    $dest = Join-Path $storage ($path -replace '/', '\')
    New-Item -ItemType Directory -Force -Path (Split-Path $dest) | Out-Null
    curl.exe -s -L --max-time 60 -o $dest $url
    $size = if (Test-Path $dest) { (Get-Item $dest).Length } else { 0 }
    if ($size -gt 5000) { Write-Host ("OK  {0,-26} {1,4}KB  ({2})" -f $path, [math]::Round($size/1KB), $file) }
    else { Write-Host ("FAIL {0}  <- {1}" -f $path, $file) -ForegroundColor Red }
    Start-Sleep -Milliseconds 1500
}

# --- Bilinen iyi dosyalar (onceki API loglarindan) ---
Pull 'banners/photo-3.jpg'    'Turkish Breakfast Spread.jpg' 1600
Pull 'categories/photo-6.jpg' 'Turkish Breakfast Spread.jpg' 900
Pull 'categories/photo-15.jpg' 'Mixture of beans (small red, cannellini, pinto, roman, red kidney, black), peas (blackeye, yellow split), and pardina lentils 8.jpg' 900
Pull 'categories/photo-24.jpg' 'Savon artisanal naturel - Natural handmade soap - صابون طبيعي.jpg' 900
Pull 'products/photo-1.jpg'   'Bright red tomato and cross section02.jpg' 900
Pull 'products/photo-3.jpg'   'Eggs in basket 2020 G1.jpg' 900
Pull 'products/photo-7.jpg'   'Bowl of lentil soup with green and red lentils.jpg' 900
Pull 'products/photo-8.jpg'   'Liat Portal for Foodie Disorder - Raw almonds in a bowl.jpg' 900
Pull 'products/photo-11.jpg'  'Liat Portal for Foodie Disorder - Parsley.jpg' 900
Pull 'producers/photo-1.jpg'  'Olive Picking a KEDUMIM 06.jpg' 1200
Pull 'producers/photo-2.jpg'  'Vegetable planting in a greenhouse during cool weather in a rural area of the countryside.jpg' 1200
Pull 'producers/photo-4.jpg'  'Dairy cows on pasture in Ireland.jpg' 1200
Pull 'blog/photo-1.jpg'       'Organic home-grown tomatoes - unripe to ripe.jpg' 1200
Pull 'blog/photo-2.jpg'       'Fruits of autumn - geograph.org.uk - 75675.jpg' 1200
Pull 'blog/photo-3.jpg'       'Liat Portal for Foodie Disorder - North African style artichokes boiled with lemon.jpg' 1200
Pull 'blog/photo-4.jpg'       'Olive Picking a KEDUMIM 06.jpg' 1200
Pull 'blog/photo-5.jpg'       'Bowl of lentil soup with green and red lentils.jpg' 1200

Write-Host ''
Write-Host '--- Akilli atamalar (kopya) ---'
function CopyTo([string]$from, [string]$to) {
    $src = Join-Path $storage ($from -replace '/', '\')
    $dst = Join-Path $storage ($to -replace '/', '\')
    if ((Test-Path $src) -and (Get-Item $src).Length -gt 5000) {
        Copy-Item $src $dst -Force
        Write-Host ("KOPYA {0} -> {1}" -f $from, $to)
    } else { Write-Host ("KAYNAK YOK: {0}" -f $from) -ForegroundColor Yellow }
}

CopyTo 'banners/photo-1.jpg'    'categories/photo-1.jpg'   # sebze pazari -> taze meyve & sebze kategorisi
CopyTo 'banners/photo-1.jpg'    'bundles/photo-1.jpg'      # sebze pazari -> sebze kutusu
CopyTo 'categories/photo-11.jpg' 'products/photo-10.jpg'   # zeytin kasesi -> yesil zeytin urunu
CopyTo 'categories/photo-11.jpg' 'banners/photo-2.jpg'     # zeytin kasesi -> hero-2 (zeytinyagi slaydi)
CopyTo 'categories/photo-24.jpg' 'categories/photo-28.jpg' # dogal sabun -> kisisel bakim
CopyTo 'categories/photo-24.jpg' 'products/photo-12.jpg'   # dogal sabun -> sivi sabun urunu
CopyTo 'banners/photo-3.jpg'    'bundles/photo-2.jpg'      # kahvalti -> kahvalti sepeti kutusu
Write-Host 'Bitti'
