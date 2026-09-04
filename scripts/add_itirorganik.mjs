/**
 * itirorganik.com (Wix Stores) -> database/data/catalog2/itirorganik.json
 * Ad/fiyat/SKU/marka ürün sayfasından, görsel Wix CDN'den (orijinal boyut).
 * Kategori ürün adından bizim taksonomiye eşlenir.
 *   node scripts/add_itirorganik.mjs
 */
import { writeFileSync, existsSync, mkdirSync, unlinkSync, statSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const exec = promisify(execFile);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const IMG = join(ROOT, 'storage', 'app', 'public', 'products');
mkdirSync(IMG, { recursive: true });
const UA = { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' };

const TR = { 'ç': 'c', 'Ç': 'c', 'ğ': 'g', 'Ğ': 'g', 'ı': 'i', 'İ': 'i', 'ö': 'o', 'Ö': 'o', 'ş': 's', 'Ş': 's', 'ü': 'u', 'Ü': 'u', 'â': 'a', 'î': 'i', 'û': 'u' };
const slugify = (s) => String(s).replace(/[çÇğĞıİöÖşŞüÜâîû]/g, (c) => TR[c] || c)
  .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
const dec = (s) => String(s || '').replace(/&amp;/g, '&').replace(/&quot;/g, '"')
  .replace(/&#0?39;/g, "'").replace(/&#(\d+);/g, (_, d) => String.fromCharCode(+d))
  .replace(/&nbsp;/g, ' ').replace(/\s{2,}/g, ' ').trim();

async function get(u, tries = 2) {
  for (let i = 0; i < tries; i++) {
    try {
      const r = await fetch(u, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(30000) });
      if (r.ok) return await r.text();
    } catch {}
  }
  return '';
}

async function pool(items, fn, conc = 6) {
  const out = [];
  let i = 0, done = 0;
  await Promise.all(Array.from({ length: Math.min(conc, items.length) }, async () => {
    while (i < items.length) {
      const k = i++;
      out[k] = await fn(items[k]);
      if (++done % 20 === 0) process.stderr.write(`  ${done}/${items.length}\r`);
    }
  }));
  process.stderr.write(`  ${done}/${items.length}\n`);
  return out;
}

/** Ürün adından bizim prod kategorimize eşleme (sıra önemli: özelden genele). */
function mapCategory(name) {
  const t = name.toLowerCase();
  const has = (...k) => k.some((x) => t.includes(x));
  const word = (...k) => k.some((x) => new RegExp(`(^|[^a-z0-9çğıöşü])${x}([^a-z0-9çğıöşü]|$)`, 'i').test(t));

  if (has('sabun', 'şampuan', 'sampuan', 'deterjan', 'temizl', 'diş macunu', 'dis macunu',
    'roll-on', 'roll on', 'deodorant', 'krem', 'losyon', 'bakım', 'bakim', 'hijyen',
    'peçete', 'pecete', 'bulaşık', 'bulasik', 'çamaşır', 'camasir', 'yüzey', 'yuzey',
    'aloe vera', 'jel', 'sprey', 'diş fırça', 'dis firca', 'fırça', 'firca', 'kolonya',
    'bebek yağı', 'bebek yagi', 'bebek kremi')) return 'dogal-yasam-temizlik';
  if (has('bebek', 'ek gıda', 'ek gida')) return 'bebek';
  if (has('glutensiz')) return 'glutensiz';
  // Ana ürün tipi baharat/sos'tan ÖNCE: "Zerdeçallı Makarna" baharat değil makarna,
  // "Ekşi Mayalı Köy Somunu" sos değil ekmektir.
  if (has('ekmek', 'ekmeğ', 'ekmeg', 'somun', 'lavaş', 'lavas', 'galeta', 'simit', 'poğaça', 'pogaca')) return 'firin-ekmek';
  if (has('mercimek', 'nohut', 'fasulye', 'bulgur', 'pirinç', 'pirinc', 'makarna', 'erişte', 'eriste',
    'bakliyat', 'bezelye', 'barbunya', 'buğday', 'bugday', 'kinoa', 'şehriye', 'sehriye', 'yulaf',
    'irmik', 'nişasta', 'nisasta', 'mısır', 'misir', 'tarhana') || word('un', 'unu')) return 'bakliyat-makarna';
  if (has('baharat', 'kekik', 'nane', 'kimyon', 'zerdeçal', 'zerdecal', 'tarçın', 'tarcin',
    'karabiber', 'pul biber', 'toz biber', 'zencefil', 'sumak', 'çörek otu', 'corek otu',
    'anason', 'rezene', 'defne', 'kişniş', 'kisnis', 'yenibahar', 'safran', 'çemen', 'tuz', 'biberiye')) return 'baharat-aktar';
  if (has('yumurta')) return 'yumurta';
  if (has('zeytinyağ', 'zeytinyag', 'sızma', 'sizma', 'zeytin')) return 'zeytin-zeytinyagi-yag';
  if (has('süt', 'yoğurt', 'yogurt', 'peynir', 'kefir', 'tereyağ', 'tereyag', 'kaymak', 'ayran', 'çökelek') || word('sut', 'lor')) return 'sut-urunleri';
  if (has('sucuk', 'kıyma', 'kiyma', 'kuşbaşı', 'kusbasi', 'pastırma', 'pastirma', 'salam', 'tavuk', 'kavurma') || word('et', 'dana', 'kuzu')) return 'et-sarkuteri';
  // Saf bal ürünleri ayrı "bal" kategorisinde; reçel/pekmez/tahin kahvaltılıkta.
  if (word('bal', 'balı', 'bali', 'ballar') || has('karakovan', 'petek bal', 'süzme bal', 'suzme bal')) return 'bal';
  if (has('reçel', 'recel', 'marmelat', 'pekmez', 'tahin', 'kahvalt', 'helva')) return 'kahvaltilik-recel';
  if (has('sirke', 'salça', 'salca', 'ketçap', 'ketchup', 'sosu', 'püre', 'pure', 'ekşi', 'eksi', 'turşu', 'tursu', 'konserve') || word('sos')) return 'sos-salca-sirke';
  if (has('çikolata', 'cikolata', 'bisküvi', 'biskuvi', 'kurabiye', 'lokum', 'gofret', 'tatlı', 'cips', 'kraker')) return 'tatli-cikolata';
  if (has('ceviz', 'badem', 'fındık', 'findik', 'fıstık', 'fistik', 'üzüm', 'uzum', 'kayısı', 'kayisi',
    'incir', 'hurma', 'leblebi', 'çekirdek', 'kuruyemiş', 'kuruyemis', 'tohum', 'kuru ',
    'fıstığ', 'fistig', 'fındığ', 'findig', 'cevizi')) return 'kuruyemis-kurutulmus';
  if (has('kahve', 'çay', 'cayi', 'içecek', 'icecek', 'limonata', 'şurup', 'surup', 'konsantre',
    'espresso', 'melisa', 'ekinezya', 'ıhlamur', 'ihlamur', 'papatya', 'adaçay', 'adacay', 'çiçeği', 'cicegi')) return 'icecek-cay';
  return null;
}

// 1) ürün URL'leri
const sm = await get('https://www.itirorganik.com/store-products-sitemap.xml');
const urls = [...new Set([...sm.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)].map((m) => m[1]))];
console.error(`${urls.length} ürün sayfası indiriliyor...`);

// 2) sayfalardan ad/fiyat/görsel
const rows = await pool(urls, async (u) => {
  const t = await get(u);
  if (!t) return null;
  let name = dec((t.match(/<meta[^>]+property=["']og:title["'][^>]+content=["']([^"']+)/i) || [])[1] || '');
  name = name.replace(/\s*\|\s*www\.itirorganik\.com\s*$/i, '').trim();
  if (!name) return null;

  // fiyat: JSON-LD > Wix state > meta
  const m1 = t.match(/"priceCurrency"\s*:\s*"TRY"\s*,\s*"price"\s*:\s*"?([0-9.,]+)/i);
  const m2 = t.match(/"price"\s*:\s*([0-9.]+)\s*,\s*"comparePrice"/i);
  const m3 = t.match(/<meta[^>]+property=["']product:price:amount["'][^>]+content=["']([0-9.,]+)/i);
  const raw = m1?.[1] ?? m2?.[1] ?? m3?.[1] ?? '';
  const price = parseFloat(String(raw).replace(/\.(?=\d{3}\b)/g, '').replace(',', '.')) || 0;
  if (price <= 0) return null;

  let img = (t.match(/<meta[^>]+property=["']og:image["'][^>]+content=["']([^"']+)/i) || [])[1] || '';
  img = img.replace(/(\.(?:jpg|jpeg|png|webp))\/v1\/.*$/i, '$1'); // Wix: orijinal boyut
  const sku = (t.match(/"sku"\s*:\s*"([^"]{3,40})"/i) || [])[1] || null;
  const brand = (t.match(/"brand"\s*:\s*"([^"]{2,40})"/i) || [])[1] || 'İtir Organik';
  return { name, price, img, sku, brand };
}, 6);

const items = rows.filter(Boolean);
console.error(`${items.length} üründe ad+fiyat bulundu`);

// 3) görseller + JSON
let ok = 0, fail = 0;
const noCat = [];
const products = [];
const seen = new Set();

for (const it of items) {
  const cat = mapCategory(it.name);
  if (!cat) { noCat.push(it.name); continue; }
  const slug = slugify(it.name);
  if (!slug || seen.has(slug)) continue;
  seen.add(slug);

  const dest = join(IMG, `${slug}-1.jpg`);
  if (it.img && !existsSync(dest)) {
    try {
      const r = await fetch(it.img, { headers: UA, redirect: 'follow', signal: AbortSignal.timeout(40000) });
      if (!r.ok) throw new Error('http ' + r.status);
      const buf = Buffer.from(await r.arrayBuffer());
      const tmp = dest + '.tmp';
      writeFileSync(tmp, buf);
      await exec('magick', [tmp, '-background', 'white', '-flatten', '-resize', '800x800>', '-quality', '82', dest]);
      unlinkSync(tmp);
      if (!existsSync(dest) || statSync(dest).size < 300) throw new Error('bos');
      ok++;
    } catch { fail++; }
  } else if (existsSync(dest)) ok++;

  const sd = `${it.name}, özenle seçilmiş organik içeriğiyle sofranıza doğallık katar; katkısız ve güvenilir.`;
  products.push({
    slug,
    name: it.name,
    category: cat,
    sku: it.sku,
    price: Math.round(it.price * 100) / 100,
    unit: 'adet',
    unit_amount: 1,
    is_weight_based: false,
    images: existsSync(dest) ? [`products/${slug}-1.jpg`] : [],
    short_description: sd,
    description: `<p><strong>${it.name}</strong>, ${it.brand} güvencesiyle Organik Express rafında. `
      + 'Doğal ve katkısız içeriğiyle sağlıklı beslenmeye katkı sağlar.</p>'
      + '<ul><li>Organik içerik, katkısız üretim</li><li>Özenli paketleme ile taze teslim</li>'
      + '<li>Güvenle sipariş verin, kapınıza gelsin</li></ul>',
    meta_title: `${it.name} | Organik Express`,
    meta_description: sd,
  });
}

writeFileSync(join(ROOT, 'database/data/catalog2/itirorganik.json'),
  JSON.stringify({ source: 'itirorganik', scraped_at: new Date().toISOString(), products }, null, 2), 'utf8');

const cats = {};
products.forEach((p) => { cats[p.category] = (cats[p.category] || 0) + 1; });
console.log(`\nitirorganik: ${products.length} ürün | görsel ${ok} ok / ${fail} hata`);
Object.entries(cats).sort((a, b) => b[1] - a[1]).forEach(([k, v]) => console.log(`  ${k.padEnd(24)} ${v}`));
if (noCat.length) {
  console.log(`\nKATEGORISIZ (${noCat.length}) — atlandi:`);
  noCat.slice(0, 25).forEach((n) => console.log('  ' + n));
}
