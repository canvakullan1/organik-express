# Organik Express — cPanel Yayın (Deploy) Rehberi

> Yığın: Laravel 11 + MySQL + Filament 3 + Tailwind v4 (Vite).
> **cPanel paylaşımlı hostingde Node.js yok** → varlıklar (CSS/JS) **yerelde derlenir**, çıktı sunucuya yüklenir.

---

## 1. Yerelde hazırlık (yüklemeden önce)

```bash
# 1) Frontend varlıklarını derle (public/build oluşur)
npm install
npm run build

# 2) Üretim bağımlılıkları (dev paketleri olmadan, optimize autoloader)
php composer.phar install --no-dev --optimize-autoloader
```

Yüklenecek kritik klasörler: `public/build/` (derlenmiş CSS/JS) ve `vendor/` (veya sunucuda composer çalıştır).

---

## 2. Dosya yapısı (cPanel)

İki seçenek var:

**A) Belge kökünü `public/`'e taşıyabiliyorsan (önerilen):**
Domain'in "Document Root"unu `.../organik/public` yap. Proje kökünü `public_html` dışında bir klasöre koy (ör. `/home/KULLANICI/organik`).

**B) Belge kökünü değiştiremiyorsan:**
Proje kökünü `public_html` içine koy ve kök dizine şu `.htaccess`'i ekle (istekleri `public/`'e yönlendirir):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

`public/.htaccess` Laravel ile birlikte gelir, dokunma.

---

## 3. `.env` (üretim) — sunucuda oluştur

`.env.example`'ı kopyalayıp düzenle. Gerekli değerler:

```env
APP_NAME="Organik Express"
APP_ENV=production
APP_DEBUG=false                  # ÜRETİMDE MUTLAKA false
APP_URL=https://alanadiniz.com

APP_KEY=                         # boşsa: php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpanel_db_adi
DB_USERNAME=cpanel_kullanici
DB_PASSWORD=guclu_sifre

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp                 # veya panelden Mail Ayarları'ndan
LOG_LEVEL=error
```

> Not: Bu projede DB bağlantısı `config/database.php`'de `mysql`'e sabitlenmiştir; yine de `.env`'deki DB_* değerleri kullanılır.

---

## 4. Veritabanı

```bash
php artisan migrate --force        # tabloları oluştur
php artisan db:seed --force        # (opsiyonel) demo veri — CANLIDA genelde atlanır
php artisan shield:generate --all  # rol/izinleri üret (panel erişimi için)
php artisan shield:super-admin --user=1   # yöneticiyi süper admin yap
```

Yönetici hesabı seed ile gelir (`admin@organik.test` / `admin1234`) — **canlıda şifreyi hemen değiştir.**

---

## 5. Bağlantı, izinler, cache

```bash
php artisan storage:link           # public/storage → storage/app/public
chmod -R 775 storage bootstrap/cache

# Üretim performansı için cache'le:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Kod/ayar güncellediğinde `php artisan optimize:clear` ile temizle, sonra tekrar cache'le.

---

## 6. Zamanlanmış görevler (cron)

cPanel → Cron Jobs. Paylaşımlı hostingde uzun worker yerine cron + scheduler:

```bash
# Her dakika (Laravel scheduler):
* * * * * cd /home/KULLANICI/organik && php artisan schedule:run >> /dev/null 2>&1

# Kuyruk (mail/sipariş arka plan işleri) — her 5 dk, biten işle çık:
*/5 * * * * cd /home/KULLANICI/organik && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

---

## 7. Ödeme & Mail (panelden)

Admin → **Ayarlar**:
- **Mail (SMTP) Ayarları:** kurumsal/Gmail SMTP bilgileri (girilene kadar maillere `log` ile çalışır).
- **Ödeme Ayarları:** iyzico API Key/Secret (sandbox'ı kapat, canlı anahtarları gir) ve/veya PayTR merchant_id/key/salt.
  - **PayTR Bildirim URL** (mağaza panelinde): `https://alanadiniz.com/odeme/paytr/notify`

---

## 8. Yayın sonrası kontrol listesi

- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] HTTPS zorunlu (cPanel → SSL/AutoSSL), `APP_URL` https
- [ ] Yönetici şifresi değiştirildi
- [ ] `/admin` açılıyor, ürün/sipariş/ayar ekranları çalışıyor
- [ ] Vitrin açılıyor, sepete ekleme + checkout (test kartı/havale) çalışıyor
- [ ] Mail testi (Mail Ayarları → Test Maili Gönder)
- [ ] Ödeme sağlayıcısı (iyzico/PayTR) canlı anahtarla bir test ödemesi
- [ ] `sitemap.xml` ve `robots.txt` erişilebilir; Google Search Console'a sitemap eklendi
- [ ] KVKK/Mesafeli Satış/Gizlilik sayfaları gerçek metinlerle dolduruldu (admin → Sayfalar)
- [ ] ETBİS kaydı yapıldı, footer'a ETBİS doğrulama linki girildi (Ayarlar → SEO)
- [ ] Düzenli yedekleme (DB + storage) planlandı

---

## 9. Güncelleme (yeni sürüm yükleme)

```bash
# Yerelde: npm run build  → public/build'i yükle
php composer.phar install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
```
